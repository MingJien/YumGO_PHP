<?php
$title = "Lịch sử đơn hàng - YumGO";
require_once 'views/layouts/header.php';

$phone = $phone ?? '';
$status = $status ?? 'all';
$time = $time ?? 'all';
$sort = $sort ?? 'date_desc';
$q = $q ?? '';
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;

$statusLabels = [
    'Placed' => 'Đã tiếp nhận',
    'Preparing' => 'Đang chuẩn bị',
    'Ready' => 'Sẵn sàng giao',
    'Delivering' => 'Đang giao hàng',
    'Delivered' => 'Hoàn thành',
    'Cancelled By User' => 'Đã hủy',
    'Cancelled By Admin' => 'Đã hủy',
];

$queryParams = [];
if ($phone !== '') $queryParams['phone'] = $phone;
if ($status !== 'all') $queryParams['status'] = $status;
if ($time !== 'all') $queryParams['time'] = $time;
if ($sort !== 'date_desc') $queryParams['sort'] = $sort;
if ($q !== '') $queryParams['q'] = $q;

function buildHistoryUrl(array $queryParams, int $pageNumber): string {
    $params = $queryParams;
    if ($pageNumber > 1) {
        $params['p'] = $pageNumber;
    } else {
        unset($params['p']);
    }
    return 'index.php?page=order-history&' . http_build_query($params);
}

function buildSortUrl(array $queryParams, string $column): string {
    $params = $queryParams;
    $currentSort = $params['sort'] ?? 'date_desc';
    if ($column === 'date') {
        $params['sort'] = ($currentSort === 'date_desc') ? 'date_asc' : 'date_desc';
    } elseif ($column === 'price') {
        $params['sort'] = ($currentSort === 'price_desc') ? 'price_asc' : 'price_desc';
    }
    $params['p'] = 1;
    return 'index.php?page=order-history&' . http_build_query($params);
}
?>

