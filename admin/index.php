<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../app/models/Voucher.php';
require_once __DIR__ . '/../app/models/Order.php';
require_once __DIR__ . '/../app/services/DeliveryService.php';

requireAdminLogin();
startSession();

$pdo = Database::getConnection();
ensureActivitiesTable($pdo);
pruneActivities($pdo, 3);
DeliveryService::ensureSchema($pdo);
ensureAdminTableColumn($pdo, 'shippers', 'description', 'TEXT NULL');
ensureAdminTableColumn($pdo, 'orders', 'issue_seen_at', 'DATETIME NULL');
ensureAdminTableColumn($pdo, 'orders', 'order_seen_at', 'DATETIME NULL');
ensureAdminTableColumn($pdo, 'orders', 'cancel_reason', 'VARCHAR(255) NULL');

$page = $_GET['page'] ?? 'dashboard';
$allowedPages = ['dashboard', 'categories', 'foods', 'menu', 'vouchers', 'orders', 'shippers', 'profile'];
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

if ($page === 'orders' && isset($_GET['view'], $_GET['mark_issue'])) {
    $issueOrderId = max(0, (int)$_GET['view']);
    if ($issueOrderId > 0) {
        $issueStmt = $pdo->prepare(
            'SELECT o.id, o.order_code, o.issue_reason, o.issue_reported_at, o.issue_seen_at, s.name AS shipper_name
             FROM orders o
             LEFT JOIN shippers s ON s.id = o.shipper_id
             WHERE o.id = :id AND o.issue_reported_at IS NOT NULL
             LIMIT 1'
        );
        $issueStmt->execute(['id' => $issueOrderId]);
        $issueOrder = $issueStmt->fetch();

        $needsIssueSeenUpdate = $issueOrder
            && (
                empty($issueOrder['issue_seen_at'])
                || strtotime((string)$issueOrder['issue_seen_at']) < strtotime((string)($issueOrder['issue_reported_at'] ?? ''))
            );

        if ($needsIssueSeenUpdate) {
            $seenStmt = $pdo->prepare('UPDATE orders SET issue_seen_at = NOW() WHERE id = :id');
            $seenStmt->execute(['id' => $issueOrderId]);
            logActivity(
                $pdo,
                'issue',
                'Đã xem sự cố đơn ' . $issueOrder['order_code'] . ' từ ' . ($issueOrder['shipper_name'] ?? 'shipper'),
                ['id' => $issueOrderId, 'reason' => $issueOrder['issue_reason'] ?? null]
            );
        }
    }
}

if ($page === 'orders' && isset($_GET['view'], $_GET['mark_order'])) {
    $newOrderId = max(0, (int)$_GET['view']);
    if ($newOrderId > 0) {
        $orderStmt = $pdo->prepare(
            'SELECT id, order_code, order_seen_at
             FROM orders
             WHERE id = :id
             LIMIT 1'
        );
        $orderStmt->execute(['id' => $newOrderId]);
        $newOrder = $orderStmt->fetch();

        if ($newOrder && empty($newOrder['order_seen_at'])) {
            $seenStmt = $pdo->prepare('UPDATE orders SET order_seen_at = NOW() WHERE id = :id');
            $seenStmt->execute(['id' => $newOrderId]);
            logActivity($pdo, 'order', 'Đã xem đơn mới ' . $newOrder['order_code'], ['id' => $newOrderId]);
        }
    }
}

function adminUrl(string $page, array $params = []): string
{
    return ADMIN_DASHBOARD_URL . '?' . http_build_query(array_merge(['page' => $page], $params));
}

function moneyFormat(float|string|null $value): string
{
    return number_format((float)$value, 0, ',', '.') . 'đ';
}

function postBool(string $key): int
{
    return isset($_POST[$key]) ? 1 : 0;
}

function requirePositiveInt(string $key): int
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
    return $value !== false && $value !== null && $value > 0 ? (int)$value : 0;
}

function ensureAdminTableColumn(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name"
    );
    $stmt->execute(['table_name' => $table, 'column_name' => $column]);

    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}

function growthPercent(float $today, float $yesterday): array
{
    if ($yesterday <= 0) {
        return [$today > 0 ? 100 : 0, $today >= $yesterday];
    }

    $percent = (($today - $yesterday) / $yesterday) * 100;
    return [round(abs($percent), 1), $percent >= 0];
}

function adminRangeUrl(string $rangeKey): string
{
    $query = $_GET;
    $query['page'] = 'dashboard';
    $query['revenue_range'] = $rangeKey;

    return ADMIN_DASHBOARD_URL . '?' . http_build_query($query);
}

