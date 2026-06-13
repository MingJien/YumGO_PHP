<?php
declare(strict_types=1);

// Session helper
function startSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = parse_url(BASE_URL, PHP_URL_PATH) ?: '';
        $basePath = rtrim($basePath, '/');
        $cookiePath = $basePath !== '' ? $basePath . '/' : '/';
        $sessionName = 'YUMGO_USER_SESSID';

        if (preg_match('#/(admin)(/|$)#', $scriptName)) {
            $sessionName = 'YUMGO_ADMIN_SESSID';
            $cookiePath = ($basePath !== '' ? $basePath : '') . '/admin';
        } elseif (preg_match('#/(shipper)(/|$)#', $scriptName)) {
            $sessionName = 'YUMGO_SHIPPER_SESSID';
            $cookiePath = ($basePath !== '' ? $basePath : '') . '/shipper';
        }

        session_name($sessionName);

        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => $cookiePath,
                'domain' => '',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        session_start();
    }
}

function csrfToken(): string
{
    startSession();

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrfToken(?string $token): bool
{
    startSession();

    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function requireValidCsrf(): void
{
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        throw new RuntimeException('Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
    }
}

// Cart contract: chi luu [food_id => quantity]
function ensureCartInitialized(): void
{
    startSession();

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

// Sanitize input cho output HTML
function sanitizeInput(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Redirect helper
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function setFlash(string $type, string $message): void
{
    startSession();
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    startSession();

    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function isValidPhone(string $phone): bool
{
    return validatePhoneDetailed($phone) === null;
}

function validatePhoneDetailed(string $phone): ?string
{
    $phone = trim($phone);

    if ($phone === '') {
        return 'Số điện thoại không được để trống.';
    }

    if (preg_match('/\D/', $phone) === 1) {
        return 'Số điện thoại chỉ được nhập chữ số, không được nhập chữ cái hoặc ký tự đặc biệt.';
    }

    if (!str_starts_with($phone, '0')) {
        return 'Số điện thoại phải bắt đầu bằng số 0.';
    }

    if (strlen($phone) !== 10) {
        return 'Số điện thoại phải đủ đúng 10 số.';
    }

    return null;
}

function validatePasswordDetailed(string $password, bool $required = true): ?string
{
    if ($password === '') {
        return $required ? 'Mật khẩu không được để trống.' : null;
    }

    $length = strlen($password);
    if ($length < 6) {
        return 'Mật khẩu phải có tối thiểu 6 ký tự.';
    }

    if ($length > 18) {
        return 'Mật khẩu chỉ được tối đa 18 ký tự.';
    }

    if (preg_match('/[A-Z]/', $password) !== 1) {
        return 'Mật khẩu phải có ít nhất 1 ký tự in hoa.';
    }

    if (preg_match('/[0-9]/', $password) !== 1) {
        return 'Mật khẩu phải có ít nhất 1 chữ số.';
    }

    return null;
}

function isFinalOrderStatus(string $status): bool
{
    return in_array($status, ['Delivered', 'Cancelled By User', 'Cancelled By Admin', 'Cancelled By Shipper'], true);
}

function canAdminTransitionOrderStatus(string $currentStatus, string $nextStatus): bool
{
    if ($currentStatus === $nextStatus) {
        return true;
    }

    if (isFinalOrderStatus($currentStatus)) {
        return false;
    }

    $allowedTransitions = [
        'Placed' => ['Preparing', 'Cancelled By Admin'],
        'Preparing' => ['Ready', 'Cancelled By Admin'],
        'Ready' => ['Cancelled By Admin'],
        'Delivering' => ['Delivered'],
    ];

    return in_array($nextStatus, $allowedTransitions[$currentStatus] ?? [], true);
}

function canShipperAcceptOrder(string $status): bool
{
    return $status === 'Ready';
}

function canShipperCompleteOrder(string $status): bool
{
    return $status === 'Delivering';
}

function orderStatusLabel(string $status): string
{
    $labels = [
        'Placed' => 'Đã đặt',
        'Preparing' => 'Đang chuẩn bị',
        'Ready' => 'Đã chuẩn bị',
        'Delivering' => 'Đang giao',
        'Delivered' => 'Đã nhận',
        'Cancelled By User' => 'Khách đã hủy',
        'Cancelled By Admin' => 'Admin đã hủy',
        'Cancelled By Shipper' => 'Shipper đã hủy',
    ];

    return $labels[$status] ?? $status;
}

function orderStatusBadgeClass(string $status): string
{
    $classes = [
        'Placed' => 'badge-primary',
        'Preparing' => 'badge-warning',
        'Ready' => 'badge-info',
        'Delivering' => 'badge-warning',
        'Delivered' => 'badge-success',
        'Cancelled By User' => 'badge-danger',
        'Cancelled By Admin' => 'badge-danger',
        'Cancelled By Shipper' => 'badge-danger',
    ];

    return $classes[$status] ?? 'badge-secondary';
}

// Activities helper: ensure table exists and log activity
function ensureActivitiesTable(PDO $pdo): void
{
    $sql = "CREATE TABLE IF NOT EXISTS activities (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        message VARCHAR(255) NOT NULL,
        meta JSON NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
}

function pruneActivities(PDO $pdo, int $days = 3): void
{
    ensureActivitiesTable($pdo);

    $days = max(1, $days);
    $pdo->exec('DELETE FROM activities WHERE created_at < DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)');
}

function logActivity(PDO $pdo, string $type, string $message, $meta = null): void
{
    ensureActivitiesTable($pdo);
    $sql = 'INSERT INTO activities (type, message, meta) VALUES (:type, :message, :meta)';
    $stmt = $pdo->prepare($sql);
    $metaJson = $meta !== null ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;
    $stmt->execute(['type' => $type, 'message' => $message, 'meta' => $metaJson]);
}

// Upload helper: validate extension, mime, size va rename
function uploadImage(array $file, string $targetDir, array $allowedExtensions, int $maxSize): ?string
{
    if (!isset($file['error'], $file['tmp_name'], $file['name'], $file['size'])) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($file['size'] > $maxSize) {
        return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return null;
    }

    $mimeMap = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!isset($mimeMap[$extension]) || $mimeType !== $mimeMap[$extension]) {
        return null;
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $newName = uniqid('img_', true) . '.' . $extension;
    $targetPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $newName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return null;
    }

    return $newName;
}
