<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/controllers/ShipperController.php';

requireShipperLogin();
startSession();

$pdo = Database::getConnection();
$shipper = $_SESSION['user'];
$shipperId = (int)$shipper['id'];
ShipperController::ensureTables($pdo);
$isOnline = ShipperController::isOnline($pdo, $shipperId);

function shipperMoney(float|string|null $value): string
{
    return number_format((float)$value, 0, ',', '.') . 'đ';
}

function shipperRangeUrl(string $paramName, string $rangeKey): string
{
    $query = $_GET;
    $query[$paramName] = $rangeKey;
    return SHIPPER_DASHBOARD_URL . '?' . http_build_query($query);
}

function shipperFilterUrl(string $filter): string
{
    $query = $_GET;
    $query['my_filter'] = $filter;
    return SHIPPER_DASHBOARD_URL . '?' . http_build_query($query);
}

function shipperRangeStartDate(array $range): string
{
    if (isset($range['days'])) {
        return date('Y-m-d', strtotime('-' . (int)$range['days'] . ' days'));
    }
    if (isset($range['months'])) {
        return date('Y-m-d', strtotime('-' . (int)$range['months'] . ' months'));
    }
    return date('Y-m-d', strtotime('-1 year'));
}

function shipperActionLabel(string $action): string
{
    return match ($action) {
        'accept' => 'Đã nhận đơn',
        'complete' => 'Đã hoàn thành',
        'skip' => 'Đã bỏ qua',
        'online' => 'Đã bật online',
        'offline' => 'Đã tắt online',
        'cod_collected' => 'Đã thu COD',
        'issue_report' => 'Đã báo sự cố',
        'cancel_order' => 'Đã hủy đơn',
        'profile_update' => 'Đã cập nhật hồ sơ',
        default => 'Hoạt động',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

    try {
        requireValidCsrf();

        if ($action === 'set_online') {
            $nextOnline = (int)($_POST['is_online'] ?? 0) === 1;
            ShipperController::setOnline($pdo, $shipperId, $nextOnline);
            setFlash('success', $nextOnline ? 'Đã bật trạng thái online.' : 'Đã chuyển sang ngoại tuyến.');
        } elseif ($action === 'update_profile') {
            $freshShipper = ShipperController::updateProfile($pdo, $shipperId, $_POST, $_FILES);
            $_SESSION['user'] = $freshShipper;
            setFlash('success', 'Đã cập nhật hồ sơ shipper.');
        } else {
            if (!$orderId || $orderId <= 0) {
                throw new RuntimeException('Đơn hàng không hợp lệ.');
            }

            if ($action === 'accept') {
                ShipperController::accept($pdo, (int)$orderId, $shipperId);
                setFlash('success', 'Đã nhận đơn giao.');
            } elseif ($action === 'skip') {
                ShipperController::skip($pdo, (int)$orderId, $shipperId, (string)($_POST['reason'] ?? ''));
                setFlash('success', 'Đã lưu lý do bỏ qua đơn.');
            } elseif ($action === 'complete') {
                ShipperController::complete($pdo, (int)$orderId, $shipperId, (string)($_POST['delivery_note'] ?? ''));
                setFlash('success', 'Đã hoàn thành đơn giao.');
            } elseif ($action === 'collect_cod') {
                ShipperController::collectCod($pdo, (int)$orderId, $shipperId);
                setFlash('success', 'Đã xác nhận thu tiền COD.');
            } elseif ($action === 'report_issue') {
                ShipperController::reportIssue($pdo, (int)$orderId, $shipperId, (string)($_POST['issue_reason'] ?? ''), (string)($_POST['issue_note'] ?? ''));
                setFlash('success', 'Đã lưu sự cố đơn hàng.');
            } elseif ($action === 'cancel_order') {
                ShipperController::cancel($pdo, (int)$orderId, $shipperId, (string)($_POST['cancel_reason'] ?? ''), (string)($_POST['cancel_note'] ?? ''));
                setFlash('success', 'Đã hủy đơn. Đơn này được tính là không hoàn thành trong KPI.');
            }
        }
    } catch (Throwable $exception) {
        setFlash('danger', $exception->getMessage());
    }

    redirect(SHIPPER_DASHBOARD_URL);
}

$shipperStmt = $pdo->prepare('SELECT id, name, phone, avatar, description, is_active, created_at FROM shippers WHERE id = :id LIMIT 1');
$shipperStmt->execute(['id' => $shipperId]);
$shipper = $shipperStmt->fetch() ?: $shipper;
$_SESSION['user'] = $shipper;

$readyStmt = $pdo->prepare(
    "SELECT o.*
     FROM orders o
     WHERE o.status = 'Ready'
       AND COALESCE(o.delivery_type, 'delivery') = 'delivery'
       AND COALESCE(o.delivery_status, 'unchecked') <> 'too_far'
       AND NOT EXISTS (
           SELECT 1
           FROM shipper_order_actions a
           WHERE a.order_id = o.id
             AND a.shipper_id = :shipper_id
             AND a.action = 'skip'
       )
     ORDER BY o.created_at ASC"
);
$readyStmt->execute(['shipper_id' => $shipperId]);
$readyOrders = $readyStmt->fetchAll();
$myOrders = StaffOrder::byShipper($pdo, $shipperId);
$activeDeliveries = array_values(array_filter($myOrders, static fn(array $order): bool => $order['status'] === 'Delivering'));

$rangeOptions = [
    '7d' => ['label' => '7 ngày', 'days' => 6],
    '1m' => ['label' => '1 tháng', 'months' => 1],
    '3m' => ['label' => '3 tháng', 'months' => 3],
    '6m' => ['label' => '6 tháng', 'months' => 6],
    '1y' => ['label' => '1 năm', 'years' => 1],
];
$selectedIncomeRange = (string)($_GET['income_range'] ?? '7d');
$selectedHistoryRange = (string)($_GET['history_range'] ?? '7d');
if (!isset($rangeOptions[$selectedIncomeRange])) {
    $selectedIncomeRange = '7d';
}
if (!isset($rangeOptions[$selectedHistoryRange])) {
    $selectedHistoryRange = '7d';
}
$incomeRange = $rangeOptions[$selectedIncomeRange];
$historyRange = $rangeOptions[$selectedHistoryRange];
$incomeRangeStartDateTime = shipperRangeStartDate($incomeRange) . ' 00:00:00';
$historyRangeStartDateTime = shipperRangeStartDate($historyRange) . ' 00:00:00';

$myFilterOptions = [
    'all' => 'Tất cả',
    'delivering' => 'Đang giao',
    'delivered' => 'Đã nhận',
    'cancelled' => 'Đã hủy',
    'today' => 'Hôm nay',
    'week' => 'Tuần này',
    'month' => 'Tháng này',
];
$selectedMyFilter = (string)($_GET['my_filter'] ?? 'all');
if (!isset($myFilterOptions[$selectedMyFilter])) {
    $selectedMyFilter = 'all';
}
$myWhere = "shipper_id = :shipper_id AND status IN ('Delivering', 'Delivered', 'Cancelled By Shipper')";
if ($selectedMyFilter === 'delivering') {
    $myWhere = "shipper_id = :shipper_id AND status = 'Delivering'";
} elseif ($selectedMyFilter === 'delivered') {
    $myWhere = "shipper_id = :shipper_id AND status = 'Delivered'";
} elseif ($selectedMyFilter === 'cancelled') {
    $myWhere = "shipper_id = :shipper_id AND status = 'Cancelled By Shipper'";
} elseif ($selectedMyFilter === 'today') {
    $myWhere = "shipper_id = :shipper_id AND status IN ('Delivering', 'Delivered', 'Cancelled By Shipper') AND DATE(updated_at) = CURDATE()";
} elseif ($selectedMyFilter === 'week') {
    $myWhere = "shipper_id = :shipper_id AND status IN ('Delivering', 'Delivered', 'Cancelled By Shipper') AND YEARWEEK(updated_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($selectedMyFilter === 'month') {
    $myWhere = "shipper_id = :shipper_id AND status IN ('Delivering', 'Delivered', 'Cancelled By Shipper') AND YEAR(updated_at) = YEAR(CURDATE()) AND MONTH(updated_at) = MONTH(CURDATE())";
}
$myFilteredStmt = $pdo->prepare("SELECT * FROM orders WHERE {$myWhere} ORDER BY updated_at DESC LIMIT 30");
$myFilteredStmt->execute(['shipper_id' => $shipperId]);
$myFilteredOrders = $myFilteredStmt->fetchAll();

$metricStmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN status = 'Delivered' THEN total ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN status = 'Delivered' AND DATE(updated_at) = CURDATE() THEN total ELSE 0 END), 0) AS today_income,
        COALESCE(SUM(CASE WHEN status = 'Delivered' AND DATE(updated_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN total ELSE 0 END), 0) AS yesterday_income,
        COALESCE(SUM(CASE WHEN status = 'Delivered' AND YEARWEEK(updated_at, 1) = YEARWEEK(CURDATE(), 1) THEN total ELSE 0 END), 0) AS week_income,
        COALESCE(SUM(CASE WHEN status = 'Delivered' AND YEAR(updated_at) = YEAR(CURDATE()) AND MONTH(updated_at) = MONTH(CURDATE()) THEN total ELSE 0 END), 0) AS month_income,
        COALESCE(SUM(CASE WHEN status = 'Delivered' AND YEAR(updated_at) = YEAR(CURDATE()) THEN total ELSE 0 END), 0) AS year_income,
        COUNT(CASE WHEN status = 'Delivered' AND DATE(updated_at) = CURDATE() THEN 1 END) AS today_orders,
        COUNT(CASE WHEN status = 'Delivered' AND YEAR(updated_at) = YEAR(CURDATE()) AND MONTH(updated_at) = MONTH(CURDATE()) THEN 1 END) AS month_orders,
        COUNT(CASE WHEN status IN ('Delivering', 'Delivered', 'Cancelled By Shipper') THEN 1 END) AS assigned_orders,
        COUNT(CASE WHEN status = 'Delivered' THEN 1 END) AS completed_orders,
        COUNT(CASE WHEN status = 'Cancelled By Shipper' THEN 1 END) AS cancelled_orders,
        COUNT(CASE WHEN issue_reported_at IS NOT NULL THEN 1 END) AS delayed_orders,
        COALESCE(SUM(CASE WHEN payment_method = 'COD' AND cod_collected = 1 THEN total ELSE 0 END), 0) AS cod_collected_total,
        COALESCE(SUM(CASE WHEN payment_method = 'COD' AND status IN ('Delivering', 'Delivered') AND cod_collected = 0 THEN total ELSE 0 END), 0) AS cod_pending_total
     FROM orders
     WHERE shipper_id = :shipper_id"
);
$metricStmt->execute(['shipper_id' => $shipperId]);
$metrics = $metricStmt->fetch() ?: [];
$todayIncome = (float)($metrics['today_income'] ?? 0);
$yesterdayIncome = (float)($metrics['yesterday_income'] ?? 0);
$weekIncome = (float)($metrics['week_income'] ?? 0);
$monthIncome = (float)($metrics['month_income'] ?? 0);
$yearIncome = (float)($metrics['year_income'] ?? 0);
$walletBalance = (float)($metrics['total_income'] ?? 0);
$todayCompleted = (int)($metrics['today_orders'] ?? 0);
$monthOrders = (int)($metrics['month_orders'] ?? 0);
$assignedOrders = (int)($metrics['assigned_orders'] ?? 0);
$completedOrders = (int)($metrics['completed_orders'] ?? 0);
$cancelledOrders = (int)($metrics['cancelled_orders'] ?? 0);
$delayedOrders = (int)($metrics['delayed_orders'] ?? 0);
$codCollectedTotal = (float)($metrics['cod_collected_total'] ?? 0);
$codPendingTotal = (float)($metrics['cod_pending_total'] ?? 0);
$targetDone = min(100, (int)round(($todayCompleted / 25) * 100));
$completionRate = $assignedOrders > 0 ? (int)round(($completedOrders / $assignedOrders) * 100) : 0;