function adminRangeStartDate(array $range): string
{
    if (isset($range['days'])) {
        return date('Y-m-d', strtotime('-' . (int)$range['days'] . ' days'));
    }

    if (isset($range['months'])) {
        return date('Y-m-d', strtotime('-' . (int)$range['months'] . ' months'));
    }

    return date('Y-m-d', strtotime('-1 year'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        requireValidCsrf();

        if ($action === 'save_category') {
            $id = requirePositiveInt('id');
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                throw new RuntimeException('Tên danh mục không được trống.');
            }

            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE categories SET name = :name WHERE id = :id');
                $stmt->execute(['name' => $name, 'id' => $id]);
                logActivity($pdo, 'category', 'Cập nhật danh mục ' . $name, ['id' => $id]);
                setFlash('success', 'Đã cập nhật danh mục.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (:name)');
                $stmt->execute(['name' => $name]);
                logActivity($pdo, 'category', 'Tạo danh mục ' . $name, ['id' => (int)$pdo->lastInsertId()]);
                setFlash('success', 'Đã thêm danh mục.');
            }
            redirect(adminUrl('categories'));
        }

        if ($action === 'toggle_category_delete') {
            $id = requirePositiveInt('id');
            $isDeleted = (int)($_POST['is_deleted'] ?? 0);
            $stmt = $pdo->prepare('UPDATE categories SET is_deleted = :is_deleted WHERE id = :id');
            $stmt->execute(['is_deleted' => $isDeleted, 'id' => $id]);
            setFlash('success', $isDeleted ? 'Đã ẩn danh mục.' : 'Đã khôi phục danh mục.');
            redirect(adminUrl('categories'));
        }

        if ($action === 'delete_category') {
            $id = requirePositiveInt('id');
            $stmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM order_items oi
                 JOIN foods f ON f.id = oi.food_id
                 WHERE f.category_id = :id'
            );
            $stmt->execute(['id' => $id]);
            $historyCount = (int)$stmt->fetchColumn();

            $pdo->beginTransaction();
            if ($historyCount > 0) {
                $stmt = $pdo->prepare('UPDATE categories SET is_deleted = 1 WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $stmt = $pdo->prepare('UPDATE foods SET is_deleted = 1, is_available = 0 WHERE category_id = :id');
                $stmt->execute(['id' => $id]);
                setFlash('success', 'Danh mục có món trong lịch sử đơn nên đã ẩn danh mục và ẩn toàn bộ món thuộc danh mục này.');
            } else {
                $stmt = $pdo->prepare('DELETE FROM foods WHERE category_id = :id');
                $stmt->execute(['id' => $id]);
                $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
                $stmt->execute(['id' => $id]);
                setFlash('success', 'Đã xóa danh mục và toàn bộ món thuộc danh mục này.');
            }
            $pdo->commit();

            redirect(adminUrl('categories'));
        }

        if ($action === 'save_food') {
            $id = requirePositiveInt('id');
            $categoryId = requirePositiveInt('category_id');
            $name = trim($_POST['name'] ?? '');
            $priceRaw = preg_replace('/[^\d]/', '', (string)($_POST['price'] ?? ''));
            $price = $priceRaw !== '' ? (float)$priceRaw : false;
            $description = trim($_POST['description'] ?? '');
            $discountPercent = filter_input(INPUT_POST, 'discount_percent', FILTER_VALIDATE_INT);
            $currentImage = trim($_POST['current_image'] ?? '');

            if ($categoryId <= 0 || $name === '' || $price === false || $price < 0) {
                throw new RuntimeException('Dữ liệu món ăn không hợp lệ.');
            }

            $image = $currentImage;
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploaded = uploadImage($_FILES['image'], UPLOAD_FOOD_PATH, ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
                if ($uploaded === null) {
                    throw new RuntimeException('Tải ảnh thất bại. Chỉ chấp nhận jpg, png, webp và tối đa 2MB.');
                }
                $image = $uploaded;
            }
            if ($image === '') {
                throw new RuntimeException('Món ăn cần có hình ảnh.');
            }

            $data = [
                'category_id' => $categoryId,
                'name' => $name,
                'price' => $price,
                'image' => $image,
                'description' => $description,
                'is_hot' => postBool('is_hot'),
                'is_sale' => postBool('is_sale'),
                'discount_percent' => max(0, min(100, (int)$discountPercent)),
                'is_available' => postBool('is_available'),
            ];

            if ($id > 0) {
                $data['id'] = $id;
                $stmt = $pdo->prepare(
                    'UPDATE foods SET category_id = :category_id, name = :name, price = :price, image = :image,
                     description = :description, is_hot = :is_hot, is_sale = :is_sale,
                     discount_percent = :discount_percent, is_available = :is_available WHERE id = :id'
                );
                $stmt->execute($data);
                logActivity($pdo, 'food', 'Cập nhật món ' . $name, ['id' => $id]);
                setFlash('success', 'Đã cập nhật món ăn.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO foods (category_id, name, price, image, description, is_hot, is_sale, discount_percent, is_available)
                     VALUES (:category_id, :name, :price, :image, :description, :is_hot, :is_sale, :discount_percent, :is_available)'
                );
                $stmt->execute($data);
                logActivity($pdo, 'food', 'Tạo món ' . $name, ['id' => (int)$pdo->lastInsertId()]);
                setFlash('success', 'Đã thêm món ăn.');
            }
            redirect(adminUrl('foods'));
        }

        if ($action === 'toggle_food_delete') {
            $id = requirePositiveInt('id');
            $isDeleted = (int)($_POST['is_deleted'] ?? 0);
            $stmt = $pdo->prepare('UPDATE foods SET is_deleted = :is_deleted WHERE id = :id');
            $stmt->execute(['is_deleted' => $isDeleted, 'id' => $id]);
            setFlash('success', $isDeleted ? 'Đã ẩn món ăn.' : 'Đã khôi phục món ăn.');
            redirect(adminUrl('foods'));
        }

        if ($action === 'delete_food') {
            $id = requirePositiveInt('id');
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE food_id = :id');
            $stmt->execute(['id' => $id]);
            $orderItemCount = (int)$stmt->fetchColumn();

            if ($orderItemCount > 0) {
                $stmt = $pdo->prepare('UPDATE foods SET is_deleted = 1, is_available = 0 WHERE id = :id');
                $stmt->execute(['id' => $id]);
                setFlash('success', 'Món đã có trong đơn hàng nên hệ thống đã ẩn món và tắt bán để giữ lịch sử đơn.');
            } else {
                $stmt = $pdo->prepare('DELETE FROM foods WHERE id = :id');
                $stmt->execute(['id' => $id]);
                setFlash('success', 'Đã xóa món ăn.');
            }

            redirect(adminUrl('foods'));
        }

        if ($action === 'save_voucher') {
            $id = requirePositiveInt('id');
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $type = $_POST['type'] ?? '';
            $value = filter_input(INPUT_POST, 'value', FILTER_VALIDATE_FLOAT);
            $minOrder = filter_input(INPUT_POST, 'min_order', FILTER_VALIDATE_FLOAT);
            $expiredAt = trim($_POST['expired_at'] ?? '');

            if ($code === '' || !in_array($type, ['percent', 'fixed'], true) || $value === false || $minOrder === false || $expiredAt === '') {
                throw new RuntimeException('Dữ liệu voucher không hợp lệ.');
            }
            if ($type === 'percent' && ($value < 10 || $value > 100)) {
                throw new RuntimeException('Voucher phần trăm phải từ 10% đến 100%.');
            }
            if ($type === 'fixed' && $value <= 0) {
                throw new RuntimeException('Voucher cố định phải có giá trị lớn hơn 0đ.');
            }
            if ($minOrder < 0) {
                throw new RuntimeException('Đơn tối thiểu không hợp lệ.');
            }

            $data = [
                'code' => $code,
                'type' => $type,
                'value' => $value,
                'min_order' => $minOrder,
                'expired_at' => str_replace('T', ' ', $expiredAt) . ':00',
                'is_active' => postBool('is_active'),
            ];

            if ($id > 0) {
                $data['id'] = $id;
                $stmt = $pdo->prepare('UPDATE vouchers SET code = :code, type = :type, value = :value, min_order = :min_order, expired_at = :expired_at, is_active = :is_active WHERE id = :id');
                $stmt->execute($data);
                logActivity($pdo, 'voucher', 'Cập nhật voucher ' . $code, ['id' => $id]);
                setFlash('success', 'Đã cập nhật voucher.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO vouchers (code, type, value, min_order, expired_at, is_active) VALUES (:code, :type, :value, :min_order, :expired_at, :is_active)');
                $stmt->execute($data);
                logActivity($pdo, 'voucher', 'Tạo voucher ' . $code, ['id' => (int)$pdo->lastInsertId()]);
                setFlash('success', 'Đã tạo voucher.');
            }
            redirect(adminUrl('vouchers'));
        }

        if ($action === 'disable_voucher') {
            $id = requirePositiveInt('id');
            $stmt = $pdo->prepare('UPDATE vouchers SET is_active = 0 WHERE id = :id');
            $stmt->execute(['id' => $id]);
            setFlash('success', 'Đã tắt voucher.');
            redirect(adminUrl('vouchers'));
        }

        if ($action === 'delete_voucher') {
            $id = requirePositiveInt('id');
            $stmt = $pdo->prepare('DELETE FROM vouchers WHERE id = :id');
            $stmt->execute(['id' => $id]);
            setFlash('success', 'Đã xóa voucher.');
            redirect(adminUrl('vouchers'));
        }

        if ($action === 'update_order_status') {
            $id = requirePositiveInt('id');
            $nextStatus = $_POST['status'] ?? '';
            $order = StaffOrder::find($pdo, $id);
            $isPickupReceived = $order
                && ($order['delivery_type'] ?? 'delivery') === 'pickup'
                && $order['status'] === 'Ready'
                && $nextStatus === 'Delivered';
            if (!$order || (!$isPickupReceived && !canAdminTransitionOrderStatus($order['status'], $nextStatus))) {
                throw new RuntimeException('Chuyển trạng thái đơn hàng không hợp lệ.');
            }

            $stmt = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
            $stmt->execute(['status' => $nextStatus, 'id' => $id]);
            logActivity($pdo, 'order', 'Cập nhật đơn ' . $order['order_code'] . ': ' . orderStatusLabel($nextStatus), ['id' => $id]);
            setFlash('success', 'Đã cập nhật trạng thái đơn hàng.');
            redirect(adminUrl('orders', ['view' => $id]));
        }

        if ($action === 'cancel_order') {
            $id = requirePositiveInt('id');
            $reason = trim((string)($_POST['cancel_reason'] ?? ''));
            if ($reason === '') {
                throw new RuntimeException('Vui lòng nhập lý do hủy đơn để khách hàng biết.');
            }
            $order = StaffOrder::find($pdo, $id);
            if (!$order || !canAdminTransitionOrderStatus($order['status'], 'Cancelled By Admin')) {
                throw new RuntimeException('Không thể hủy đơn hàng này.');
            }

            $stmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled By Admin', cancel_reason = :reason WHERE id = :id");
            $stmt->execute(['id' => $id, 'reason' => $reason]);
            logActivity($pdo, 'order', 'Admin hủy đơn ' . $order['order_code'] . ': ' . $reason, ['id' => $id, 'reason' => $reason]);
            setFlash('success', 'Đã hủy đơn hàng.');
            redirect(adminUrl('orders', ['view' => $id]));
        }

        if ($action === 'refresh_delivery_distance') {
            $id = requirePositiveInt('id');
            $quote = DeliveryService::refreshOrderDelivery($pdo, $id);
            logActivity($pdo, 'order', 'Kiểm tra khoảng cách Google Maps cho đơn #' . $id . ': ' . DeliveryService::statusLabel($quote['delivery_status']), ['id' => $id]);
            setFlash($quote['delivery_status'] === DeliveryService::STATUS_OK || $quote['delivery_status'] === DeliveryService::STATUS_PICKUP ? 'success' : 'warning', $quote['message']);
            redirect(adminUrl('orders', ['view' => $id]));
        }

        if ($action === 'set_order_pickup') {
            $id = requirePositiveInt('id');
            $order = StaffOrder::find($pdo, $id);
            if (!$order) {
                throw new RuntimeException('Không tìm thấy đơn hàng.');
            }
            if (isFinalOrderStatus((string)$order['status'])) {
                throw new RuntimeException('Không thể đổi hình thức giao hàng cho đơn đã kết thúc.');
            }

            $total = max(0, (float)$order['subtotal'] - (float)$order['discount_amount']);
            $stmt = $pdo->prepare(
                "UPDATE orders
                 SET delivery_type = 'pickup',
                     delivery_status = 'pickup',
                     distance_km = 0,
                     delivery_duration_text = NULL,
                     shipping_fee = 0,
                     total = :total
                 WHERE id = :id"
            );
            $stmt->execute(['total' => $total, 'id' => $id]);
            logActivity($pdo, 'order', 'Chuyển đơn ' . $order['order_code'] . ' sang khách tự lấy', ['id' => $id]);
            setFlash('success', 'Đã chuyển đơn sang hình thức khách đến tự lấy và tính lại phí ship 0đ.');
            redirect(adminUrl('orders', ['view' => $id]));
        }

        if ($action === 'save_shipper') {
            $id = requirePositiveInt('id');
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = (string)($_POST['password'] ?? '');
            $currentAvatar = trim($_POST['current_avatar'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $phoneError = validatePhoneDetailed($phone);
            $passwordError = validatePasswordDetailed($password, $id === 0);
            if ($name === '') {
                throw new RuntimeException('Tên shipper không được để trống.');
            }
            if ($phoneError !== null) {
                throw new RuntimeException($phoneError);
            }
            if ($passwordError !== null) {
                throw new RuntimeException($passwordError);
            }

            $avatar = $currentAvatar;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploaded = uploadImage($_FILES['avatar'], UPLOAD_AVATAR_PATH, ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
                if ($uploaded === null) {
                    throw new RuntimeException('Tải avatar thất bại.');
                }
                $avatar = $uploaded;
            }

            if ($id > 0) {
                $params = [
                    'name' => $name,
                    'phone' => $phone,
                    'avatar' => $avatar !== '' ? $avatar : null,
                    'description' => $description,
                    'is_active' => postBool('is_active'),
                    'id' => $id,
                ];
                $sql = 'UPDATE shippers SET name = :name, phone = :phone, avatar = :avatar, description = :description, is_active = :is_active';
                if ($password !== '') {
                    $sql .= ', password = :password';
                    $params['password'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $sql .= ' WHERE id = :id';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                setFlash('success', 'Đã cập nhật shipper.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO shippers (name, phone, password, avatar, description, is_active) VALUES (:name, :phone, :password, :avatar, :description, :is_active)');
                $stmt->execute([
                    'name' => $name,
                    'phone' => $phone,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'avatar' => $avatar !== '' ? $avatar : null,
                    'description' => $description,
                    'is_active' => postBool('is_active'),
                ]);
                setFlash('success', 'Đã tạo shipper.');
            }
            logActivity($pdo, 'shipper', ($id > 0 ? 'Cập nhật shipper ' : 'Tạo shipper ') . $name, ['id' => $id]);
            redirect(adminUrl('shippers'));
        }

        if ($action === 'delete_shipper') {
            $id = requirePositiveInt('id');
            $stmt = $pdo->prepare(
                'SELECT
                    (SELECT COUNT(*) FROM orders WHERE shipper_id = :id) +
                    (SELECT COUNT(*) FROM shipper_order_actions WHERE shipper_id = :id) AS related_count'
            );
            $stmt->execute(['id' => $id]);
            $relatedCount = (int)$stmt->fetchColumn();

            if ($relatedCount > 0) {
                $stmt = $pdo->prepare('UPDATE shippers SET is_active = 0 WHERE id = :id');
                $stmt->execute(['id' => $id]);
                setFlash('success', 'Shipper đã có lịch sử đơn nên hệ thống đã khóa tài khoản để giữ đúng dữ liệu báo cáo.');
            } else {
                $stmt = $pdo->prepare('DELETE FROM shippers WHERE id = :id');
                $stmt->execute(['id' => $id]);
                setFlash('success', 'Đã xóa shipper chưa phát sinh dữ liệu.');
            }
            redirect(adminUrl('shippers'));
        }

        if ($action === 'save_profile') {
            $adminId = (int)$_SESSION['user']['id'];
            $displayName = trim($_POST['display_name'] ?? '');
            $password = (string)($_POST['password'] ?? '');
            $currentAvatar = trim($_POST['current_avatar'] ?? '');
            if ($displayName === '') {
                throw new RuntimeException('Tên hiển thị không được trống.');
            }
            $passwordError = validatePasswordDetailed($password, false);
            if ($passwordError !== null) {
                throw new RuntimeException($passwordError);
            }

            $avatar = $currentAvatar;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploaded = uploadImage($_FILES['avatar'], UPLOAD_AVATAR_PATH, ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
                if ($uploaded === null) {
                    throw new RuntimeException('Tải avatar thất bại.');
                }
                $avatar = $uploaded;
            }

            $params = ['display_name' => $displayName, 'avatar' => $avatar !== '' ? $avatar : null, 'id' => $adminId];
            $sql = 'UPDATE admins SET display_name = :display_name, avatar = :avatar';
            if ($password !== '') {
                $sql .= ', password = :password';
                $params['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $_SESSION['user']['display_name'] = $displayName;
            $_SESSION['user']['avatar'] = $avatar !== '' ? $avatar : null;
            setFlash('success', 'Đã cập nhật hồ sơ.');
            redirect(adminUrl('profile'));
        }
    } catch (Throwable $exception) {
        setFlash('danger', $exception->getMessage());
        redirect(adminUrl($page));
    }
}

$flash = getFlash();
$editId = isset($_GET['edit']) ? max(0, (int)$_GET['edit']) : 0;
$adminUser = $_SESSION['user'] ?? ['display_name' => 'Admin', 'avatar' => null];
$issueNotifications = $pdo->query(
    "SELECT o.id, o.order_code, o.customer_name, o.issue_reason, o.issue_note, o.issue_reported_at,
            s.name AS shipper_name, s.phone AS shipper_phone
     FROM orders o
     LEFT JOIN shippers s ON s.id = o.shipper_id
     WHERE o.issue_reported_at IS NOT NULL
       AND (o.issue_seen_at IS NULL OR o.issue_seen_at < o.issue_reported_at)
     ORDER BY o.issue_reported_at DESC
     LIMIT 6"
)->fetchAll();
$newOrderNotifications = $pdo->query(
    "SELECT id, order_code, customer_name, phone, total, created_at
     FROM orders
     WHERE order_seen_at IS NULL
       AND status = 'Placed'
     ORDER BY created_at DESC
     LIMIT 6"
)->fetchAll();
$notificationCount = count($issueNotifications) + count($newOrderNotifications);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản trị YumGO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(BASE_URL . '/assets/css/staff.css') ?>" rel="stylesheet">
</head>
<body class="staff-shell">
<div class="admin-layout">
    <aside class="admin-sidebar">
        <a class="brand-link" href="<?= e(adminUrl('dashboard')) ?>"><span class="brand-mark">Y</span><span>YumGO</span></a>
        <nav class="sidebar-menu">
            <?php
            $menu = [
                'dashboard' => ['Tổng quan', 'bi-house'],
                'categories' => ['Danh mục', 'bi-folder2'],
                'foods' => ['Món ăn', 'bi-egg-fried'],
                'menu' => ['Xem menu', 'bi-grid-3x3-gap'],
                'vouchers' => ['Voucher', 'bi-ticket-perforated'],
                'orders' => ['Đơn hàng', 'bi-box-seam'],
                'shippers' => ['Shipper', 'bi-truck'],
                'profile' => ['Hồ sơ', 'bi-person'],
            ];
            foreach ($menu as $key => [$label, $icon]):
            ?>
                <a class="sidebar-item <?= $page === $key ? 'active' : '' ?>" href="<?= e(adminUrl($key)) ?>"><i class="bi <?= e($icon) ?>"></i><span><?= e($label) ?></span></a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <form class="admin-search" method="get" action="<?= e(ADMIN_DASHBOARD_URL) ?>">
                <input type="hidden" name="page" value="orders">
                <i class="bi bi-search"></i>
                <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Tìm đơn hàng, khách hàng, số điện thoại...">
            </form>

            <div class="dropdown">
                <button class="btn btn-light position-relative" data-bs-toggle="dropdown" type="button">
                    <i class="bi bi-bell"></i>
                    <?php if ($notificationCount > 0): ?>
                        <span class="notif-badge"><?= $notificationCount ?></span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><span class="dropdown-header">Đơn mới</span></li>
                    <?php foreach ($newOrderNotifications as $orderNotice): ?>
                        <li>
                            <a class="dropdown-item text-wrap" href="<?= e(adminUrl('orders', ['view' => $orderNotice['id'], 'mark_order' => 1])) ?>">
                                <strong>Đơn mới <?= e($orderNotice['order_code']) ?></strong>
                                <small class="text-muted d-block"><?= e($orderNotice['customer_name']) ?> - <?= e($orderNotice['phone']) ?> - <?= moneyFormat($orderNotice['total']) ?></small>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <?php if (!$newOrderNotifications): ?>
                        <li><span class="dropdown-item text-muted">Chưa có đơn mới.</span></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><span class="dropdown-header">Sự cố từ shipper</span></li>
                    <?php foreach ($issueNotifications as $issue): ?>
                        <li>
                            <a class="dropdown-item text-wrap" href="<?= e(adminUrl('orders', ['view' => $issue['id'], 'mark_issue' => 1])) ?>">
                                <strong>Sự cố từ <?= e($issue['shipper_name'] ?? 'shipper') ?></strong>
                                <small class="text-muted d-block">Đơn <?= e($issue['order_code']) ?> - Khách: <?= e($issue['customer_name']) ?></small>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <?php if (!$issueNotifications): ?>
                        <li><span class="dropdown-item text-muted">Chưa có sự cố mới.</span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="dropdown">
                <button class="admin-profile" data-bs-toggle="dropdown" type="button">
                    <?php if (!empty($adminUser['avatar'])): ?>
                        <img class="admin-avatar" src="<?= e(BASE_URL . '/uploads/avatars/' . $adminUser['avatar']) ?>" alt="">
                    <?php else: ?>
                        <span class="admin-avatar avatar-fallback">A</span>
                    <?php endif; ?>
                    <span><?= e($adminUser['display_name'] ?? 'Admin') ?></span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="<?= e(adminUrl('profile')) ?>">Hồ sơ</a></li>
                    <li><a class="dropdown-item text-danger" href="<?= e(BASE_URL . '/admin/logout.php') ?>">Đăng xuất</a></li>
                </ul>
            </div>
        </header>

        <main class="admin-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>

            <?php if ($page === 'dashboard'): ?>
                <?php
                $todayOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()')->fetchColumn();
                $yesterdayOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)')->fetchColumn();
                $todayRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'Delivered' AND DATE(created_at) = CURDATE()")->fetchColumn();
                $yesterdayRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'Delivered' AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetchColumn();
                $deliveringOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Delivering'")->fetchColumn();
                $newCustomers = (int)$pdo->query('SELECT COUNT(DISTINCT phone) FROM orders WHERE DATE(created_at) = CURDATE()')->fetchColumn();
                $bestSeller = $pdo->query(
                    'SELECT f.name, COALESCE(SUM(oi.quantity),0) AS sold
                     FROM order_items oi JOIN foods f ON f.id = oi.food_id
                     GROUP BY f.id, f.name ORDER BY sold DESC LIMIT 1'
                )->fetch() ?: ['name' => 'Chưa có', 'sold' => 0];
                [$orderGrowth, $orderUp] = growthPercent($todayOrders, $yesterdayOrders);
                [$revenueGrowth, $revenueUp] = growthPercent($todayRevenue, $yesterdayRevenue);

                $revenueRangeOptions = [
                    '7d' => ['label' => '7 ngày', 'days' => 6],
                    '1m' => ['label' => '1 tháng', 'months' => 1],
                    '3m' => ['label' => '3 tháng', 'months' => 3],
                    '6m' => ['label' => '6 tháng', 'months' => 6],
                    '1y' => ['label' => '1 năm', 'years' => 1],
                ];
                $selectedRevenueRange = (string)($_GET['revenue_range'] ?? '7d');
                if (!isset($revenueRangeOptions[$selectedRevenueRange])) {
                    $selectedRevenueRange = '7d';
                }
                $revenueRange = $revenueRangeOptions[$selectedRevenueRange];
                $revenueStartDate = adminRangeStartDate($revenueRange);
                $revenueStartDateTime = $revenueStartDate . ' 00:00:00';
                if ($selectedRevenueRange === '7d') {
                    $revenueStmt = $pdo->prepare(
                        "SELECT DATE(updated_at) AS bucket, COALESCE(SUM(total), 0) AS revenue
                         FROM orders
                         WHERE status = 'Delivered'
                           AND updated_at >= :start_date
                         GROUP BY DATE(updated_at)
                         ORDER BY bucket"
                    );
                } else {
                    $revenueStmt = $pdo->prepare(
                        "SELECT DATE_FORMAT(updated_at, '%Y-%m') AS bucket, COALESCE(SUM(total), 0) AS revenue
                         FROM orders
                         WHERE status = 'Delivered'
                           AND updated_at >= :start_date
                         GROUP BY DATE_FORMAT(updated_at, '%Y-%m')
                         ORDER BY bucket"
                    );
                }
                $revenueStmt->execute(['start_date' => $revenueStartDateTime]);
                $revenueRows = $revenueStmt->fetchAll();
                $revenueMap = [];
                foreach ($revenueRows as $row) {
                    $revenueMap[$row['bucket']] = (float)$row['revenue'];
                }
                $chartLabels = [];
                $chartRevenue = [];
                if ($selectedRevenueRange === '7d') {
                    $cursor = new DateTimeImmutable($revenueStartDate);
                    $endDate = new DateTimeImmutable(date('Y-m-d'));
                    while ($cursor <= $endDate) {
                        $day = $cursor->format('Y-m-d');
                        $chartLabels[] = $cursor->format('d/m');
                        $chartRevenue[] = $revenueMap[$day] ?? 0;
                        $cursor = $cursor->modify('+1 day');
                    }
                } else {
                    $monthCount = (int)($revenueRange['months'] ?? 12);
                    $startMonth = (new DateTimeImmutable('first day of this month'))->modify('-' . ($monthCount - 1) . ' months');
                    for ($i = 0; $i < $monthCount; $i++) {
                        $month = $startMonth->modify("+{$i} months");
                        $bucket = $month->format('Y-m');
                        $chartLabels[] = $month->format('m/Y');
                        $chartRevenue[] = $revenueMap[$bucket] ?? 0;
                    }
                }
                $statusSummary = $pdo->query(
                    "SELECT
                        COUNT(CASE WHEN status IN ('Placed', 'Preparing', 'Ready', 'Delivering') THEN 1 END) AS placed_orders,
                        COUNT(CASE WHEN status IN ('Cancelled By User', 'Cancelled By Admin', 'Cancelled By Shipper') THEN 1 END) AS cancelled_orders,
                        COUNT(CASE WHEN status = 'Delivered' THEN 1 END) AS delivered_orders
                     FROM orders"
                )->fetch() ?: ['placed_orders' => 0, 'cancelled_orders' => 0, 'delivered_orders' => 0];
                $statusLabels = ['Đã đặt', 'Đã hủy', 'Đã nhận'];
                $statusValues = [
                    (int)$statusSummary['placed_orders'],
                    (int)$statusSummary['cancelled_orders'],
                    (int)$statusSummary['delivered_orders'],
                ];
                $recentOrders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 6')->fetchAll();
                $activities = $pdo->query('SELECT * FROM activities ORDER BY created_at DESC LIMIT 6')->fetchAll();
                $moreActivities = $pdo->query('SELECT * FROM activities ORDER BY created_at DESC LIMIT 30 OFFSET 6')->fetchAll();
                ?>
                <div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
                    <div>
                        <h1 class="page-title mb-1">Tổng quan</h1>
                        <div class="text-muted">Theo dõi đơn hàng, doanh thu và vận hành giao nhận.</div>
                    </div>
                    <div class="quick-actions d-none">
                        <a class="btn btn-primary" href="<?= e(adminUrl('foods')) ?>"><i class="bi bi-plus-lg"></i> Thêm món</a>
                        <a class="btn btn-outline-primary" href="<?= e(adminUrl('vouchers')) ?>">Tạo voucher</a>
                        <a class="btn btn-outline-primary" href="<?= e(adminUrl('orders')) ?>">Xem đơn</a>
                        <a class="btn btn-outline-primary" href="<?= e(adminUrl('shippers')) ?>">Thêm shipper</a>
                    </div>
                </div>

                <div class="quick-action-grid mb-4">
                    <a class="quick-action-card" href="<?= e(BASE_URL . '/api/delivery_quote.php') ?>">
                        <span class="quick-action-icon bg-soft-blue"><i class="bi bi-geo-alt"></i></span>
                        <span>Check ship</span>
                    </a>
                    <a class="quick-action-card" href="<?= e(adminUrl('foods')) ?>">
                        <span class="quick-action-icon bg-soft-orange"><i class="bi bi-plus-lg"></i></span>
                        <span>Thêm món</span>
                    </a>
                    <a class="quick-action-card" href="<?= e(adminUrl('vouchers')) ?>">
                        <span class="quick-action-icon bg-soft-purple"><i class="bi bi-ticket-perforated"></i></span>
                        <span>Tạo voucher</span>
                    </a>
                    <a class="quick-action-card" href="<?= e(adminUrl('orders')) ?>">
                        <span class="quick-action-icon bg-soft-blue"><i class="bi bi-receipt"></i></span>
                        <span>Xem đơn</span>
                    </a>
                    <a class="quick-action-card" href="<?= e(adminUrl('shippers')) ?>">
                        <span class="quick-action-icon bg-soft-green"><i class="bi bi-truck"></i></span>
                        <span>Thêm shipper</span>
                    </a>
                </div>

                <div class="kpi-grid mb-4">
                    <div class="kpi-card"><div class="kpi-icon bg-soft-orange"><i class="bi bi-receipt"></i></div><div><div class="kpi-value"><?= $todayOrders ?></div><div class="kpi-label">Tổng đơn hôm nay</div><div class="kpi-trend <?= $orderUp ? 'trend-up' : 'trend-down' ?>"><?= $orderUp ? '↑' : '↓' ?> <?= $orderGrowth ?>% so với hôm qua</div></div></div>
                    <div class="kpi-card"><div class="kpi-icon bg-soft-green"><i class="bi bi-cash-stack"></i></div><div><div class="kpi-value"><?= moneyFormat($todayRevenue) ?></div><div class="kpi-label">Doanh thu hôm nay</div><div class="kpi-trend <?= $revenueUp ? 'trend-up' : 'trend-down' ?>"><?= $revenueUp ? '↑' : '↓' ?> <?= $revenueGrowth ?>% so với hôm qua</div></div></div>
                    <div class="kpi-card"><div class="kpi-icon bg-soft-blue"><i class="bi bi-truck"></i></div><div><div class="kpi-value"><?= $deliveringOrders ?></div><div class="kpi-label">Đơn đang giao</div><div class="kpi-trend trend-up">Live order count</div></div></div>
                    <div class="kpi-card"><div class="kpi-icon bg-soft-purple"><i class="bi bi-people"></i></div><div><div class="kpi-value"><?= $newCustomers ?></div><div class="kpi-label">Khách hàng mới</div><div class="kpi-trend">Theo số điện thoại</div></div></div>
                    <div class="kpi-card"><div class="kpi-icon bg-soft-orange"><i class="bi bi-fire"></i></div><div><div class="kpi-value"><?= (int)$bestSeller['sold'] ?></div><div class="kpi-label"><?= e($bestSeller['name']) ?></div><div class="kpi-trend">Món bán chạy</div></div></div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-xl-8"><div class="card"><div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <h2 class="h5 page-title mb-0">Doanh thu <?= e($revenueRange['label']) ?></h2>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <?= e($revenueRange['label']) ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php foreach ($revenueRangeOptions as $key => $option): ?>
                                        <li><a class="dropdown-item <?= $selectedRevenueRange === $key ? 'active' : '' ?>" href="<?= e(adminRangeUrl($key)) ?>"><?= e($option['label']) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <canvas id="revenueChart" height="115"></canvas>
                    </div></div></div>
                    <div class="col-xl-4"><div class="card"><div class="card-body"><h2 class="h5 page-title">Trạng thái đơn hàng</h2><canvas id="statusChart" height="230"></canvas></div></div></div>
                </div>

                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="card"><div class="card-body table-responsive">
                            <h2 class="h5 page-title">Đơn gần đây</h2>
                            <table class="table table-modern align-middle">
                                <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?= e($order['order_code']) ?></td>
                                        <td><?= e($order['customer_name']) ?><div class="text-muted small"><?= e($order['phone']) ?></div></td>
                                        <td><?= moneyFormat($order['total']) ?></td>
                                        <td><span class="badge <?= e(orderStatusBadgeClass($order['status'])) ?>"><?= e(orderStatusLabel($order['status'])) ?></span></td>
                                        <td><a class="btn btn-sm btn-outline-primary" href="<?= e(adminUrl('orders', ['view' => $order['id']])) ?>">Xem</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div></div>
                    </div>
                    <div class="col-xl-4">
                        <div class="card"><div class="card-body">
                            <h2 class="h5 page-title">Hoạt động gần đây</h2>
                            <ul class="recent-activity">
                                <?php foreach ($activities as $activity): ?>
                                    <li><span>• <?= e($activity['message']) ?></span><small><?= e($activity['created_at']) ?></small></li>
                                <?php endforeach; ?>
                                <?php if (!$activities): ?>
                                    <li class="text-muted">Chưa có hoạt động.</li>
                                <?php endif; ?>
                            </ul>
                            <?php if ($moreActivities): ?>
                                <button class="btn btn-link p-0 fst-italic text-success fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#moreActivities" aria-expanded="false">
                                    xem thêm
                                </button>
                                <div class="collapse mt-3" id="moreActivities">
                                    <ul class="recent-activity">
                                        <?php foreach ($moreActivities as $activity): ?>
                                            <li><span>• <?= e($activity['message']) ?></span><small><?= e($activity['created_at']) ?></small></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div></div>
                    </div>
                </div>

                <script>
                    window.yumgoCharts = {
                        revenueLabels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
                        revenueData: <?= json_encode($chartRevenue, JSON_UNESCAPED_UNICODE) ?>,
                        statusLabels: <?= json_encode($statusLabels, JSON_UNESCAPED_UNICODE) ?>,
                        statusData: <?= json_encode($statusValues, JSON_UNESCAPED_UNICODE) ?>
                    };
                </script>
            <?php endif; ?>

            <?php if ($page === 'categories'): ?>
                <?php
                $categoryEdit = null;
                if ($editId > 0) {
                    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id');
                    $stmt->execute(['id' => $editId]);
                    $categoryEdit = $stmt->fetch() ?: null;
                }
                $categories = $pdo->query('SELECT * FROM categories ORDER BY is_deleted, name')->fetchAll();
                $categoryFoodRows = $pdo->query(
                    'SELECT f.*, c.name AS category_name
                     FROM foods f
                     JOIN categories c ON c.id = f.category_id
                     ORDER BY c.name, f.is_deleted, f.name'
                )->fetchAll();
                $categoryFoods = [];
                foreach ($categoryFoodRows as $foodRow) {
                    $categoryFoods[(int)$foodRow['category_id']][] = $foodRow;
                }
                ?>
                <div class="row g-4">
                    <div class="col-lg-4"><div class="card"><div class="card-body">
                        <h1 class="h5 page-title"><?= $categoryEdit ? 'Sửa danh mục' : 'Thêm danh mục' ?></h1>
                        <form method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="save_category">
                            <input type="hidden" name="id" value="<?= (int)($categoryEdit['id'] ?? 0) ?>">
                            <label class="form-label">Tên danh mục</label>
                            <input class="form-control mb-3" name="name" value="<?= e($categoryEdit['name'] ?? '') ?>" required>
                            <button class="btn btn-primary" type="submit">Lưu</button>
                        </form>
                    </div></div></div>
                    <div class="col-lg-8"><div class="card"><div class="card-body table-responsive">
                        <table class="table table-modern align-middle"><thead><tr><th>Tên danh mục</th><th>Trạng thái</th><th></th></tr></thead><tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= e($category['name']) ?></td>
                                <td><?= (int)$category['is_deleted'] ? '<span class="badge badge-danger">Đã ẩn</span>' : '<span class="badge badge-success">Đang dùng</span>' ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(adminUrl('categories', ['edit' => $category['id']])) ?>">Sửa</a>
                                    <form class="d-inline" method="post">
                            <?= csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_category_delete">
                                        <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                                        <input type="hidden" name="is_deleted" value="<?= (int)$category['is_deleted'] ? 0 : 1 ?>">
                                        <button class="btn btn-sm btn-outline-warning" type="submit"><?= (int)$category['is_deleted'] ? 'Khôi phục' : 'Ẩn' ?></button>
                                    </form>
                                    <form class="d-inline category-delete-form" method="post">
                            <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                                        <button
                                            class="btn btn-sm btn-outline-danger"
                                            type="button"
                                            data-category-delete-trigger
                                            data-category-name="<?= e($category['name']) ?>"
                                            data-category-foods="<?= e(json_encode($categoryFoods[(int)$category['id']] ?? [], JSON_UNESCAPED_UNICODE)) ?>"
                                        >Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody></table>
                    </div></div></div>
                </div>
                <div class="modal fade" id="categoryDeleteModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div>
                                    <h5 class="modal-title">Xác nhận xóa danh mục</h5>
                                    <div class="text-muted small" id="categoryDeleteName"></div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-warning">
                                    Khi xác nhận, toàn bộ món trong danh mục này sẽ bị xóa. Nếu món đã có trong lịch sử đơn hàng, hệ thống sẽ ẩn món và ẩn danh mục để giữ hóa đơn cũ không lỗi.
                                </div>
                                <div id="categoryDeleteFoods" class="menu-grid"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                                <button type="button" class="btn btn-danger" id="categoryDeleteConfirm">Xóa danh mục</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($page === 'foods'): ?>
                <?php
                $foodEdit = null;
                if ($editId > 0) {
                    $stmt = $pdo->prepare('SELECT * FROM foods WHERE id = :id');
                    $stmt->execute(['id' => $editId]);
                    $foodEdit = $stmt->fetch() ?: null;
                }
                $categories = $pdo->query('SELECT * FROM categories WHERE is_deleted = 0 ORDER BY name')->fetchAll();
                $foods = $pdo->query('SELECT f.*, c.name AS category_name FROM foods f JOIN categories c ON c.id = f.category_id WHERE f.is_deleted = 0 AND c.is_deleted = 0 ORDER BY f.created_at DESC')->fetchAll();
                ?>
                <div class="row g-4">
                    <div class="col-xl-4"><div class="card"><div class="card-body">
                        <h1 class="h5 page-title"><?= $foodEdit ? 'Sửa món ăn' : 'Thêm món ăn' ?></h1>
                        <form method="post" enctype="multipart/form-data">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="save_food">
                            <input type="hidden" name="id" value="<?= (int)($foodEdit['id'] ?? 0) ?>">
                            <input type="hidden" name="current_image" value="<?= e($foodEdit['image'] ?? '') ?>">
                            <label class="form-label">Danh mục</label>
                            <select class="form-select mb-2" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int)$category['id'] ?>" <?= (int)($foodEdit['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label class="form-label">Tên món</label>
                            <input class="form-control mb-2" name="name" value="<?= e($foodEdit['name'] ?? '') ?>" required>
                            <label class="form-label">Giá</label>
                            <input class="form-control mb-2" name="price" type="text" inputmode="numeric" data-money-input value="<?= e(isset($foodEdit['price']) ? number_format((float)$foodEdit['price'], 0, ',', '.') : '') ?>" required>
                            <label class="form-label">Hình ảnh</label>
                            <input class="form-control mb-2" name="image" type="file" accept=".jpg,.jpeg,.png,.webp">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-control mb-2" name="description" rows="3"><?= e($foodEdit['description'] ?? '') ?></textarea>
                            <div class="d-flex flex-wrap gap-3">
                                <label class="form-check"><input class="form-check-input" type="checkbox" name="is_hot" <?= (int)($foodEdit['is_hot'] ?? 0) ? 'checked' : '' ?>> HOT</label>
                                <label class="form-check"><input class="form-check-input" type="checkbox" name="is_sale" <?= (int)($foodEdit['is_sale'] ?? 0) ? 'checked' : '' ?>> SALE</label>
                                <label class="form-check"><input class="form-check-input" type="checkbox" name="is_available" <?= (int)($foodEdit['is_available'] ?? 1) ? 'checked' : '' ?>> Còn bán</label>
                            </div>
                            <label class="form-label mt-2">Giảm giá %</label>
                            <input class="form-control mb-3" name="discount_percent" type="number" min="0" max="100" value="<?= (int)($foodEdit['discount_percent'] ?? 0) ?>">
                            <button class="btn btn-primary" type="submit">Lưu</button>
                        </form>
                    </div></div></div>
                    <div class="col-xl-8"><div class="card"><div class="card-body table-responsive">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <h2 class="h5 page-title mb-0">Danh sách món ăn</h2>
                            <a class="btn btn-primary" href="<?= e(adminUrl('menu')) ?>"><i class="bi bi-grid-3x3-gap"></i> Xem menu</a>
                        </div>
                        <table class="table table-modern align-middle"><thead><tr><th>Món ăn</th><th>Danh mục</th><th>Giá</th><th>Nhãn</th><th></th></tr></thead><tbody>
                        <?php foreach ($foods as $food): ?>
                            <tr>
                                <td><?= e($food['name']) ?><div class="text-muted small"><?= e($food['image']) ?></div></td>
                                <td><?= e($food['category_name']) ?></td>
                                <td><?= moneyFormat($food['price']) ?></td>
                                <td>
                                    <?= (int)$food['is_hot'] ? '<span class="badge badge-danger">HOT</span>' : '' ?>
                                    <?= (int)$food['is_sale'] ? '<span class="badge badge-warning">SALE</span>' : '' ?>
                                    <?= (int)$food['is_available'] ? '<span class="badge badge-success">Còn bán</span>' : '<span class="badge badge-secondary">Tạm hết</span>' ?>
                                    <?= (int)$food['is_deleted'] ? '<span class="badge badge-danger">Đã ẩn</span>' : '' ?>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(adminUrl('foods', ['edit' => $food['id']])) ?>">Sửa</a>
                                    <form class="d-inline" method="post">
                            <?= csrfField() ?>
                                        <input type="hidden" name="action" value="toggle_food_delete">
                                        <input type="hidden" name="id" value="<?= (int)$food['id'] ?>">
                                        <input type="hidden" name="is_deleted" value="<?= (int)$food['is_deleted'] ? 0 : 1 ?>">
                                        <button class="btn btn-sm btn-outline-warning" type="submit"><?= (int)$food['is_deleted'] ? 'Khôi phục' : 'Ẩn' ?></button>
                                    </form>
                                    <form class="d-inline" method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa món này? Nếu món đã có trong đơn hàng, hệ thống sẽ ẩn món và tắt bán để giữ lịch sử đơn.');">
                            <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete_food">
                                        <input type="hidden" name="id" value="<?= (int)$food['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody></table>
                    </div></div></div>
                </div>
            <?php endif; ?>

            <?php if ($page === 'menu'): ?>
                <?php
                $menuCategories = $pdo->query(
                    'SELECT DISTINCT c.id, c.name
                     FROM categories c
                     JOIN foods f ON f.category_id = c.id
                     WHERE c.is_deleted = 0 AND f.is_deleted = 0
                     ORDER BY c.name'
                )->fetchAll();
                $selectedMenuCategory = isset($_GET['category_id']) ? max(0, (int)$_GET['category_id']) : 0;
                $menuCategoryIds = array_map(static fn(array $category): int => (int)$category['id'], $menuCategories);
                if ($selectedMenuCategory > 0 && !in_array($selectedMenuCategory, $menuCategoryIds, true)) {
                    $selectedMenuCategory = 0;
                }
                $menuSql =
                    'SELECT f.*, c.name AS category_name
                     FROM foods f JOIN categories c ON c.id = f.category_id
                     WHERE f.is_deleted = 0 AND c.is_deleted = 0';
                $menuParams = [];
                if ($selectedMenuCategory > 0) {
                    $menuSql .= ' AND c.id = :category_id';
                    $menuParams['category_id'] = $selectedMenuCategory;
                }
                $menuSql .= ' ORDER BY f.is_hot DESC, f.is_sale DESC, f.is_available DESC, c.name, f.name';
                $menuStmt = $pdo->prepare($menuSql);
                $menuStmt->execute($menuParams);
                $menuFoods = $menuStmt->fetchAll();
                ?>
                <section class="menu-preview">
                    <div class="menu-preview-header">
                        <div>
                            <div class="brand-link mb-2"><span class="brand-mark">Y</span><span>YumGO</span></div>
                            <div class="menu-eyebrow">Fresh Food Delivery</div>
                            <h1>Menu món ăn</h1>
                        </div>
                        <a class="btn btn-outline-primary" href="<?= e(adminUrl('foods')) ?>"><i class="bi bi-arrow-left"></i> Trở về</a>
                    </div>
                    <div class="menu-category-tabs">
                        <a class="menu-category-tab <?= $selectedMenuCategory === 0 ? 'active' : '' ?>" href="<?= e(adminUrl('menu')) ?>">Tất cả</a>
                        <?php foreach ($menuCategories as $category): ?>
                            <a class="menu-category-tab <?= $selectedMenuCategory === (int)$category['id'] ? 'active' : '' ?>" href="<?= e(adminUrl('menu', ['category_id' => $category['id']])) ?>"><?= e($category['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                    <div class="menu-grid">
                        <?php foreach ($menuFoods as $food): ?>
                            <?php
                            $imagePath = BASE_URL . '/uploads/foods/' . $food['image'];
                            $hasSalePrice = (int)$food['is_sale'] === 1 && (int)$food['discount_percent'] > 0;
                            $salePrice = max(0, (float)$food['price'] * (100 - (int)$food['discount_percent']) / 100);
                            ?>
                            <article class="menu-food-card">
                                <div class="menu-food-image" style="background-image:url('<?= e($imagePath) ?>')"></div>
                                <div class="menu-food-body">
                                    <div class="d-flex justify-content-between gap-2">
                                        <h2><?= e($food['name']) ?></h2>
                                        <div class="menu-price">
                                            <?php if ($hasSalePrice): ?>
                                                <span class="menu-price-original"><?= moneyFormat($food['price']) ?></span>
                                                <strong class="menu-price-sale"><?= moneyFormat($salePrice) ?></strong>
                                            <?php else: ?>
                                                <strong><?= moneyFormat($food['price']) ?></strong>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-muted small mb-2"><?= e($food['category_name']) ?></div>
                                    <p><?= e($food['description'] ?? 'Món ngon YumGO.') ?></p>
                                    <div class="menu-tag-row">
                                        <?= (int)$food['is_hot'] ? '<span class="badge badge-danger menu-tag">HOT</span>' : '' ?>
                                        <?= (int)$food['is_sale'] ? '<span class="badge badge-warning menu-tag">SALE</span>' : '' ?>
                                        <?= (int)$food['is_available'] ? '<span class="badge badge-success menu-tag">Còn bán</span>' : '<span class="badge badge-secondary menu-tag">Tạm hết</span>' ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($page === 'vouchers'): ?>
                <?php
                $voucherEdit = null;
                if ($editId > 0) {
                    $stmt = $pdo->prepare('SELECT * FROM vouchers WHERE id = :id');
                    $stmt->execute(['id' => $editId]);
                    $voucherEdit = $stmt->fetch() ?: null;
                }
                $vouchers = $pdo->query('SELECT * FROM vouchers ORDER BY created_at DESC')->fetchAll();
                $preview = null;
                if (isset($_GET['preview_code'], $_GET['preview_subtotal']) && $_GET['preview_code'] !== '') {
                    $preview = Voucher::preview($pdo, (string)$_GET['preview_code'], (float)$_GET['preview_subtotal']);
                }
                ?>
                <div class="row g-4">
                    <div class="col-lg-4"><div class="card mb-4"><div class="card-body">
                        <h1 class="h5 page-title"><?= $voucherEdit ? 'Sửa voucher' : 'Tạo voucher' ?></h1>
                        <form method="post">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="save_voucher">
                            <input type="hidden" name="id" value="<?= (int)($voucherEdit['id'] ?? 0) ?>">
                            <label class="form-label">Mã voucher</label>
                            <input class="form-control mb-2" name="code" value="<?= e($voucherEdit['code'] ?? '') ?>" required>
                            <label class="form-label">Loại giảm</label>
                            <select class="form-select mb-2" name="type" id="voucherType">
                                <option value="percent" <?= ($voucherEdit['type'] ?? '') === 'percent' ? 'selected' : '' ?>>Phần trăm</option>
                                <option value="fixed" <?= ($voucherEdit['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Số tiền cố định</option>
                            </select>
                            <label class="form-label">Giá trị</label>
                            <div class="input-group mb-2">
                                <input id="voucherValue" class="form-control" name="value" type="number" value="<?= e((string)($voucherEdit['value'] ?? '')) ?>" required>
                                <span id="voucherUnit" class="input-group-text"><?= ($voucherEdit['type'] ?? '') === 'percent' ? '%' : 'đ' ?></span>
                            </div>
                            <div class="form-text mb-2">Phần trăm: 10-100%. Cố định: trừ thẳng theo giá trị nhập.</div>
                            <label class="form-label">Đơn tối thiểu</label>
                            <input class="form-control mb-2" name="min_order" type="number" min="0" step="1000" value="<?= e((string)($voucherEdit['min_order'] ?? '0')) ?>">
                            <label class="form-label">Hết hạn lúc</label>
                            <input class="form-control mb-2" name="expired_at" type="datetime-local" value="<?= e(isset($voucherEdit['expired_at']) ? date('Y-m-d\TH:i', strtotime($voucherEdit['expired_at'])) : '') ?>" required>
                            <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" <?= (int)($voucherEdit['is_active'] ?? 1) ? 'checked' : '' ?>> Đang bật</label>
                            <button class="btn btn-primary" type="submit">Lưu</button>
                        </form>
                    </div></div>

                    <div class="card"><div class="card-body">
                        <h2 class="h6 page-title">Tính thử voucher</h2>
                        <form method="get">
                            <input type="hidden" name="page" value="vouchers">
                            <label class="form-label">Mã voucher</label>
                            <input class="form-control mb-2" name="preview_code" value="<?= e($_GET['preview_code'] ?? '') ?>" placeholder="YUM10">
                            <label class="form-label">Tạm tính đơn hàng</label>
                            <input class="form-control mb-3" type="number" name="preview_subtotal" value="<?= e($_GET['preview_subtotal'] ?? '100000') ?>">
                            <button class="btn btn-outline-primary w-100" type="submit">Tính tiền sau giảm</button>
                        </form>
                        <?php if ($preview): ?>
                            <div class="alert <?= $preview['valid'] ? 'alert-success' : 'alert-warning' ?> mt-3 mb-0">
                                <div><?= e($preview['message']) ?></div>
                                <strong>Giảm: <?= moneyFormat($preview['discount']) ?></strong><br>
                                <strong>Còn lại: <?= moneyFormat($preview['total']) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div></div></div>

                    <div class="col-lg-8"><div class="card"><div class="card-body table-responsive">
                        <table class="table table-modern align-middle"><thead><tr><th>Mã</th><th>Loại</th><th>Giá trị</th><th>Đơn tối thiểu</th><th>Hết hạn</th><th>Trạng thái</th><th></th></tr></thead><tbody>
                        <?php foreach ($vouchers as $voucher): ?>
                            <?php $expired = strtotime((string)$voucher['expired_at']) < time(); ?>
                            <tr>
                                <td><?= e($voucher['code']) ?></td>
                                <td><?= e($voucher['type'] === 'percent' ? 'Phần trăm' : 'Cố định') ?></td>
                                <td><?= $voucher['type'] === 'percent' ? e((string)(int)$voucher['value']) . '%' : moneyFormat($voucher['value']) ?></td>
                                <td><?= moneyFormat($voucher['min_order']) ?></td>
                                <td><?= e($voucher['expired_at']) ?></td>
                                <td><?= !$expired && (int)$voucher['is_active'] ? '<span class="badge badge-success">Đang bật</span>' : '<span class="badge badge-secondary">Không dùng</span>' ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(adminUrl('vouchers', ['edit' => $voucher['id']])) ?>">Sửa</a>
                                    <form class="d-inline" method="post">
                            <?= csrfField() ?>
                                        <input type="hidden" name="action" value="disable_voucher">
                                        <input type="hidden" name="id" value="<?= (int)$voucher['id'] ?>">
                                        <button class="btn btn-sm btn-outline-warning" type="submit">Tắt</button>
                                    </form>
                                    <form class="d-inline" method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa voucher này?');">
                            <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete_voucher">
                                        <input type="hidden" name="id" value="<?= (int)$voucher['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody></table>
                    </div></div></div>
                </div>
            <?php endif; ?>

            <?php if ($page === 'orders'): ?>
                <?php
                $viewOrderId = isset($_GET['view']) ? (int)$_GET['view'] : 0;
                $searchQ = trim((string)($_GET['q'] ?? ''));
                $viewOrder = $viewOrderId > 0 ? StaffOrder::find($pdo, $viewOrderId) : null;
                $orderItems = $viewOrder ? StaffOrder::items($pdo, $viewOrderId) : [];
                $displaySubtotal = array_sum(array_map(static fn(array $item): float => (float)$item['subtotal'], $orderItems));
                $displayShippingFee = $viewOrder ? (float)$viewOrder['shipping_fee'] : 0.0;
                $hasVoucherDiscount = $viewOrder && trim((string)($viewOrder['voucher_code'] ?? '')) !== '' && (float)$viewOrder['discount_amount'] > 0;
                $displayDiscount = $hasVoucherDiscount ? min((float)$viewOrder['discount_amount'], $displaySubtotal) : 0.0;
                $displayTotal = max(0, $displaySubtotal + $displayShippingFee - $displayDiscount);
                $orders = $searchQ !== '' ? StaffOrder::search($pdo, $searchQ) : StaffOrder::allWithShipper($pdo);
                $allAdminStatuses = ['Placed', 'Preparing', 'Ready', 'Delivering', 'Delivered', 'Cancelled By Admin'];
                $statuses = $viewOrder
                    ? array_values(array_filter($allAdminStatuses, static fn(string $status): bool => canAdminTransitionOrderStatus($viewOrder['status'], $status)))
                    : $allAdminStatuses;
                if ($viewOrder && ($viewOrder['delivery_type'] ?? 'delivery') === 'pickup' && $viewOrder['status'] === 'Ready' && !in_array('Delivered', $statuses, true)) {
                    $statuses[] = 'Delivered';
                }
                ?>
                <?php if ($searchQ !== ''): ?>
                    <div class="alert alert-info">Kết quả tìm kiếm cho: <strong><?= e($searchQ) ?></strong></div>
                <?php endif; ?>
                <?php if ($viewOrder): ?>
                    <div class="card mb-4"><div class="card-body">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <div>
                                <h1 class="h5 page-title">Đơn hàng <?= e($viewOrder['order_code']) ?></h1>
                                <div class="text-muted"><?= e($viewOrder['customer_name']) ?> - <?= e($viewOrder['phone']) ?> - <?= e($viewOrder['address']) ?></div>
                                <div class="text-muted small mt-1">
                                    Shipper:
                                    <?php if (!empty($viewOrder['shipper_name'])): ?>
                                        <strong><?= e($viewOrder['shipper_name']) ?></strong> - <?= e($viewOrder['shipper_phone'] ?? '') ?>
                                    <?php else: ?>
                                        Chưa phân công
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge <?= e(orderStatusBadgeClass($viewOrder['status'])) ?> align-self-start"><?= e(orderStatusLabel($viewOrder['status'])) ?></span>
                        </div>
                        <div class="delivery-check-panel mt-3">
                            <div>
                                <div class="text-muted small">Điểm lấy hàng</div>
                                <strong><?= e(RESTAURANT_ADDRESS) ?></strong>
                            </div>
                            <div>
                                <div class="text-muted small">Khoảng cách Google Maps</div>
                                <strong><?= $viewOrder['distance_km'] !== null ? number_format((float)$viewOrder['distance_km'], 2, ',', '.') . 'km' : 'Chưa kiểm tra' ?></strong>
                                <?php if (!empty($viewOrder['delivery_duration_text'])): ?>
                                    <span class="text-muted small"> - <?= e($viewOrder['delivery_duration_text']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="text-muted small">Trạng thái giao hàng</div>
                                <span class="badge <?= ($viewOrder['delivery_status'] ?? '') === DeliveryService::STATUS_TOO_FAR ? 'badge-danger' : (($viewOrder['delivery_status'] ?? '') === DeliveryService::STATUS_OK ? 'badge-success' : 'badge-secondary') ?>"><?= e(DeliveryService::statusLabel($viewOrder['delivery_status'] ?? null)) ?></span>
                            </div>
                            <?php if (($viewOrder['delivery_status'] ?? '') === DeliveryService::STATUS_TOO_FAR): ?>
                                <div class="alert alert-warning mb-0">Đơn này vượt quá 10km. Hệ thống không nên nhận giao hàng, chỉ nên chuyển sang khách tự lấy hoặc hủy giao.</div>
                            <?php endif; ?>
                            <form method="post" class="m-0">
                            <?= csrfField() ?>
                                <input type="hidden" name="action" value="refresh_delivery_distance">
                                <input type="hidden" name="id" value="<?= (int)$viewOrder['id'] ?>">
                                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-geo-alt"></i> Kiểm tra Google Maps</button>
                            </form>
                            <?php if (($viewOrder['delivery_type'] ?? 'delivery') !== DeliveryService::TYPE_PICKUP): ?>
                                <form method="post" class="m-0">
                            <?= csrfField() ?>
                                    <input type="hidden" name="action" value="set_order_pickup">
                                    <input type="hidden" name="id" value="<?= (int)$viewOrder['id'] ?>">
                                    <button class="btn btn-outline-warning" type="submit"><i class="bi bi-bag-check"></i> Khách tự lấy</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($viewOrder['issue_reason'])): ?>
                            <div class="alert alert-warning mt-3 mb-0">
                                <div class="fw-bold mb-1">Sự cố từ <?= e($viewOrder['shipper_name'] ?? 'shipper') ?></div>
                                <?php if (!empty($viewOrder['shipper_phone'])): ?>
                                    <div class="small text-muted mb-2">Số điện thoại shipper: <?= e($viewOrder['shipper_phone']) ?></div>
                                <?php endif; ?>
                                <div><strong>Lý do:</strong> <?= e($viewOrder['issue_reason']) ?></div>
                                <?php if (!empty($viewOrder['issue_note'])): ?>
                                    <div class="mt-1"><strong>Ghi chú:</strong> <?= nl2br(e($viewOrder['issue_note'])) ?></div>
                                <?php endif; ?>
                        <?php if (!empty($viewOrder['issue_reported_at'])): ?>
                                    <div class="small text-muted mt-2">Thời gian báo: <?= e(date('d/m/Y H:i', strtotime((string)$viewOrder['issue_reported_at']))) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (str_starts_with((string)$viewOrder['status'], 'Cancelled') && !empty($viewOrder['cancel_reason'])): ?>
                            <div class="alert alert-danger mt-3">
                                <strong>Lý do hủy:</strong> <?= e($viewOrder['cancel_reason']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="table-responsive mt-3"><table class="table"><thead><tr><th>Món ăn</th><th>SL</th><th>Giá tại lúc đặt</th><th>Tạm tính</th></tr></thead><tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr><td><?= e($item['name']) ?></td><td><?= (int)$item['quantity'] ?></td><td><?= moneyFormat($item['price']) ?></td><td><?= moneyFormat($item['subtotal']) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody></table></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <form class="d-flex gap-2" method="post">
                            <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update_order_status">
                                    <input type="hidden" name="id" value="<?= (int)$viewOrder['id'] ?>">
                                    <select class="form-select" name="status">
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= e($status) ?>" <?= $viewOrder['status'] === $status ? 'selected' : '' ?>><?= e(orderStatusLabel($status)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-primary" type="submit">Cập nhật</button>
                                </form>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="order-money-summary">
                                    <div><strong>Tạm tính:</strong> <?= moneyFormat($displaySubtotal) ?></div>
                                    <div><strong>Phí giao:</strong> <?= moneyFormat($displayShippingFee) ?></div>
                                    <?php if ($hasVoucherDiscount): ?>
                                        <div><strong>Voucher <?= e($viewOrder['voucher_code']) ?>:</strong> -<?= moneyFormat($displayDiscount) ?></div>
                                    <?php endif; ?>
                                    <div class="h5 mt-1 mb-0"><strong>Tổng:</strong> <?= moneyFormat($displayTotal) ?></div>
                                </div>
                                <form class="mt-2" method="post">
                            <?= csrfField() ?>
                                    <input type="hidden" name="action" value="cancel_order">
                                    <input type="hidden" name="id" value="<?= (int)$viewOrder['id'] ?>">
                                    <input class="form-control mb-2" name="cancel_reason" maxlength="255" placeholder="Nhập lý do hủy để hiện cho khách" required>
                                    <button class="btn btn-outline-danger" type="submit">Admin hủy đơn</button>
                                </form>
                            </div>
                        </div>
                    </div></div>
                <?php endif; ?>
                <div class="card"><div class="card-body table-responsive">
                    <h1 class="h5 page-title">Quản lý đơn hàng</h1>
                    <table class="table table-modern align-middle"><thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Khoảng cách</th><th>Phí ship</th><th>Tổng tiền</th><th>Trạng thái</th><th>Shipper</th><th></th></tr></thead><tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= e($order['order_code']) ?></td>
                            <td><?= e($order['customer_name']) ?><div class="text-muted small"><?= e($order['phone']) ?></div></td>
                            <td><?= $order['distance_km'] !== null ? number_format((float)$order['distance_km'], 2, ',', '.') . 'km' : '-' ?></td>
                            <td><?= moneyFormat($order['shipping_fee']) ?></td>
                            <td><?= moneyFormat($order['total']) ?></td>
                            <td><span class="badge <?= e(orderStatusBadgeClass($order['status'])) ?>"><?= e(orderStatusLabel($order['status'])) ?></span></td>
                            <td><?= e($order['shipper_name'] ?? 'Chưa nhận') ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="<?= e(adminUrl('orders', ['view' => $order['id']])) ?>">Chi tiết</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table>
                </div></div>
            <?php endif; ?>

            <?php if ($page === 'shippers'): ?>
                <?php
                $shipperEdit = null;
                if ($editId > 0) {
                    $stmt = $pdo->prepare('SELECT * FROM shippers WHERE id = :id');
                    $stmt->execute(['id' => $editId]);
                    $shipperEdit = $stmt->fetch() ?: null;
                }
                $shippers = $pdo->query('SELECT * FROM shippers ORDER BY is_active DESC, name')->fetchAll();
                ?>
                <div class="row g-4">
                    <div class="col-lg-4"><div class="card"><div class="card-body">
                        <h1 class="h5 page-title"><?= $shipperEdit ? 'Sửa shipper' : 'Tạo shipper' ?></h1>
                        <form method="post" enctype="multipart/form-data">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="save_shipper">
                            <input type="hidden" name="id" value="<?= (int)($shipperEdit['id'] ?? 0) ?>">
                            <input type="hidden" name="current_avatar" value="<?= e($shipperEdit['avatar'] ?? '') ?>">
                            <label class="form-label">Tên shipper</label>
                            <input class="form-control mb-2" name="name" value="<?= e($shipperEdit['name'] ?? '') ?>" required>
                            <label class="form-label">Số điện thoại</label>
                            <input class="form-control mb-2" name="phone" inputmode="numeric" maxlength="10" value="<?= e($shipperEdit['phone'] ?? '') ?>" required>
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-control mb-2" name="description" rows="3"><?= e($shipperEdit['description'] ?? '') ?></textarea>
                            <label class="form-label">Mật khẩu <?= $shipperEdit ? '(để trống nếu không đổi)' : '' ?></label>
                            <div class="password-field mb-2">
                                <input class="form-control" id="shipperPassword" name="password" type="password" maxlength="18" placeholder="<?= $shipperEdit ? 'Không nhập mới sẽ giữ nguyên' : '6-18 ký tự, có chữ hoa và số' ?>" <?= $shipperEdit ? '' : 'required' ?>>
                                <button class="password-toggle" type="button" data-toggle-password="shipperPassword" aria-label="Xem mật khẩu"><i class="bi bi-eye"></i></button>
                            </div>
                            <label class="form-label">Avatar</label>
                            <input class="form-control mb-2" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp">
                            <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" <?= (int)($shipperEdit['is_active'] ?? 1) ? 'checked' : '' ?>> Đang hoạt động</label>
                            <button class="btn btn-primary" type="submit">Lưu</button>
                        </form>
                    </div></div></div>
                    <div class="col-lg-8"><div class="card"><div class="card-body table-responsive">
                        <table class="table table-modern align-middle"><thead><tr><th>Tên shipper</th><th>Số điện thoại</th><th>Mô tả</th><th>Trạng thái</th><th></th></tr></thead><tbody>
                        <?php foreach ($shippers as $shipper): ?>
                            <tr>
                                <td><?= e($shipper['name']) ?></td>
                                <td><?= e($shipper['phone']) ?></td>
                                <td class="text-muted small"><?= e(mb_substr((string)($shipper['description'] ?? ''), 0, 90)) ?></td>
                                <td><?= (int)$shipper['is_active'] ? '<span class="badge badge-success">Đang hoạt động</span>' : '<span class="badge badge-secondary">Đã khóa</span>' ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(adminUrl('shippers', ['edit' => $shipper['id']])) ?>">Sửa</a>
                                    <form class="d-inline" method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa shipper này? Shipper sẽ bị gỡ khỏi các đơn cũ.');">
                            <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete_shipper">
                                        <input type="hidden" name="id" value="<?= (int)$shipper['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody></table>
                    </div></div></div>
                </div>
            <?php endif; ?>

            <?php if ($page === 'profile'): ?>
                <?php
                $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = :id');
                $stmt->execute(['id' => (int)$_SESSION['user']['id']]);
                $admin = $stmt->fetch();
                ?>
                <div class="card profile-card"><div class="card-body">
                    <h1 class="h5 page-title">Hồ sơ admin</h1>
                    <form method="post" enctype="multipart/form-data">
                            <?= csrfField() ?>
                        <input type="hidden" name="action" value="save_profile">
                        <input type="hidden" name="current_avatar" value="<?= e($admin['avatar'] ?? '') ?>">
                        <label class="form-label">Tên hiển thị</label>
                        <input class="form-control mb-2" name="display_name" value="<?= e($admin['display_name'] ?? '') ?>" required>
                        <label class="form-label">Mật khẩu mới</label>
                        <div class="password-field mb-2">
                            <input class="form-control" id="adminProfilePassword" name="password" type="password" maxlength="18" placeholder="Không nhập mới sẽ giữ nguyên">
                            <button class="password-toggle" type="button" data-toggle-password="adminProfilePassword" aria-label="Xem mật khẩu"><i class="bi bi-eye"></i></button>
                        </div>
                        <label class="form-label">Avatar</label>
                        <input class="form-control mb-3" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp">
                        <button class="btn btn-primary" type="submit">Lưu</button>
                    </form>
                </div></div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= e(BASE_URL . '/assets/js/staff.js') ?>"></script>
<script>
const typeSelect = document.getElementById('voucherType');
const unit = document.getElementById('voucherUnit');
document.querySelectorAll('.admin-profile + .dropdown-menu a[href*="page=dashboard"]').forEach((link) => {
    link.closest('li')?.remove();
});
if (typeSelect && unit) {
    const syncVoucherUnit = () => { unit.textContent = typeSelect.value === 'percent' ? '%' : 'đ'; };
    typeSelect.addEventListener('change', syncVoucherUnit);
    syncVoucherUnit();
}

document.querySelectorAll('[data-money-input]').forEach((input) => {
    const formatMoney = () => {
        const digits = input.value.replace(/[^\d]/g, '');
        if (!digits) {
            input.value = '';
            return;
        }
        const value = digits.length <= 3 ? Number(digits) * 1000 : Number(digits);
        input.value = new Intl.NumberFormat('vi-VN').format(value);
    };
    input.addEventListener('blur', formatMoney);
    input.form?.addEventListener('submit', () => {
        input.value = input.value.replace(/[^\d]/g, '');
    });
});

const categoryDeleteModalEl = document.getElementById('categoryDeleteModal');
if (categoryDeleteModalEl) {
    const categoryDeleteModal = new bootstrap.Modal(categoryDeleteModalEl);
    const categoryDeleteName = document.getElementById('categoryDeleteName');
    const categoryDeleteFoods = document.getElementById('categoryDeleteFoods');
    const categoryDeleteConfirm = document.getElementById('categoryDeleteConfirm');
    let pendingCategoryDeleteForm = null;
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    document.querySelectorAll('[data-category-delete-trigger]').forEach((button) => {
        button.addEventListener('click', () => {
            pendingCategoryDeleteForm = button.closest('form');
            const foods = JSON.parse(button.dataset.categoryFoods || '[]');
            if (categoryDeleteName) {
                categoryDeleteName.textContent = 'Danh mục: ' + (button.dataset.categoryName || '');
            }
            if (categoryDeleteFoods) {
                if (!foods.length) {
                    categoryDeleteFoods.innerHTML = '<div class="alert alert-secondary mb-0">Danh mục này chưa có món.</div>';
                } else {
                    categoryDeleteFoods.innerHTML = foods.map((food) => {
                        const image = food.image ? `<?= e(BASE_URL) ?>/uploads/foods/${escapeHtml(food.image)}` : '';
                        const price = new Intl.NumberFormat('vi-VN').format(Number(food.price || 0)) + 'đ';
                        const status = Number(food.is_available) === 1 && Number(food.is_deleted) === 0 ? 'Còn bán' : 'Đang ẩn/tạm hết';
                        return `
                            <article class="menu-food-card">
                                <div class="menu-food-image">
                                    ${image ? `<img src="${image}" alt="${escapeHtml(food.name)}">` : '<span>Y</span>'}
                                </div>
                                <div class="menu-food-content">
                                    <div class="text-muted small">${escapeHtml(food.category_name || '')}</div>
                                    <h3>${escapeHtml(food.name)}</h3>
                                    <p>${escapeHtml(food.description || 'Món ngon YumGO.')}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>${price}</strong>
                                        <span class="badge badge-secondary">${status}</span>
                                    </div>
                                </div>
                            </article>
                        `;
                    }).join('');
                }
            }
            categoryDeleteModal.show();
        });
    });
    categoryDeleteConfirm?.addEventListener('click', () => pendingCategoryDeleteForm?.submit());
}

if (window.yumgoCharts) {
    const revenueCanvas = document.getElementById('revenueChart');
    const statusCanvas = document.getElementById('statusChart');
    if (revenueCanvas) {
        new Chart(revenueCanvas, {
            type: 'line',
            data: {
                labels: window.yumgoCharts.revenueLabels,
                datasets: [{ label: 'Doanh thu', data: window.yumgoCharts.revenueData, borderColor: '#ff6b35', backgroundColor: 'rgba(255,107,53,.12)', tension: .38, fill: true }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }
    if (statusCanvas) {
        new Chart(statusCanvas, {
            type: 'doughnut',
            data: { labels: window.yumgoCharts.statusLabels, datasets: [{ data: window.yumgoCharts.statusData, backgroundColor: ['#0ea5e9','#ef4444','#16a34a'] }] },
            options: { plugins: { legend: { position: 'bottom' } }, cutout: '62%' }
        });
    }
}
</script>
</body>
</html>

