<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../models/Order.php';

final class ShipperController
{
    public static function ensureTables(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS shipper_statuses (
                shipper_id INT UNSIGNED PRIMARY KEY,
                is_online TINYINT(1) NOT NULL DEFAULT 1,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_shipper_statuses_shipper FOREIGN KEY (shipper_id) REFERENCES shippers(id)
                    ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS shipper_order_actions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                shipper_id INT UNSIGNED NOT NULL,
                order_id INT UNSIGNED NULL,
                action VARCHAR(30) NOT NULL,
                reason VARCHAR(255) NULL,
                note TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_shipper_actions_shipper (shipper_id),
                INDEX idx_shipper_actions_order (order_id),
                CONSTRAINT fk_shipper_actions_shipper FOREIGN KEY (shipper_id) REFERENCES shippers(id)
                    ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT fk_shipper_actions_order FOREIGN KEY (order_id) REFERENCES orders(id)
                    ON UPDATE CASCADE ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        self::ensureOrderColumn($pdo, 'cod_collected', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::ensureOrderColumn($pdo, 'issue_reason', 'VARCHAR(255) NULL');
        self::ensureOrderColumn($pdo, 'issue_note', 'TEXT NULL');
        self::ensureOrderColumn($pdo, 'issue_reported_at', 'DATETIME NULL');
        self::ensureOrderColumn($pdo, 'delivery_type', "ENUM('delivery','pickup') NOT NULL DEFAULT 'delivery'");
        self::ensureOrderColumn($pdo, 'delivery_status', "VARCHAR(40) NOT NULL DEFAULT 'unchecked'");
        self::ensureOrderColumn($pdo, 'distance_km', 'DECIMAL(8,2) NULL');
        self::ensureOrderColumn($pdo, 'delivery_duration_text', 'VARCHAR(50) NULL');
        self::ensureOrderStatusEnum($pdo);
    }

    public static function isOnline(PDO $pdo, int $shipperId): bool
    {
        $stmt = $pdo->prepare('SELECT is_online FROM shipper_statuses WHERE shipper_id = :shipper_id LIMIT 1');
        $stmt->execute(['shipper_id' => $shipperId]);
        $status = $stmt->fetchColumn();

        if ($status === false) {
            $stmt = $pdo->prepare('INSERT INTO shipper_statuses (shipper_id, is_online) VALUES (:shipper_id, 1)');
            $stmt->execute(['shipper_id' => $shipperId]);

            return true;
        }

        return (int)$status === 1;
    }

    public static function setOnline(PDO $pdo, int $shipperId, bool $isOnline): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO shipper_statuses (shipper_id, is_online)
             VALUES (:shipper_id, :is_online)
             ON DUPLICATE KEY UPDATE is_online = VALUES(is_online)'
        );
        $stmt->execute([
            'shipper_id' => $shipperId,
            'is_online' => $isOnline ? 1 : 0,
        ]);

        self::logAction($pdo, $shipperId, null, $isOnline ? 'online' : 'offline');
    }