if ($selectedIncomeRange === '7d') {
    $chartRowsStmt = $pdo->prepare(
        "SELECT DATE(updated_at) AS bucket, COALESCE(SUM(total), 0) AS income, COUNT(*) AS orders
         FROM orders
         WHERE shipper_id = :shipper_id AND status = 'Delivered' AND updated_at >= :start_date
         GROUP BY DATE(updated_at)
         ORDER BY bucket"
    );
} else {
    $chartRowsStmt = $pdo->prepare(
        "SELECT DATE_FORMAT(updated_at, '%Y-%m') AS bucket, COALESCE(SUM(total), 0) AS income, COUNT(*) AS orders
         FROM orders
         WHERE shipper_id = :shipper_id AND status = 'Delivered' AND updated_at >= :start_date
         GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
         ORDER BY bucket"
    );
}
$chartRowsStmt->execute(['shipper_id' => $shipperId, 'start_date' => $incomeRangeStartDateTime]);
$incomeByBucket = [];
$ordersByBucket = [];
foreach ($chartRowsStmt->fetchAll() as $row) {
    $incomeByBucket[$row['bucket']] = (float)$row['income'];
    $ordersByBucket[$row['bucket']] = (int)$row['orders'];
}
$chartLabels = [];
$shipFees = [];
$orderCounts = [];
if ($selectedIncomeRange === '7d') {
    $cursor = new DateTimeImmutable(shipperRangeStartDate($incomeRange));
    $endDate = new DateTimeImmutable(date('Y-m-d'));
    while ($cursor <= $endDate) {
        $bucket = $cursor->format('Y-m-d');
        $chartLabels[] = $cursor->format('d/m');
        $shipFees[] = $incomeByBucket[$bucket] ?? 0;
        $orderCounts[] = $ordersByBucket[$bucket] ?? 0;
        $cursor = $cursor->modify('+1 day');
    }
} else {
    $monthCount = (int)($incomeRange['months'] ?? 12);
    $startMonth = (new DateTimeImmutable('first day of this month'))->modify('-' . ($monthCount - 1) . ' months');
    for ($i = 0; $i < $monthCount; $i++) {
        $month = $startMonth->modify("+{$i} months");
        $bucket = $month->format('Y-m');
        $chartLabels[] = $month->format('m/Y');
        $shipFees[] = $incomeByBucket[$bucket] ?? 0;
        $orderCounts[] = $ordersByBucket[$bucket] ?? 0;
    }
}

