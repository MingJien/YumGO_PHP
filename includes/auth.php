<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserCart.php';

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

// User login/register foundation
function registerUser(string $name, string $phone, string $password, ?string $address = null): bool
{
    startSession();

    $pdo = Database::getConnection();
    $userModel = new User($pdo);

    if ($userModel->phoneExists($phone)) {
        return false;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $userId = $userModel->create($name, $phone, $passwordHash, $address);

    session_regenerate_id(true);

    $_SESSION['role'] = 'user';
    $_SESSION['user'] = [
        'id' => $userId,
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
    ];
    syncUserCartAfterLogin($pdo, $userId);

    return true;
}

function loginUser(string $phone, string $password): bool
{
    startSession();

    $pdo = Database::getConnection();
    $userModel = new User($pdo);
    $user = $userModel->activeByPhone($phone);

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['role'] = 'user';
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'phone' => $user['phone'],
        'address' => $user['address'],
    ];
    syncUserCartAfterLogin($pdo, (int)$user['id']);

    return true;
}

function syncUserCartAfterLogin(PDO $pdo, int $userId): void
{
    startSession();

    try {
        $cartModel = new UserCart($pdo);
        $savedCart = $cartModel->getCart($userId);
    } catch (Throwable $exception) {
        error_log('Could not load saved user cart: ' . $exception->getMessage());
        return;
    }
    $sessionCart = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
    $mergedCart = $savedCart;

    foreach ($sessionCart as $foodId => $item) {
        $foodId = (int)($item['food_id'] ?? $foodId);
        $quantity = (int)($item['quantity'] ?? 0);

        if ($foodId <= 0 || $quantity <= 0) {
            continue;
        }

        if (isset($mergedCart[$foodId])) {
            $mergedCart[$foodId]['quantity'] += $quantity;
        } else {
            $mergedCart[$foodId] = [
                'food_id' => $foodId,
                'quantity' => $quantity,
            ];
        }
    }

    $_SESSION['cart'] = $mergedCart;
    try {
        $cartModel->replaceCart($userId, $mergedCart);
    } catch (Throwable $exception) {
        error_log('Could not persist merged user cart: ' . $exception->getMessage());
    }
}

function persistCurrentUserCart(): void
{
    startSession();

    if (($_SESSION['role'] ?? null) !== 'user' || empty($_SESSION['user']['id'])) {
        return;
    }

    $cart = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
    try {
        $cartModel = new UserCart(Database::getConnection());
        $cartModel->replaceCart((int)$_SESSION['user']['id'], $cart);
    } catch (Throwable $exception) {
        error_log('Could not persist user cart before logout: ' . $exception->getMessage());
    }
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

function requireUserLogin(): void
{
    if (!isRole('user')) {
        redirect(BASE_URL . '/index.php?page=login');
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

    if ($_SESSION['role'] === 'user') {
        redirect(BASE_URL . '/index.php?page=home');
    }

    redirect(BASE_URL);
}

function logout(?string $redirectUrl = null): void
{
    startSession();
    persistCurrentUserCart();

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