    public static function register(PDO $pdo, array $data, array $files): void
    {
        $name = trim((string)($data['name'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $password = (string)($data['password'] ?? '');

        $phoneError = validatePhoneDetailed($phone);
        $passwordError = validatePasswordDetailed($password, true);

        if ($name === '') {
            throw new RuntimeException('Họ tên không được để trống.');
        }

        if ($phoneError !== null) {
            throw new RuntimeException($phoneError);
        }

        if ($passwordError !== null) {
            throw new RuntimeException($passwordError);
        }

        $stmt = $pdo->prepare('SELECT id FROM shippers WHERE phone = :phone LIMIT 1');
        $stmt->execute(['phone' => $phone]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Số điện thoại này đã được đăng ký.');
        }

        $avatar = null;
        if (isset($files['avatar']) && $files['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $avatar = uploadImage($files['avatar'], UPLOAD_AVATAR_PATH, ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
            if ($avatar === null) {
                throw new RuntimeException('Tải avatar thất bại.');
            }
        }

        $stmt = $pdo->prepare(
            'INSERT INTO shippers (name, phone, password, avatar, is_active)
             VALUES (:name, :phone, :password, :avatar, 1)'
        );
        $stmt->execute([
            'name' => $name,
            'phone' => $phone,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'avatar' => $avatar,
        ]);
    }

    public static function updateProfile(PDO $pdo, int $shipperId, array $data, array $files): array
    {
        $name = trim((string)($data['name'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($name === '') {
            throw new RuntimeException('Họ tên không được để trống.');
        }

        $phoneError = validatePhoneDetailed($phone);
        if ($phoneError !== null) {
            throw new RuntimeException($phoneError);
        }

        if ($password !== '') {
            $passwordError = validatePasswordDetailed($password, false);
            if ($passwordError !== null) {
                throw new RuntimeException($passwordError);
            }
        }

        $stmt = $pdo->prepare('SELECT id FROM shippers WHERE phone = :phone AND id <> :id LIMIT 1');
        $stmt->execute(['phone' => $phone, 'id' => $shipperId]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Số điện thoại này đã được shipper khác sử dụng.');
        }

        $currentStmt = $pdo->prepare('SELECT * FROM shippers WHERE id = :id LIMIT 1');
        $currentStmt->execute(['id' => $shipperId]);
        $current = $currentStmt->fetch();
        if (!$current) {
            throw new RuntimeException('Không tìm thấy tài khoản shipper.');
        }

        $avatar = $current['avatar'] ?? null;
        if (isset($files['avatar']) && $files['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploaded = uploadImage($files['avatar'], UPLOAD_AVATAR_PATH, ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
            if ($uploaded === null) {
                throw new RuntimeException('Tải avatar thất bại.');
            }
            $avatar = $uploaded;
        }

        $params = [
            'name' => $name,
            'phone' => $phone,
            'avatar' => $avatar,
            'id' => $shipperId,
        ];
        $passwordSql = '';
        if ($password !== '') {
            $passwordSql = ', password = :password';
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $stmt = $pdo->prepare(
            "UPDATE shippers
             SET name = :name, phone = :phone, avatar = :avatar {$passwordSql}
             WHERE id = :id"
        );
        $stmt->execute($params);

        $freshStmt = $pdo->prepare('SELECT id, name, phone, avatar, is_active, created_at FROM shippers WHERE id = :id LIMIT 1');
        $freshStmt->execute(['id' => $shipperId]);
        $fresh = $freshStmt->fetch();
        if (!$fresh) {
            throw new RuntimeException('Không thể tải lại hồ sơ shipper.');
        }

        self::logAction($pdo, $shipperId, null, 'profile_update');

        return $fresh;
    }

    public static function skip(PDO $pdo, int $orderId, int $shipperId, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('Vui lòng chọn lý do bỏ qua đơn.');
        }

        $order = Order::find($pdo, $orderId);
        if (!$order || !canShipperAcceptOrder($order['status'])) {
            throw new RuntimeException('Chỉ có thể bỏ qua đơn ở trạng thái đã chuẩn bị.');
        }

        self::logAction($pdo, $shipperId, $orderId, 'skip', $reason);
    }

    public static function accept(PDO $pdo, int $orderId, int $shipperId): void
    {
        if (!self::isOnline($pdo, $shipperId)) {
            throw new RuntimeException('Bạn đang ngoại tuyến, hãy bật online trước khi nhận đơn.');
        }

        $order = Order::find($pdo, $orderId);
        if (!$order || !canShipperAcceptOrder($order['status'])) {
            throw new RuntimeException('Chỉ có thể nhận đơn ở trạng thái đã chuẩn bị.');
        }
        if (($order['delivery_type'] ?? 'delivery') === 'pickup') {
            throw new RuntimeException('Đơn này khách đến tự lấy, shipper không thể nhận giao.');
        }
        if (($order['delivery_status'] ?? 'unchecked') === 'too_far') {
            throw new RuntimeException('Đơn vượt quá phạm vi giao hàng, shipper không thể nhận.');
        }

        $stmt = $pdo->prepare(
            "UPDATE orders SET shipper_id = :shipper_id, status = 'Delivering'
             WHERE id = :id
               AND status = 'Ready'
               AND COALESCE(delivery_type, 'delivery') = 'delivery'
               AND COALESCE(delivery_status, 'unchecked') <> 'too_far'"
        );
        $stmt->execute(['shipper_id' => $shipperId, 'id' => $orderId]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Đơn hàng đã được shipper khác nhận.');
        }

        self::logAction($pdo, $shipperId, $orderId, 'accept');
    }

    public static function complete(PDO $pdo, int $orderId, int $shipperId, string $note = ''): void
    {
        $order = Order::find($pdo, $orderId);
        if (!$order || !canShipperCompleteOrder($order['status']) || (int)$order['shipper_id'] !== $shipperId) {
            throw new RuntimeException('Chỉ shipper đang giao đơn mới được hoàn thành.');
        }

        $stmt = $pdo->prepare(
            "UPDATE orders SET status = 'Delivered'
             WHERE id = :id AND shipper_id = :shipper_id AND status = 'Delivering'"
        );
        $stmt->execute(['id' => $orderId, 'shipper_id' => $shipperId]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Đơn hàng đã thay đổi trạng thái, vui lòng tải lại trang.');
        }

        self::logAction($pdo, $shipperId, $orderId, 'complete', null, trim($note));
    }

    public static function collectCod(PDO $pdo, int $orderId, int $shipperId): void
    {
        $order = Order::find($pdo, $orderId);
        if (!$order || (int)$order['shipper_id'] !== $shipperId || !in_array($order['status'], ['Delivering', 'Delivered'], true)) {
            throw new RuntimeException('Chỉ có thể xác nhận thu COD cho đơn của bạn.');
        }

        if (strtoupper((string)$order['payment_method']) !== 'COD') {
            throw new RuntimeException('Đơn này không phải thanh toán COD.');
        }

        $stmt = $pdo->prepare('UPDATE orders SET cod_collected = 1 WHERE id = :id AND shipper_id = :shipper_id');
        $stmt->execute(['id' => $orderId, 'shipper_id' => $shipperId]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Không thể cập nhật COD cho đơn này, vui lòng tải lại trang.');
        }

        self::logAction($pdo, $shipperId, $orderId, 'cod_collected');
    }

    public static function reportIssue(PDO $pdo, int $orderId, int $shipperId, string $reason, string $note): void
    {
        $reason = trim($reason);
        $note = trim($note);
        if ($reason === '') {
            throw new RuntimeException('Vui lòng chọn lý do sự cố.');
        }

        $order = Order::find($pdo, $orderId);
        if (!$order || (int)$order['shipper_id'] !== $shipperId || $order['status'] !== 'Delivering') {
            throw new RuntimeException('Chỉ có thể báo sự cố cho đơn đang giao của bạn.');
        }

        $stmt = $pdo->prepare(
            'UPDATE orders
             SET issue_reason = :reason, issue_note = :note, issue_reported_at = NOW()
             WHERE id = :id AND shipper_id = :shipper_id'
        );
        $stmt->execute([
            'reason' => $reason,
            'note' => $note,
            'id' => $orderId,
            'shipper_id' => $shipperId,
        ]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Không thể lưu sự cố vì đơn hàng đã thay đổi trạng thái.');
        }

        self::logAction($pdo, $shipperId, $orderId, 'issue_report', $reason, $note);
    }

    public static function cancel(PDO $pdo, int $orderId, int $shipperId, string $reason, string $note): void
    {
        $reason = trim($reason);
        $note = trim($note);
        if ($reason === '') {
            throw new RuntimeException('Vui lòng chọn lý do hủy đơn.');
        }

        $order = Order::find($pdo, $orderId);
        if (!$order || (int)$order['shipper_id'] !== $shipperId || $order['status'] !== 'Delivering') {
            throw new RuntimeException('Chỉ có thể hủy đơn đang giao của bạn.');
        }

        $stmt = $pdo->prepare(
            "UPDATE orders
             SET status = 'Cancelled By Shipper',
                 issue_reason = :reason,
                 issue_note = :note,
                 issue_reported_at = NOW()
             WHERE id = :id AND shipper_id = :shipper_id AND status = 'Delivering'"
        );
        $stmt->execute([
            'reason' => $reason,
            'note' => $note,
            'id' => $orderId,
            'shipper_id' => $shipperId,
        ]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Không thể hủy vì đơn hàng đã thay đổi trạng thái.');
        }

        self::logAction($pdo, $shipperId, $orderId, 'cancel_order', $reason, $note);
    }

    private static function logAction(PDO $pdo, int $shipperId, ?int $orderId, string $action, ?string $reason = null, ?string $note = null): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO shipper_order_actions (shipper_id, order_id, action, reason, note)
             VALUES (:shipper_id, :order_id, :action, :reason, :note)'
        );
        $stmt->execute([
            'shipper_id' => $shipperId,
            'order_id' => $orderId,
            'action' => $action,
            'reason' => $reason,
            'note' => $note,
        ]);
    }

    private static function ensureOrderColumn(PDO $pdo, string $column, string $definition): void
    {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'orders'
               AND COLUMN_NAME = :column"
        );
        $stmt->execute(['column' => $column]);

        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN {$column} {$definition}");
        }
    }

    private static function ensureOrderStatusEnum(PDO $pdo): void
    {
        $stmt = $pdo->query(
            "SELECT COLUMN_TYPE
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'orders'
               AND COLUMN_NAME = 'status'"
        );
        $columnType = (string)$stmt->fetchColumn();

        if (!str_contains($columnType, 'Cancelled By Shipper')) {
            $pdo->exec(
                "ALTER TABLE orders MODIFY status
                 ENUM('Placed','Preparing','Ready','Delivering','Delivered','Cancelled By User','Cancelled By Admin','Cancelled By Shipper')
                 NOT NULL DEFAULT 'Placed'"
            );
        }
    }
}