$historyStmt = $pdo->prepare(
    "SELECT * FROM orders
     WHERE shipper_id = :shipper_id
       AND status IN ('Delivered', 'Cancelled By Shipper')
       AND updated_at >= :start_date
     ORDER BY updated_at DESC"
);
$historyStmt->execute(['shipper_id' => $shipperId, 'start_date' => $historyRangeStartDateTime]);
$historyOrders = $historyStmt->fetchAll();

$topOrdersStmt = $pdo->prepare(
    "SELECT order_code, customer_name, total, updated_at
     FROM orders
     WHERE shipper_id = :shipper_id AND status = 'Delivered'
     ORDER BY total DESC, updated_at DESC
     LIMIT 5"
);
$topOrdersStmt->execute(['shipper_id' => $shipperId]);
$topOrders = $topOrdersStmt->fetchAll();

$avgMinutesStmt = $pdo->prepare(
    "SELECT AVG(TIMESTAMPDIFF(MINUTE, accepted.created_at, completed.created_at)) AS avg_minutes
     FROM shipper_order_actions accepted
     JOIN shipper_order_actions completed
       ON completed.order_id = accepted.order_id
      AND completed.shipper_id = accepted.shipper_id
      AND completed.action = 'complete'
     WHERE accepted.shipper_id = :shipper_id AND accepted.action = 'accept'"
);
$avgMinutesStmt->execute(['shipper_id' => $shipperId]);
$avgDeliveryMinutes = (int)round((float)($avgMinutesStmt->fetchColumn() ?: 0));

$actionsStmt = $pdo->prepare(
    "SELECT a.*, o.order_code
     FROM shipper_order_actions a
     LEFT JOIN orders o ON o.id = a.order_id
     WHERE a.shipper_id = :shipper_id
     ORDER BY a.created_at DESC, a.id DESC
     LIMIT 6"
);
$actionsStmt->execute(['shipper_id' => $shipperId]);
$recentActions = $actionsStmt->fetchAll();

$incomeGrowth = 0.0;
if ($yesterdayIncome > 0) {
    $incomeGrowth = (($todayIncome - $yesterdayIncome) / $yesterdayIncome) * 100;
} elseif ($todayIncome > 0) {
    $incomeGrowth = 100;
}
$deliveredOrdersInSelectedRange = array_sum($orderCounts);
$flash = getFlash();
$isProfilePage = (string)($_GET['page'] ?? '') === 'profile';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bảng giao hàng YumGO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(BASE_URL . '/assets/css/staff.css') ?>" rel="stylesheet">
</head>
<body class="driver-shell">
<header class="driver-topbar">
    <div class="driver-topbar-inner">
        <div class="driver-brand"><span class="brand-mark">Y</span><span>YumGO Driver</span></div>
        <div class="driver-profile">
            <span class="ready-pill"><i class="bi bi-box-seam"></i> <?= count($readyOrders) ?> đơn sẵn sàng</span>
            <form method="post" class="m-0">
                            <?= csrfField() ?>
                <input type="hidden" name="action" value="set_online">
                <input type="hidden" name="is_online" value="<?= $isOnline ? 0 : 1 ?>">
                <button class="online-toggle <?= $isOnline ? '' : 'offline-toggle' ?>" type="submit"><span class="online-dot"></span><?= $isOnline ? 'Online' : 'Offline' ?></button>
            </form>
            <span class="wallet-pill"><i class="bi bi-wallet2"></i> <?= shipperMoney($walletBalance) ?></span>
            <a class="driver-avatar-link" href="<?= e(SHIPPER_DASHBOARD_URL . '?page=profile') ?>" title="Hồ sơ shipper">
                <?php if (!empty($shipper['avatar'])): ?>
                    <img class="admin-avatar" src="<?= e(BASE_URL . '/uploads/avatars/' . $shipper['avatar']) ?>" alt="">
                <?php else: ?>
                    <span class="admin-avatar avatar-fallback">S</span>
                <?php endif; ?>
            </a>
            <span class="d-none d-md-inline text-muted small"><?= e($shipper['name']) ?></span>
            <a class="btn btn-outline-danger btn-sm" href="<?= e(BASE_URL . '/shipper/logout.php') ?>">Đăng xuất</a>
        </div>
    </div>
</header>

<section class="driver-hero">
    <div class="row align-items-center g-3">
        <div class="col-lg-7">
            <h1 class="mb-2"><?= $isOnline ? 'Sẵn sàng nhận đơn' : 'Bạn đang ngoại tuyến' ?>, <?= e(explode(' ', trim($shipper['name']))[0] ?? 'Shipper') ?>?</h1>
            <p class="mb-0 opacity-75">Có <?= count($readyOrders) ?> đơn đã chuẩn bị. Thu nhập chỉ được cộng khi đơn được xác nhận đã giao.</p>
        </div>
        <div class="col-lg-5">
            <div class="goal-progress mb-2"><span style="width: <?= $targetDone ?>%"></span></div>
            <div class="d-flex justify-content-between small fw-semibold"><span>Mục tiêu hôm nay</span><span><?= $targetDone ?>%</span></div>
        </div>
    </div>
</section>