<style>
.order-history-page {
    font-family: 'Be Vietnam Pro', sans-serif;
    color: var(--yumgo-ink);
}
.stats-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid var(--yumgo-hairline) !important;
}
.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}
.status-tabs {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 8px;
    margin-bottom: 24px;
    border-bottom: 1px solid var(--yumgo-hairline);
    -webkit-overflow-scrolling: touch;
}
.status-tabs::-webkit-scrollbar {
    height: 4px;
}
.status-tabs::-webkit-scrollbar-thumb {
    background: var(--yumgo-hairline);
    border-radius: 4px;
}
.status-tab {
    padding: 12px 20px;
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--yumgo-body);
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    white-space: nowrap;
    text-decoration: none;
    transition: all 0.2s ease;
}
.status-tab.active {
    color: var(--yumgo-primary);
    border-bottom-color: var(--yumgo-primary);
}
.status-tab:hover:not(.active) {
    color: var(--yumgo-primary-hover);
}
.filter-panel {
    background: var(--yumgo-surface-card);
    border: 1px solid var(--yumgo-hairline);
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-card);
}
.desktop-table-card {
    background: var(--yumgo-surface-card);
    border: 1px solid var(--yumgo-hairline);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-card);
}
.table-light-header th {
    background-color: var(--yumgo-canvas-soft) !important;
    font-weight: 600;
    color: var(--yumgo-muted);
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 14px 16px;
    border-bottom: 2px solid var(--yumgo-hairline);
}
.table-row-hover td {
    padding: 16px;
    vertical-align: middle;
    border-bottom: 1px solid var(--yumgo-hairline);
}
.table-row-hover:hover {
    background-color: var(--yumgo-canvas-warm);
}
.food-badge-item {
    background: var(--yumgo-canvas-soft);
    border: 1px solid var(--yumgo-hairline);
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--yumgo-body);
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.food-items-container {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    max-width: 320px;
}
.copy-btn {
    border: none;
    background: transparent;
    padding: 2px 6px;
    color: var(--yumgo-muted);
    border-radius: 4px;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    vertical-align: middle;
}
.copy-btn:hover {
    background: var(--yumgo-primary-soft);
    color: var(--yumgo-primary);
}
body[data-theme="dark"] .stats-card,
body[data-theme="dark"] .filter-panel,
body[data-theme="dark"] .desktop-table-card {
    background: var(--yumgo-surface-dark, #23201d);
}
body[data-theme="dark"] .table-light-header th {
    background-color: rgba(255, 255, 255, 0.05) !important;
}
body[data-theme="dark"] .table-row-hover:hover {
    background-color: rgba(255, 255, 255, 0.02);
}
body[data-theme="dark"] .food-badge-item {
    background: rgba(255, 255, 255, 0.05);
}
</style>

<div class="container py-4 py-md-5 order-page order-history-page" style="min-height: 70vh;">
    <!-- Title Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Lịch sử đặt hàng</h3>
            <p class="text-secondary small mb-0">Quản lý và tra cứu trạng thái đơn hàng của bạn</p>
        </div>
    </div>

    <!-- Quick Stats Dashboard -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 h-100 stats-card" style="border-radius: 12px; background: var(--yumgo-surface-card);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-primary" style="width: 48px; height: 48px; background: var(--yumgo-primary-soft); font-size: 1.5rem;">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary small mb-1">Tổng đơn hàng</h6>
                        <h4 class="fw-bold mb-0 text-dark"><?= $stats['total_orders'] ?> đơn</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 h-100 stats-card" style="border-radius: 12px; background: var(--yumgo-surface-card);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-warning" style="width: 48px; height: 48px; background: rgba(255, 193, 7, 0.15); font-size: 1.5rem;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary small mb-1">Đơn đang xử lý/giao</h6>
                        <h4 class="fw-bold mb-0 text-dark"><?= $stats['inflight_orders'] ?> đơn</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3 h-100 stats-card" style="border-radius: 12px; background: var(--yumgo-surface-card);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-success" style="width: 48px; height: 48px; background: rgba(25, 135, 84, 0.1); font-size: 1.5rem;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary small mb-1">Tổng chi tiêu (Đã giao)</h6>
                        <h4 class="fw-bold mb-0 text-success"><?= number_format($stats['total_spent'], 0, ',', '.') ?> đ</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="status-tabs">
        <a href="<?= buildHistoryUrl(array_merge($queryParams, ['status' => 'all']), 1) ?>" class="status-tab <?= $status === 'all' ? 'active' : '' ?>">Tất cả</a>
        <a href="<?= buildHistoryUrl(array_merge($queryParams, ['status' => 'processing']), 1) ?>" class="status-tab <?= $status === 'processing' ? 'active' : '' ?>">Đang chuẩn bị</a>
        <a href="<?= buildHistoryUrl(array_merge($queryParams, ['status' => 'delivering']), 1) ?>" class="status-tab <?= $status === 'delivering' ? 'active' : '' ?>">Đang giao</a>
        <a href="<?= buildHistoryUrl(array_merge($queryParams, ['status' => 'completed']), 1) ?>" class="status-tab <?= $status === 'completed' ? 'active' : '' ?>">Đã giao</a>
        <a href="<?= buildHistoryUrl(array_merge($queryParams, ['status' => 'cancelled']), 1) ?>" class="status-tab <?= $status === 'cancelled' ? 'active' : '' ?>">Đã hủy</a>
    </div>

    <!-- Advanced Filter & Search Panel -->
    <div class="filter-panel mb-4">
        <form action="index.php" method="GET" class="row g-3 align-items-center">
            <input type="hidden" name="page" value="order-history">
            <?php if ($phone !== ''): ?>
                <input type="hidden" name="phone" value="<?= htmlspecialchars($phone) ?>">
            <?php endif; ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">

            <!-- Search Field -->
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Tìm kiếm theo mã đơn hoặc tên món ăn..." value="<?= htmlspecialchars($q) ?>">
                </div>
            </div>

            <!-- Time Filter -->
            <div class="col-md-3">
                <select name="time" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $time === 'all' ? 'selected' : '' ?>>Tất cả thời gian</option>
                    <option value="30days" <?= $time === '30days' ? 'selected' : '' ?>>30 ngày gần đây</option>
                    <option value="3months" <?= $time === '3months' ? 'selected' : '' ?>>3 tháng gần đây</option>
                    <option value="6months" <?= $time === '6months' ? 'selected' : '' ?>>6 tháng gần đây</option>
                </select>
            </div>

            <!-- Sort Option -->
            <div class="col-md-3">
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Cũ nhất</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá cao nhất</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá thấp nhất</option>
                </select>
            </div>

            <!-- Search & Reset Buttons -->
            <div class="col-md-1 d-flex gap-2">
                <button type="submit" class="btn btn-primary-yumgo w-100 p-2 d-flex align-items-center justify-content-center" style="height: 38px;"><i class="bi bi-search"></i></button>
                <?php if ($q !== '' || $time !== 'all' || $sort !== 'date_desc'): ?>
                    <a href="index.php?page=order-history&status=<?= $status ?><?= $phone !== '' ? '&phone=' . urlencode($phone) : '' ?>" class="btn btn-outline-secondary p-2 d-flex align-items-center justify-content-center" style="height: 38px; width: 38px;" title="Xóa bộ lọc"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Empty State -->
    <?php if (empty($orders)): ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
            <div class="text-center py-5 text-muted order-empty-state p-4 border rounded-3" style="background: var(--yumgo-surface-card);">
                <i class="bi bi-receipt-cutoff text-primary" style="font-size: 3.5rem;"></i>
                <h5 class="mt-3 fw-bold text-dark">Không tìm thấy đơn hàng nào</h5>
                <p class="text-secondary small max-w-md mx-auto">Thử thay đổi bộ lọc tìm kiếm hoặc khám phá thực đơn đa dạng của YumGO để đặt những món ăn đầu tiên nhé!</p>
                <a href="index.php?page=foods" class="btn btn-primary-yumgo mt-2 px-4 py-2">Khám phá thực đơn</a>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted order-empty-state p-4 border rounded-3" style="background: var(--yumgo-surface-card);">
                <i class="bi bi-shield-lock text-primary" style="font-size: 3.5rem;"></i>
                <h5 class="mt-3 fw-bold text-dark">Đăng nhập để xem lịch sử</h5>
                <p class="text-secondary small max-w-md mx-auto">Đăng nhập tài khoản YumGO để lưu và quản lý tất cả đơn hàng đã đặt.</p>
                <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
                    <a href="index.php?page=login&redirect=order-history" class="btn btn-primary-yumgo px-4 py-2">Đăng nhập ngay</a>
                    <a href="index.php?page=register" class="btn btn-outline-primary px-4 py-2">Đăng ký tài khoản</a>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Desktop Layout: Sortable Table -->
        <div class="desktop-table-card d-none d-md-block mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light-header">
                        <tr>
                            <th class="ps-4">Mã đơn hàng</th>
                            <th>
                                <a href="<?= buildSortUrl($queryParams, 'date') ?>" class="text-decoration-none text-muted d-inline-flex align-items-center gap-1">
                                    Ngày đặt
                                    <?php if ($sort === 'date_desc'): ?>
                                        <i class="bi bi-arrow-down text-primary"></i>
                                    <?php elseif ($sort === 'date_asc'): ?>
                                        <i class="bi bi-arrow-up text-primary"></i>
                                    <?php else: ?>
                                        <i class="bi bi-arrow-down-up small"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Khách hàng</th>
                            <th>Sản phẩm</th>
                            <th>
                                <a href="<?= buildSortUrl($queryParams, 'price') ?>" class="text-decoration-none text-muted d-inline-flex align-items-center gap-1">
                                    Tổng tiền
                                    <?php if ($sort === 'price_desc'): ?>
                                        <i class="bi bi-arrow-down text-primary"></i>
                                    <?php elseif ($sort === 'price_asc'): ?>
                                        <i class="bi bi-arrow-up text-primary"></i>
                                    <?php else: ?>
                                        <i class="bi bi-arrow-down-up small"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Trạng thái</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr class="table-row-hover">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <strong class="font-monospace text-dark"><?= htmlspecialchars($o['order_code']) ?></strong>
                                        <button class="copy-btn btn-sm" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($o['order_code']) ?>'); showToast('Đã sao chép mã đơn!', 'success')">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                                <td>
                                    <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($o['customer_name']) ?></div>
                                    <div class="small text-secondary"><?= htmlspecialchars($o['phone']) ?></div>
                                </td>
                                <td>
                                    <div class="food-items-container">
                                        <?php foreach ($o['items'] as $item): ?>
                                            <span class="food-badge-item" title="<?= htmlspecialchars($item['food_name']) ?>">
                                                <?= $item['quantity'] ?>x <?= htmlspecialchars(strlen($item['food_name']) > 16 ? mb_substr($item['food_name'], 0, 14, 'UTF-8') . '..' : $item['food_name']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="text-danger fw-bold"><?= number_format($o['total'], 0, ',', '.') ?> đ</td>
                                <td>
                                    <?php
                                    $badgeClass = 'bg-secondary';
                                    if ($o['status'] === 'Placed') $badgeClass = 'bg-info text-dark';
                                    if ($o['status'] === 'Preparing') $badgeClass = 'bg-primary text-white';
                                    if ($o['status'] === 'Ready') $badgeClass = 'bg-warning text-dark';
                                    if ($o['status'] === 'Delivering') $badgeClass = 'bg-info text-dark';
                                    if ($o['status'] === 'Delivered') $badgeClass = 'bg-success text-white';
                                    if (strpos($o['status'], 'Cancelled') !== false) $badgeClass = 'bg-danger text-white';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>" style="font-size: 0.8rem; padding: 6px 12px; border-radius: 100px;">
                                        <?= htmlspecialchars($statusLabels[$o['status']] ?? 'Đã hủy') ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="index.php?page=order-tracking&code=<?= htmlspecialchars($o['order_code']) ?>&phone=<?= htmlspecialchars($o['phone']) ?>" class="btn btn-sm btn-outline-primary" style="border-radius: 100px; padding: 6px 16px;">Chi tiết</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Layout: Ticket-Style Cards -->
        <div class="order-history-cards d-md-none">
            <?php foreach ($orders as $o): ?>
                <?php
                $badgeClass = 'status-placed';
                if ($o['status'] === 'Placed') $badgeClass = 'status-placed';
                if ($o['status'] === 'Preparing') $badgeClass = 'status-preparing';
                if ($o['status'] === 'Ready') $badgeClass = 'status-ready';
                if ($o['status'] === 'Delivering') $badgeClass = 'status-delivering';
                if ($o['status'] === 'Delivered') $badgeClass = 'status-delivered';
                if (strpos($o['status'], 'Cancelled') !== false) $badgeClass = 'status-cancelled';

                $shortCode = strlen($o['order_code']) > 15 
                    ? substr($o['order_code'], 0, 6) . '...' . substr($o['order_code'], -4) 
                    : $o['order_code'];
                ?>
                <div class="order-mobile-card">
                    <!-- Card Header -->
                    <div class="order-mobile-card-header">
                        <div class="order-code-group">
                            <i class="bi bi-receipt text-primary"></i>
                            <span class="order-code"><?= htmlspecialchars($shortCode) ?></span>
                            <button class="copy-btn btn-sm p-0" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($o['order_code']) ?>'); showToast('Đã sao chép mã đơn!', 'success')">
                                <i class="bi bi-clipboard" style="font-size: 0.75rem;"></i>
                            </button>
                        </div>
                        <span class="status-badge <?= $badgeClass ?>">
                            <?= htmlspecialchars($statusLabels[$o['status']] ?? 'Đã hủy') ?>
                        </span>
                    </div>

                    <!-- Ticket Tear Line -->
                    <div class="ticket-divider"></div>

                    <!-- Card Body -->
                    <div class="order-mobile-card-body">
                        <div class="info-row">
                            <span class="info-label">Ngày đặt</span>
                            <span class="info-value"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Sản phẩm</span>
                            <span class="info-value text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars(implode(', ', array_map(fn($item) => $item['quantity'] . 'x ' . $item['food_name'], $o['items']))) ?>">
                                <?= htmlspecialchars(implode(', ', array_map(fn($item) => $item['quantity'] . 'x ' . $item['food_name'], $o['items']))) ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Thanh toán</span>
                            <span class="info-value"><?= $o['payment_method'] === 'Banking' ? 'Chuyển khoản' : 'Tiền mặt (COD)' ?></span>
                        </div>
                        <div class="info-row total-row">
                            <span class="info-label fw-bold">Tổng tiền</span>
                            <span class="info-value order-total"><?= number_format($o['total'], 0, ',', '.') ?> đ</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="order-mobile-card-footer mt-2 d-flex gap-2">
                        <a href="index.php?page=order-tracking&code=<?= htmlspecialchars($o['order_code']) ?>&phone=<?= htmlspecialchars($o['phone']) ?>" class="btn btn-outline-primary btn-view-detail flex-fill">
                            Xem chi tiết <i class="bi bi-chevron-right"></i>
                        </a>
                        <?php if (in_array($o['status'], ['Placed', 'Preparing'])): ?>
                            <form action="index.php?page=order-cancel" method="POST" class="flex-fill" onsubmit="event.preventDefault(); Swal.fire({title: 'Hủy đơn hàng?', text: 'Bạn có chắc chắn muốn hủy đơn hàng <?= htmlspecialchars($o['order_code']) ?>?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d', confirmButtonText: 'Đồng ý hủy', cancelButtonText: 'Không'}).then((result) => { if (result.isConfirmed) { this.submit(); } })">
                                <input type="hidden" name="code" value="<?= htmlspecialchars($o['order_code']) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-view-detail w-100">
                                    Hủy đơn
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav class="d-flex justify-content-center mt-4" aria-label="Phân trang đơn hàng">
            <!-- Desktop Pagination -->
            <ul class="pagination d-none d-md-flex mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= buildHistoryUrl($queryParams, $page - 1) ?>" aria-label="Trang trước" style="border-radius: 50% 0 0 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php
                // Render standard slider
                for ($i = 1; $i <= $totalPages; $i++):
                    if ($i === 1 || $i === $totalPages || abs($i - $page) <= 2):
                ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildHistoryUrl($queryParams, $i) ?>" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: 600; margin: 0 2px; border-radius: 8px;"><?= $i ?></a>
                        </li>
                <?php
                    elseif (abs($i - $page) === 3):
                ?>
                        <li class="page-item disabled"><span class="page-link" style="border: none; background: transparent;">...</span></li>
                <?php
                    endif;
                endfor;
                ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= buildHistoryUrl($queryParams, $page + 1) ?>" aria-label="Trang sau" style="border-radius: 0 50% 50% 0; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>

            <!-- Mobile Pagination -->
            <div class="d-flex d-md-none align-items-center gap-3">
                <a href="<?= buildHistoryUrl($queryParams, $page - 1) ?>" class="btn btn-sm btn-outline-primary <?= $page <= 1 ? 'disabled' : '' ?>" style="border-radius: 100px; padding: 6px 16px;">
                    <i class="bi bi-chevron-left"></i> Trước
                </a>
                <span class="small fw-bold text-secondary">Trang <?= $page ?> / <?= $totalPages ?></span>
                <a href="<?= buildHistoryUrl($queryParams, $page + 1) ?>" class="btn btn-sm btn-outline-primary <?= $page >= $totalPages ? 'disabled' : '' ?>" style="border-radius: 100px; padding: 6px 16px;">
                    Sau <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </nav>
    <?php endif; ?>
</div>

<?php require_once 'views/layouts/footer.php'; ?>
