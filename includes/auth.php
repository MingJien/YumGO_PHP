<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

// Admin login foundation
function loginAdmin(string $username, string $password): bool
{
    startSession();

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT id, username, password, display_name, avatar FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['role'] = 'admin';
    $_SESSION['user'] = [
        'id' => (int)$admin['id'],
        'username' => $admin['username'],
        'display_name' => $admin['display_name'],
        'avatar' => $admin['avatar'],
    ];

    return true;
}

// Shipper login foundation
function loginShipper(string $phone, string $password): bool
{
    startSession();

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare('SELECT id, name, phone, password, avatar FROM shippers WHERE phone = :phone AND is_active = 1 LIMIT 1');
    $stmt->execute(['phone' => $phone]);
    $shipper = $stmt->fetch();

    if (!$shipper || !password_verify($password, $shipper['password'])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['role'] = 'shipper';
    $_SESSION['user'] = [
        'id' => (int)$shipper['id'],
        'name' => $shipper['name'],
        'phone' => $shipper['phone'],
        'avatar' => $shipper['avatar'],
    ];

    return true;
}

// Session check
function isLoggedIn(): bool
{
    startSession();

    return isset($_SESSION['role']);
}

function isRole(string $role): bool
{
    startSession();

    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Session middleware
function requireAdminLogin(): void
{
    if (!isRole('admin')) {
        redirect(ADMIN_LOGIN_URL);
    }
}

function requireShipperLogin(): void
{
    if (!isRole('shipper')) {
        redirect(SHIPPER_LOGIN_URL);
    }
}

function redirectIfLoggedIn(): void
{
    startSession();

    if (!isset($_SESSION['role'])) {
        return;
    }

    if ($_SESSION['role'] === 'admin') {
        redirect(ADMIN_DASHBOARD_URL);
    }

    if ($_SESSION['role'] === 'shipper') {
        redirect(SHIPPER_DASHBOARD_URL);
    }

    redirect(BASE_URL);
}

function logout(?string $redirectUrl = null): void
{
    startSession();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    if ($redirectUrl === null) {
        $redirectUrl = BASE_URL;
    }

    redirect($redirectUrl);
}