<main class="driver-container">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($isProfilePage): ?>
        <section class="driver-profile-page">
            <div class="driver-section-card driver-profile-card">
                <div class="driver-section-title">
                    <div>
                        <h2>Hồ sơ shipper</h2>
                        <div class="text-muted small">Quản lý thông tin tài khoản giao hàng</div>
                    </div>
                    <a class="btn btn-outline-primary" href="<?= e(SHIPPER_DASHBOARD_URL) ?>"><i class="bi bi-arrow-left"></i> Quay về</a>
                </div>
                <form method="post" enctype="multipart/form-data">
                            <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="profile-avatar-preview mb-3">
                        <?php if (!empty($shipper['avatar'])): ?>
                            <img src="<?= e(BASE_URL . '/uploads/avatars/' . $shipper['avatar']) ?>" alt="">
                        <?php else: ?>
                            <span>S</span>
                        <?php endif; ?>
                    </div>
                    <label class="form-label">Họ tên</label>
                    <input class="form-control mb-3" name="name" value="<?= e($shipper['name']) ?>" required>
                    <label class="form-label">Số điện thoại</label>
                    <input class="form-control mb-3" name="phone" inputmode="numeric" maxlength="10" value="<?= e($shipper['phone']) ?>" required>
                    <label class="form-label">Mô tả</label>
                    <textarea class="form-control mb-3" name="description" rows="4" placeholder="Khu vực giao, kinh nghiệm, ghi chú liên hệ..."><?= e($shipper['description'] ?? '') ?></textarea>
                    <label class="form-label">Mật khẩu mới</label>
                    <div class="password-field mb-3">
                        <input class="form-control" id="shipperProfilePassword" name="password" type="password" placeholder="Không nhập mới sẽ giữ nguyên">
                        <button class="password-toggle" type="button" data-toggle-password="shipperProfilePassword" aria-label="Xem mật khẩu"><i class="bi bi-eye"></i></button>
                    </div>
                    <label class="form-label">Avatar</label>
                    <input class="form-control mb-3" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp">
                    <div class="small text-muted mb-3">Ngày tạo: <?= !empty($shipper['created_at']) ? e(date('d/m/Y', strtotime($shipper['created_at']))) : '-' ?></div>
                    <button class="btn btn-primary w-100" type="submit">Lưu hồ sơ</button>
                </form>
            </div>
        </section>
    <?php else: ?>

    <section class="driver-stats">
        <div class="driver-stat-card"><span class="driver-stat-icon"><i class="bi bi-cash-coin"></i></span><div class="driver-stat-value"><?= shipperMoney($todayIncome) ?></div><div class="text-muted">Thu nhập hôm nay</div><div class="kpi-trend <?= $incomeGrowth >= 0 ? 'trend-up' : 'trend-down' ?>"><?= $incomeGrowth >= 0 ? '↑' : '↓' ?> <?= number_format(abs($incomeGrowth), 1, ',', '.') ?>% so với hôm qua</div></div>
        <div class="driver-stat-card"><span class="driver-stat-icon"><i class="bi bi-receipt-cutoff"></i></span><div class="driver-stat-value"><?= $todayCompleted ?>/25</div><div class="text-muted">Đơn hôm nay</div><div class="kpi-trend">Còn <?= max(0, 25 - $todayCompleted) ?> đơn đạt mục tiêu</div></div>
        <div class="driver-stat-card"><span class="driver-stat-icon"><i class="bi bi-truck"></i></span><div class="driver-stat-value"><?= count($activeDeliveries) ?></div><div class="text-muted">Đang giao</div><div class="kpi-trend">Tính từ đơn đã nhận</div></div>
        <div class="driver-stat-card"><span class="driver-stat-icon"><i class="bi bi-graph-up-arrow"></i></span><div class="driver-stat-value"><?= $completionRate ?>%</div><div class="text-muted">Tỷ lệ hoàn thành</div><div class="kpi-trend"><?= $cancelledOrders ?> đơn không hoàn thành, <?= $delayedOrders ?> đơn delay</div></div>
    </section>

    <div class="driver-main-grid">
        <div>
            <section class="driver-section-card mb-4" id="orders">
                <div class="driver-section-title"><h2>Đơn sẵn sàng nhận</h2><span class="badge badge-info"><?= count($readyOrders) ?> đơn</span></div>
                <?php foreach ($readyOrders as $index => $order): ?>
                    <?php $items = StaffOrder::items($pdo, (int)$order['id']); ?>
                    <article class="driver-order-card <?= $index === 0 ? 'driver-order-highlight' : '' ?>">
                        <div class="d-flex justify-content-between gap-2">
                            <div><h3 class="h5 mb-1"><?= e($order['order_code']) ?></h3><div class="fw-semibold"><?= e($order['customer_name']) ?></div></div>
                            <span class="badge <?= e(orderStatusBadgeClass($order['status'])) ?> align-self-start"><?= e(orderStatusLabel($order['status'])) ?></span>
                        </div>
                        <div class="driver-order-meta">
                            <span class="driver-meta-pill"><i class="bi bi-telephone"></i><?= e($order['phone']) ?></span>
                            <span class="driver-meta-pill"><i class="bi bi-cash"></i><?= shipperMoney($order['total']) ?></span>
                            <span class="driver-meta-pill"><i class="bi bi-geo-alt"></i><?= $order['distance_km'] !== null ? number_format((float)$order['distance_km'], 2, ',', '.') . 'km' : 'Chưa tính km' ?></span>
                            <span class="driver-meta-pill"><i class="bi bi-wallet2"></i><?= shipperMoney($order['shipping_fee']) ?> phí ship</span>
                            <span class="driver-meta-pill"><i class="bi bi-credit-card"></i><?= e($order['payment_method']) ?></span>
                        </div>
                        <div class="mb-2"><strong>Địa chỉ:</strong> <?= e($order['address']) ?></div>
                        <?php if (!empty($order['note'])): ?><div class="text-muted small mb-2">Ghi chú khách: <?= e($order['note']) ?></div><?php endif; ?>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#readyDetail<?= (int)$order['id'] ?>">Chi tiết</button>
                            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#skipModal<?= (int)$order['id'] ?>">Bỏ qua</button>
                            <form method="post" class="m-0">
                            <?= csrfField() ?><input type="hidden" name="action" value="accept"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><button class="btn btn-primary px-4" type="submit" <?= $isOnline ? '' : 'disabled' ?>><i class="bi bi-check2-circle"></i> Nhận đơn</button></form>
                        </div>
                        <div class="collapse mt-3" id="readyDetail<?= (int)$order['id'] ?>">
                            <div class="order-detail-box">
                                <div class="row g-2 mb-2"><div class="col-md-6"><strong>Khách hàng:</strong> <?= e($order['customer_name']) ?></div><div class="col-md-6"><strong>Số điện thoại:</strong> <?= e($order['phone']) ?></div><div class="col-12"><strong>Địa chỉ:</strong> <?= e($order['address']) ?></div></div>
                                <div class="table-responsive"><table class="table table-sm mb-2"><thead><tr><th>Món</th><th class="text-center">SL</th><th class="text-end">Thành tiền</th></tr></thead><tbody><?php foreach ($items as $item): ?><tr><td><?= e($item['name']) ?></td><td class="text-center"><?= (int)$item['quantity'] ?></td><td class="text-end"><?= shipperMoney($item['subtotal']) ?></td></tr><?php endforeach; ?></tbody></table></div>
                                <div class="d-flex justify-content-between"><span>Khoảng cách</span><strong><?= $order['distance_km'] !== null ? number_format((float)$order['distance_km'], 2, ',', '.') . 'km' : 'Chưa kiểm tra' ?></strong></div>
                                <div class="d-flex justify-content-between"><span>Phí ship</span><strong><?= shipperMoney($order['shipping_fee']) ?></strong></div>
                                <div class="d-flex justify-content-between"><span>Tổng thanh toán</span><strong><?= shipperMoney($order['total']) ?></strong></div>
                            </div>
                        </div>
                    </article>
                    <div class="modal fade" id="skipModal<?= (int)$order['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered"><form method="post" class="modal-content">
                            <?= csrfField() ?>
                            <div class="modal-header"><h5 class="modal-title">Bỏ qua đơn <?= e($order['order_code']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
                            <div class="modal-body"><input type="hidden" name="action" value="skip"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><label class="form-label">Lý do</label><select class="form-select" name="reason" required><option value="">Chọn lý do</option><option value="Địa chỉ quá xa">Địa chỉ quá xa</option><option value="Đang bận đơn khác">Đang bận đơn khác</option><option value="Đã hết ca">Đã hết ca</option></select></div>
                            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-outline-secondary">Lưu lý do</button></div>
                        </form></div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$readyOrders): ?><div class="alert alert-secondary mb-0">Không có đơn đã chuẩn bị.</div><?php endif; ?>
            </section>

            <section class="driver-section-card mb-4" id="my-orders">
                <div class="driver-section-title">
                    <h2>Đơn của tôi</h2>
                    <div class="dropdown"><button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= e($myFilterOptions[$selectedMyFilter]) ?></button><ul class="dropdown-menu dropdown-menu-end"><?php foreach ($myFilterOptions as $key => $label): ?><li><a class="dropdown-item <?= $selectedMyFilter === $key ? 'active' : '' ?>" href="<?= e(shipperFilterUrl($key)) ?>"><?= e($label) ?></a></li><?php endforeach; ?></ul></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Giá trị</th><th>Trạng thái</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($myFilteredOrders as $order): ?>
                            <?php $items = StaffOrder::items($pdo, (int)$order['id']); ?>
                            <tr>
                                <td><?= e($order['order_code']) ?><div class="text-muted small"><?= e(date('d/m/Y H:i', strtotime($order['updated_at']))) ?></div></td>
                                <td><?= e($order['customer_name']) ?><div class="text-muted small"><?= e($order['phone']) ?></div></td>
                                <td><?= shipperMoney($order['total']) ?><div class="text-muted small"><?= $order['distance_km'] !== null ? number_format((float)$order['distance_km'], 2, ',', '.') . 'km' : 'Chưa tính km' ?></div></td>
                                <td><span class="badge <?= e(orderStatusBadgeClass($order['status'])) ?>"><?= e(orderStatusLabel($order['status'])) ?></span></td>
                                <td class="text-end driver-row-actions">
                                    <?php if (strtoupper((string)$order['payment_method']) === 'COD' && (int)($order['cod_collected'] ?? 0) === 1): ?>
                                        <span class="badge badge-success">Đã thu</span>
                                    <?php elseif (strtoupper((string)$order['payment_method']) === 'COD' && $order['status'] === 'Delivered'): ?>
                                        <button class="btn btn-sm btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#driverCodModal" data-order-id="<?= (int)$order['id'] ?>" data-order-code="<?= e($order['order_code']) ?>" data-order-total="<?= e(shipperMoney($order['total'])) ?>">Thu COD</button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#myDetail<?= (int)$order['id'] ?>">Chi tiết</button>
                                    <?php if ($order['status'] === 'Delivering'): ?>
                                        <?php if (strtoupper((string)$order['payment_method']) === 'COD' && (int)($order['cod_collected'] ?? 0) === 0): ?><button class="btn btn-sm btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#driverCodModal" data-order-id="<?= (int)$order['id'] ?>" data-order-code="<?= e($order['order_code']) ?>" data-order-total="<?= e(shipperMoney($order['total'])) ?>">Đã thu COD</button><?php endif; ?>
                                        <button class="btn btn-sm btn-outline-warning" type="button" data-bs-toggle="modal" data-bs-target="#driverIssueModal" data-order-id="<?= (int)$order['id'] ?>" data-order-code="<?= e($order['order_code']) ?>">Báo sự cố</button>
                                        <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#driverCancelModal" data-order-id="<?= (int)$order['id'] ?>" data-order-code="<?= e($order['order_code']) ?>">Hủy đơn</button>
                                        <button class="btn btn-sm btn-success" type="button" data-bs-toggle="modal" data-bs-target="#driverCompleteModal" data-order-id="<?= (int)$order['id'] ?>" data-order-code="<?= e($order['order_code']) ?>" data-order-total="<?= e(shipperMoney($order['total'])) ?>">Đã nhận</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr class="collapse" id="myDetail<?= (int)$order['id'] ?>"><td colspan="5"><div class="order-detail-box">
                                <div class="mb-2"><strong>Địa chỉ:</strong> <?= e($order['address']) ?></div>
                                <div class="table-responsive"><table class="table table-sm mb-2"><thead><tr><th>Món</th><th class="text-center">SL</th><th class="text-end">Thành tiền</th></tr></thead><tbody><?php foreach ($items as $item): ?><tr><td><?= e($item['name']) ?></td><td class="text-center"><?= (int)$item['quantity'] ?></td><td class="text-end"><?= shipperMoney($item['subtotal']) ?></td></tr><?php endforeach; ?></tbody></table></div>
                                <div class="d-flex justify-content-between"><span>Khoảng cách</span><strong><?= $order['distance_km'] !== null ? number_format((float)$order['distance_km'], 2, ',', '.') . 'km' : 'Chưa kiểm tra' ?></strong></div>
                                <div class="d-flex justify-content-between"><span>Phí ship</span><strong><?= shipperMoney($order['shipping_fee']) ?></strong></div>
                                <div class="d-flex justify-content-between"><span>Tổng thanh toán</span><strong><?= shipperMoney($order['total']) ?></strong></div>
                                <?php if (strtoupper((string)$order['payment_method']) === 'COD'): ?><div class="d-flex justify-content-between"><span>COD</span><strong><?= (int)($order['cod_collected'] ?? 0) === 1 ? 'Đã thu' : 'Chưa thu' ?></strong></div><?php endif; ?>
                                <?php if (!empty($order['issue_reason'])): ?><div class="issue-note mt-2"><strong>Sự cố:</strong> <?= e($order['issue_reason']) ?><?php if (!empty($order['issue_note'])): ?><div><?= e($order['issue_note']) ?></div><?php endif; ?></div><?php endif; ?>
                            </div></td></tr>

                            <?php if ($order['status'] === 'Delivering'): ?>
                                <?php if (strtoupper((string)$order['payment_method']) === 'COD' && (int)($order['cod_collected'] ?? 0) === 0): ?>
                                    <div class="modal fade" id="codModal<?= (int)$order['id'] ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form method="post" class="modal-content">
                            <?= csrfField() ?><div class="modal-header"><h5 class="modal-title">Xác nhận thu COD <?= e($order['order_code']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div><div class="modal-body"><input type="hidden" name="action" value="collect_cod"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><p class="mb-0">Xác nhận bạn đã thu đủ <strong><?= shipperMoney($order['total']) ?></strong> từ khách hàng.</p></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-success">Đã thu tiền</button></div></form></div></div>
                                <?php endif; ?>
                                <div class="modal fade" id="issueModal<?= (int)$order['id'] ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form method="post" class="modal-content">
                            <?= csrfField() ?><div class="modal-header"><h5 class="modal-title">Báo sự cố <?= e($order['order_code']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div><div class="modal-body"><input type="hidden" name="action" value="report_issue"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><label class="form-label">Lý do</label><select class="form-select mb-3" name="issue_reason" required><option value="">Chọn lý do</option><option value="Khách không nghe máy">Khách không nghe máy</option><option value="Sai địa chỉ">Sai địa chỉ</option><option value="Khách hủy khi giao">Khách hủy khi giao</option><option value="Sự cố xe">Sự cố xe</option><option value="Thời tiết xấu">Thời tiết xấu</option></select><label class="form-label">Ghi chú thêm</label><textarea class="form-control" name="issue_note" rows="3" placeholder="Nhập chi tiết để admin dễ xử lý"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-warning">Lưu sự cố</button></div></form></div></div>
                                <div class="modal fade" id="completeModal<?= (int)$order['id'] ?>" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form method="post" class="modal-content">
                            <?= csrfField() ?><div class="modal-header"><h5 class="modal-title">Xác nhận đã nhận <?= e($order['order_code']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div><div class="modal-body"><input type="hidden" name="action" value="complete"><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><p class="mb-3">Sau khi xác nhận, giá trị đơn sẽ được cộng vào thống kê thu nhập.</p><label class="form-label">Ghi chú giao hàng</label><textarea class="form-control" name="delivery_note" rows="3" placeholder="Ví dụ: Khách đã nhận hàng, thanh toán đủ COD."></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-success">Xác nhận đã nhận</button></div></form></div></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (!$myFilteredOrders): ?><tr><td colspan="5" class="text-muted">Chưa có đơn phù hợp bộ lọc.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="driver-section-card mb-4" id="income">
                <div class="driver-section-title"><h2>Thống kê số lượng đơn đã nhận</h2><div class="dropdown"><button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= e($incomeRange['label']) ?></button><ul class="dropdown-menu dropdown-menu-end"><?php foreach ($rangeOptions as $key => $option): ?><li><a class="dropdown-item <?= $selectedIncomeRange === $key ? 'active' : '' ?>" href="<?= e(shipperRangeUrl('income_range', $key)) ?>"><?= e($option['label']) ?></a></li><?php endforeach; ?></ul></div></div>
                <div class="delivery-count-summary mb-3"><span class="driver-stat-icon"><i class="bi bi-check2-circle"></i></span><div><div class="text-muted small">Tổng đơn đã nhận trong <?= e($incomeRange['label']) ?></div><strong><?= (int)$deliveredOrdersInSelectedRange ?> đơn</strong></div></div>
                <div class="driver-chart-box">
                    <canvas id="driverIncomeChart"></canvas>
                </div>
            </section>

            <section class="driver-section-card" id="history">
                <div class="driver-section-title"><h2>Lịch sử đã giao</h2><div class="dropdown"><button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= e($historyRange['label']) ?></button><ul class="dropdown-menu dropdown-menu-end"><?php foreach ($rangeOptions as $key => $option): ?><li><a class="dropdown-item <?= $selectedHistoryRange === $key ? 'active' : '' ?>" href="<?= e(shipperRangeUrl('history_range', $key)) ?>"><?= e($option['label']) ?></a></li><?php endforeach; ?></ul></div></div>
                <div class="table-responsive"><table class="table table-modern align-middle"><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Giá trị đơn</th><th>COD</th><th>Thời gian giao</th></tr></thead><tbody><?php foreach ($historyOrders as $order): ?><tr><td><?= e($order['order_code']) ?></td><td><?= e($order['customer_name']) ?><div class="text-muted small"><?= e($order['phone']) ?></div></td><td><?= shipperMoney($order['total']) ?></td><td><?= strtoupper((string)$order['payment_method']) === 'COD' ? ((int)($order['cod_collected'] ?? 0) === 1 ? 'Đã thu' : 'Chưa thu') : '-' ?></td><td><?= e(date('d/m/Y H:i', strtotime($order['updated_at']))) ?></td></tr><?php endforeach; ?><?php if (!$historyOrders): ?><tr><td colspan="5" class="text-muted">Chưa có đơn đã nhận trong khoảng này.</td></tr><?php endif; ?></tbody></table></div>
            </section>

            <section class="driver-section-card" id="delivery-history">
                <div class="driver-section-title">
                    <h2>Lịch sử giao hàng</h2>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown"><?= e($historyRange['label']) ?></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($rangeOptions as $key => $option): ?>
                                <li><a class="dropdown-item <?= $selectedHistoryRange === $key ? 'active' : '' ?>" href="<?= e(shipperRangeUrl('history_range', $key)) ?>"><?= e($option['label']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Giá trị đơn</th><th>COD</th><th>Trạng thái đơn hàng</th><th>Thời gian</th></tr></thead>
                        <tbody>
                        <?php foreach ($historyOrders as $order): ?>
                            <tr>
                                <td><?= e($order['order_code']) ?></td>
                                <td><?= e($order['customer_name']) ?><div class="text-muted small"><?= e($order['phone']) ?></div></td>
                                <td><?= shipperMoney($order['total']) ?></td>
                                <td>
                                    <?php if (strtoupper((string)$order['payment_method']) !== 'COD'): ?>
                                        -
                                    <?php elseif ((int)($order['cod_collected'] ?? 0) === 1): ?>
                                        <span class="badge badge-success">Đã thu</span>
                                    <?php elseif ($order['status'] === 'Delivered'): ?>
                                        <button class="btn btn-sm btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#driverCodModal" data-order-id="<?= (int)$order['id'] ?>" data-order-code="<?= e($order['order_code']) ?>" data-order-total="<?= e(shipperMoney($order['total'])) ?>">Thu COD</button>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Chưa thu</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($order['status'] === 'Delivered'): ?>
                                        <span class="badge badge-success">Đã nhận</span>
                                    <?php elseif ($order['status'] === 'Cancelled By Shipper'): ?>
                                        <span class="badge badge-danger">Đã hủy</span>
                                    <?php else: ?>
                                        <span class="badge <?= e(orderStatusBadgeClass($order['status'])) ?>"><?= e(orderStatusLabel($order['status'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(date('d/m/Y H:i', strtotime($order['updated_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$historyOrders): ?>
                            <tr><td colspan="6" class="text-muted">Chưa có đơn đã nhận hoặc đã hủy trong khoảng này.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <aside>
            <section class="driver-section-card mb-4" id="profile">
                <div class="driver-section-title"><h2>Hồ sơ shipper</h2></div>
                <form method="post" enctype="multipart/form-data">
                            <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_profile">
                    <label class="form-label">Họ tên</label>
                    <input class="form-control mb-3" name="name" value="<?= e($shipper['name']) ?>" required>
                    <label class="form-label">Số điện thoại</label>
                    <input class="form-control mb-3" name="phone" inputmode="numeric" maxlength="10" value="<?= e($shipper['phone']) ?>" required>
                    <label class="form-label">Mật khẩu mới</label>
                    <div class="password-field mb-3">
                        <input class="form-control" id="shipperProfilePassword" name="password" type="password" placeholder="Không nhập mới sẽ giữ nguyên">
                        <button class="password-toggle" type="button" data-toggle-password="shipperProfilePassword" aria-label="Xem mật khẩu"><i class="bi bi-eye"></i></button>
                    </div>
                    <label class="form-label">Avatar</label>
                    <input class="form-control mb-3" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp">
                    <div class="small text-muted mb-3">Ngày tạo: <?= !empty($shipper['created_at']) ? e(date('d/m/Y', strtotime($shipper['created_at']))) : '-' ?></div>
                    <button class="btn btn-primary w-100" type="submit">Lưu hồ sơ</button>
                </form>
            </section>

            <section class="driver-section-card mb-4"><div class="driver-section-title"><h2>Mini map</h2><span class="badge badge-success">Gần nhất</span></div><div class="driver-map mb-3"></div><div class="small text-muted">Bản đồ minh họa vị trí nhà hàng và khách hàng gần nhất.</div></section>
            <section class="driver-section-card mb-4" id="performance"><div class="driver-section-title"><h2>Hiệu suất cá nhân</h2></div><div class="mb-3"><div class="d-flex justify-content-between"><span>Mục tiêu hôm nay</span><strong><?= $targetDone ?>%</strong></div><div class="goal-progress"><span style="width:<?= $targetDone ?>%"></span></div></div><div class="mb-3"><div class="d-flex justify-content-between"><span>Tỷ lệ hoàn thành</span><strong><?= $completionRate ?>%</strong></div><div class="goal-progress"><span style="width:<?= $completionRate ?>%"></span></div></div><div class="p-3 bg-light rounded-4 mb-2"><strong>Đơn đã nhận trong tháng:</strong> <?= $monthOrders ?> đơn</div><div class="p-3 bg-light rounded-4 mb-2"><strong>Đơn delay:</strong> <?= $delayedOrders ?> đơn</div><div class="p-3 bg-light rounded-4 mb-2"><strong>Không hoàn thành:</strong> <?= $cancelledOrders ?> đơn</div><div class="p-3 bg-light rounded-4"><strong>Thời gian giao TB:</strong> <?= $avgDeliveryMinutes > 0 ? $avgDeliveryMinutes . ' phút' : 'Chưa có dữ liệu' ?></div></section>
            <section class="driver-section-card mb-4"><div class="driver-section-title"><h2>Ví & COD</h2></div><div class="driver-stat-value mb-1"><?= shipperMoney($walletBalance) ?></div><div class="text-muted small mb-3">Số dư từ các đơn đã nhận</div><ul class="recent-activity"><li><span>Thu nhập hôm nay: <?= shipperMoney($todayIncome) ?></span></li><li><span>Đã thu COD: <?= shipperMoney($codCollectedTotal) ?></span></li><li><span>COD chờ thu: <?= shipperMoney($codPendingTotal) ?></span></li></ul></section>
            <section class="driver-section-card mb-4"><div class="driver-section-title"><h2>Đơn giá trị cao</h2></div><ul class="recent-activity"><?php foreach ($topOrders as $order): ?><li><span><?= e($order['order_code']) ?> - <?= e($order['customer_name']) ?></span><small><?= shipperMoney($order['total']) ?></small></li><?php endforeach; ?><?php if (!$topOrders): ?><li><span>Chưa có đơn đã nhận.</span></li><?php endif; ?></ul></section>
            <section class="driver-section-card mb-4"><div class="driver-section-title"><h2>Hoạt động gần đây</h2></div><ul class="recent-activity"><?php foreach ($recentActions as $action): ?><li><span><?= e(shipperActionLabel($action['action'])) ?><?= $action['order_code'] ? ' ' . e($action['order_code']) : '' ?></span><?php if (!empty($action['reason'])): ?><small><?= e($action['reason']) ?></small><?php endif; ?><?php if (!empty($action['note'])): ?><small><?= e($action['note']) ?></small><?php endif; ?></li><?php endforeach; ?><?php if (!$recentActions): ?><li><span>Chưa có hoạt động.</span></li><?php endif; ?></ul></section>
        </aside>
    </div>
    <?php endif; ?>
</main>

<nav class="driver-bottom-nav">
    <a href="#orders"><i class="bi bi-box-seam"></i><span>Đơn</span></a>
    <a href="#my-orders"><i class="bi bi-truck"></i><span>Của tôi</span></a>
    <a href="#income"><i class="bi bi-bar-chart"></i><span>Thu nhập</span></a>
    <a href="#profile"><i class="bi bi-person"></i><span>Hồ sơ</span></a>
</nav>

<div class="modal fade driver-issue-modal" id="driverIssueModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
                            <?= csrfField() ?>
            <div class="modal-header">
                <div>
                    <div class="issue-modal-icon"><i class="bi bi-exclamation-triangle"></i></div>
                    <h5 class="modal-title mb-1">Báo sự cố đơn hàng</h5>
                    <div class="text-muted small" id="driverIssueOrderCode">Chọn lý do để admin xử lý nhanh hơn</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="report_issue">
                <input type="hidden" name="order_id" id="driverIssueOrderId">
                <label class="form-label fw-semibold">Lý do sự cố</label>
                <select class="form-select mb-3" name="issue_reason" required>
                    <option value="">Chọn lý do</option>
                    <option value="Khách không nghe máy">Khách không nghe máy</option>
                    <option value="Sai địa chỉ">Sai địa chỉ</option>
                    <option value="Khách hủy khi giao">Khách hủy khi giao</option>
                    <option value="Sự cố xe">Sự cố xe</option>
                    <option value="Thời tiết xấu">Thời tiết xấu</option>
                </select>
                <label class="form-label fw-semibold">Ghi chú thêm</label>
                <textarea class="form-control" name="issue_note" rows="4" placeholder="Ví dụ: Khách không nghe máy sau 3 cuộc gọi, đã đứng chờ 10 phút tại địa chỉ giao."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-warning"><i class="bi bi-send"></i> Lưu sự cố</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade driver-action-modal" id="driverCodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
                            <?= csrfField() ?>
            <div class="modal-header">
                <div>
                    <div class="action-modal-icon success"><i class="bi bi-cash-coin"></i></div>
                    <h5 class="modal-title mb-1">Xác nhận thu COD</h5>
                    <div class="text-muted small" id="driverCodOrderCode">Kiểm tra số tiền trước khi xác nhận</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="collect_cod">
                <input type="hidden" name="order_id" id="driverCodOrderId">
                <div class="cod-confirm-box">
                    <span>Số tiền COD cần thu</span>
                    <strong id="driverCodOrderTotal">0đ</strong>
                </div>
                <p class="text-muted small mb-0">Chỉ xác nhận khi khách đã thanh toán đủ số tiền COD.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Đã thu tiền</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade driver-action-modal" id="driverCompleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
                            <?= csrfField() ?>
            <div class="modal-header">
                <div>
                    <div class="action-modal-icon"><i class="bi bi-check2-circle"></i></div>
                    <h5 class="modal-title mb-1">Xác nhận hoàn thành</h5>
                    <div class="text-muted small" id="driverCompleteOrderCode">Đơn sẽ được tính vào thu nhập sau khi xác nhận đã nhận</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="order_id" id="driverCompleteOrderId">
                <div class="cod-confirm-box mb-3">
                    <span>Giá trị đơn</span>
                    <strong id="driverCompleteOrderTotal">0đ</strong>
                </div>
                <label class="form-label fw-semibold">Ghi chú giao hàng</label>
                <textarea class="form-control" name="delivery_note" rows="4" placeholder="Ví dụ: Khách đã nhận hàng, thanh toán đủ COD."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Xác nhận đã nhận</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade driver-cancel-modal" id="driverCancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
                            <?= csrfField() ?>
            <div class="modal-header">
                <div>
                    <div class="cancel-modal-icon"><i class="bi bi-x-octagon"></i></div>
                    <h5 class="modal-title mb-1">Hủy đơn đang giao</h5>
                    <div class="text-muted small" id="driverCancelOrderCode">Đơn hủy sẽ tính là không hoàn thành trong KPI</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="cancel_order">
                <input type="hidden" name="order_id" id="driverCancelOrderId">
                <label class="form-label fw-semibold">Lý do hủy</label>
                <select class="form-select mb-3" name="cancel_reason" required>
                    <option value="">Chọn lý do</option>
                    <option value="Khách hủy đơn khi giao">Khách hủy đơn khi giao</option>
                    <option value="Không liên hệ được khách">Không liên hệ được khách</option>
                    <option value="Sai địa chỉ không thể giao">Sai địa chỉ không thể giao</option>
                    <option value="Sự cố phương tiện">Sự cố phương tiện</option>
                    <option value="Không thể tiếp tục giao">Không thể tiếp tục giao</option>
                </select>
                <label class="form-label fw-semibold">Ghi chú hủy</label>
                <textarea class="form-control" name="cancel_note" rows="4" placeholder="Nhập chi tiết lý do hủy để admin kiểm tra."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Xác nhận hủy</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= e(BASE_URL . '/assets/js/staff.js') ?>"></script>
<script>
const incomeCanvas = document.getElementById('driverIncomeChart');
if (incomeCanvas) {
    new Chart(incomeCanvas, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                { label: 'Số đơn đã nhận', data: <?= json_encode($orderCounts, JSON_UNESCAPED_UNICODE) ?>, backgroundColor: 'rgba(255,107,0,.78)', borderRadius: 10, maxBarThickness: 42 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
}
document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        if (!button) return;
        button.classList.add('disabled');
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang xử lý';
    });
});
document.querySelectorAll('a[href="#profile"]').forEach((link) => {
    link.setAttribute('href', '<?= e(SHIPPER_DASHBOARD_URL . '?page=profile') ?>');
});
document.querySelectorAll('a[href="#history"]').forEach((link) => {
    link.setAttribute('href', '#delivery-history');
});
const driverIssueModal = document.getElementById('driverIssueModal');
if (driverIssueModal) {
    driverIssueModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        const orderId = button?.getAttribute('data-order-id') || '';
        const orderCode = button?.getAttribute('data-order-code') || '';
        driverIssueModal.querySelector('#driverIssueOrderId').value = orderId;
        driverIssueModal.querySelector('#driverIssueOrderCode').textContent = orderCode ? `Đơn ${orderCode}` : 'Chọn lý do để admin xử lý nhanh hơn';
    });
}
const driverCodModal = document.getElementById('driverCodModal');
if (driverCodModal) {
    driverCodModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        const orderId = button?.getAttribute('data-order-id') || '';
        const orderCode = button?.getAttribute('data-order-code') || '';
        const orderTotal = button?.getAttribute('data-order-total') || '0đ';
        driverCodModal.querySelector('#driverCodOrderId').value = orderId;
        driverCodModal.querySelector('#driverCodOrderCode').textContent = orderCode ? `Đơn ${orderCode}` : 'Kiểm tra số tiền trước khi xác nhận';
        driverCodModal.querySelector('#driverCodOrderTotal').textContent = orderTotal;
    });
}
const driverCompleteModal = document.getElementById('driverCompleteModal');
if (driverCompleteModal) {
    driverCompleteModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        const orderId = button?.getAttribute('data-order-id') || '';
        const orderCode = button?.getAttribute('data-order-code') || '';
        const orderTotal = button?.getAttribute('data-order-total') || '0đ';
        driverCompleteModal.querySelector('#driverCompleteOrderId').value = orderId;
        driverCompleteModal.querySelector('#driverCompleteOrderCode').textContent = orderCode ? `Đơn ${orderCode}` : 'Đơn sẽ được tính vào thu nhập sau khi xác nhận đã nhận';
        driverCompleteModal.querySelector('#driverCompleteOrderTotal').textContent = orderTotal;
    });
}
const driverCancelModal = document.getElementById('driverCancelModal');
if (driverCancelModal) {
    driverCancelModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        const orderId = button?.getAttribute('data-order-id') || '';
        const orderCode = button?.getAttribute('data-order-code') || '';
        driverCancelModal.querySelector('#driverCancelOrderId').value = orderId;
        driverCancelModal.querySelector('#driverCancelOrderCode').textContent = orderCode ? `Đơn ${orderCode} sẽ tính là không hoàn thành trong KPI` : 'Đơn hủy sẽ tính là không hoàn thành trong KPI';
    });
}
</script>
</body>
</html>
