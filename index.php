<?php
/**
 * YumGO - Main router.
 */

$sessionDir = __DIR__ . '/storage/sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0775, true);
}
if (is_dir($sessionDir) && is_writable($sessionDir)) {
    session_save_path($sessionDir);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/auth.php';

try {
    $pdo = Database::getConnection();
} catch (Exception $e) {
    die('Lỗi kết nối cơ sở dữ liệu: ' . htmlspecialchars($e->getMessage()));
}

// Đồng bộ giỏ hàng từ CSDL vào Session nếu đã đăng nhập để đồng bộ giữa các trình duyệt/thiết bị khác nhau
if (isset($_SESSION['role']) && $_SESSION['role'] === 'user' && !empty($_SESSION['user']['id'])) {
    require_once __DIR__ . '/models/UserCart.php';
    try {
        $userCartModel = new UserCart($pdo);
        $_SESSION['cart'] = $userCartModel->getCart((int)$_SESSION['user']['id']);
    } catch (Throwable $exception) {
        error_log('Could not load user cart in router: ' . $exception->getMessage());
    }
}

$page = isset($_GET['page']) ? trim($_GET['page']) : 'home';

switch ($page) {
    case 'home':
        require_once __DIR__ . '/controllers/UserHomeController.php';
        $controller = new UserHomeController($pdo);
        $controller->index();
        break;

    case 'foods':
        require_once __DIR__ . '/controllers/FoodController.php';
        $controller = new FoodController($pdo);
        $controller->listing();
        break;

    case 'food-detail':
        require_once __DIR__ . '/controllers/FoodController.php';
        $controller = new FoodController($pdo);
        $controller->detail();
        break;

    case 'favorites':
        require_once __DIR__ . '/controllers/FoodController.php';
        $controller = new FoodController($pdo);
        $controller->favorites();
        break;

    case 'favorite-toggle':
        require_once __DIR__ . '/controllers/FoodController.php';
        $controller = new FoodController($pdo);
        $controller->toggleFavorite();
        break;

    case 'favorite-sync':
        require_once __DIR__ . '/controllers/FoodController.php';
        $controller = new FoodController($pdo);
        $controller->syncFavorites();
        break;

    case 'login':
        require_once __DIR__ . '/controllers/UserAuthController.php';
        $controller = new UserAuthController();
        $controller->login();
        break;

    case 'register':
        require_once __DIR__ . '/controllers/UserAuthController.php';
        $controller = new UserAuthController();
        $controller->register();
        break;

    case 'logout':
        require_once __DIR__ . '/controllers/UserAuthController.php';
        $controller = new UserAuthController();
        $controller->logout();
        break;

    case 'account':
        require_once __DIR__ . '/controllers/UserAccountController.php';
        $controller = new UserAccountController($pdo);
        $controller->index();
        break;

    case 'account-update':
        require_once __DIR__ . '/controllers/UserAccountController.php';
        $controller = new UserAccountController($pdo);
        $controller->updateProfile();
        break;

    case 'account-password':
        require_once __DIR__ . '/controllers/UserAccountController.php';
        $controller = new UserAccountController($pdo);
        $controller->updatePassword();
        break;

    case 'food-suggest':
        require_once __DIR__ . '/controllers/FoodController.php';
        $controller = new FoodController($pdo);
        $controller->suggest();
        break;

    case 'cart':
        require_once __DIR__ . '/controllers/CartController.php';
        $controller = new CartController($pdo);
        $controller->index();
        break;

    case 'cart-add':
        require_once __DIR__ . '/controllers/CartController.php';
        $controller = new CartController($pdo);
        $controller->add();
        break;

    case 'cart-update':
        require_once __DIR__ . '/controllers/CartController.php';
        $controller = new CartController($pdo);
        $controller->update();
        break;

    case 'cart-remove':
        require_once __DIR__ . '/controllers/CartController.php';
        $controller = new CartController($pdo);
        $controller->remove();
        break;

    case 'cart-drawer':
        require_once __DIR__ . '/controllers/CartController.php';
        $controller = new CartController($pdo);
        $controller->drawer();
        break;

    case 'support':
        $title = 'Hỗ trợ khách hàng - YumGO';
        $metaDescription = 'Trung tâm hỗ trợ YumGO với FAQ đặt món, COD, Banking, hủy đơn và thông tin liên hệ demo.';
        $metaKeywords = 'hỗ trợ YumGO, FAQ đặt món, COD, Banking, hủy đơn';
        $canonicalUrl = BASE_URL . '/index.php?page=support';
        require_once __DIR__ . '/views/layouts/header.php';
        require_once __DIR__ . '/views/user/support.php';
        require_once __DIR__ . '/views/layouts/footer.php';
        break;

    case 'checkout':
        require_once __DIR__ . '/controllers/OrderController.php';
        $controller = new OrderController($pdo);
        $controller->checkout();
        break;

    case 'process-checkout':
        require_once __DIR__ . '/controllers/OrderController.php';
        $controller = new OrderController($pdo);
        $controller->processCheckout();
        break;

    case 'order-history':
        require_once __DIR__ . '/controllers/OrderController.php';
        $controller = new OrderController($pdo);
        $controller->history();
        break;

    case 'order-search':
        require_once __DIR__ . '/controllers/OrderController.php';
        $controller = new OrderController($pdo);
        $controller->search();
        break;

    case 'order-tracking':
        require_once __DIR__ . '/controllers/OrderController.php';
        $controller = new OrderController($pdo);
        $controller->tracking();
        break;

    case 'cancel-order':
        require_once __DIR__ . '/controllers/OrderController.php';
        $controller = new OrderController($pdo);
        $controller->cancel();
        break;

    case 'order-edit':
        require_once __DIR__ . '/controllers/OrderController.php';
        $controller = new OrderController($pdo);
        $controller->edit();
        break;

    case 'order-invoice':
        require_once __DIR__ . '/controllers/OrderController.php';
        $controller = new OrderController($pdo);
        $controller->exportInvoice();
        break;

    case 'reorder':
        require_once __DIR__ . '/controllers/OrderController.php';
        $controller = new OrderController($pdo);
        $controller->reorder();
        break;

    case 'admin-dashboard':
        $title = 'Đang chờ tích hợp - YumGO';
        require_once __DIR__ . '/views/layouts/header.php';

        $actor = ($page === 'admin-dashboard') ? 'Thành viên 4 (Admin & Shipper)' : 'Thành viên 3 (Checkout & Order)';
        $featureName = ($page === 'checkout') ? 'Thanh toán đơn hàng' : (($page === 'order-history') ? 'Lịch sử đơn hàng' : 'Bảng điều khiển quản trị');

        echo '
        <div class="container py-5 text-center d-flex flex-column align-items-center justify-content-center" style="min-height: 50vh;">
            <div class="mb-4 text-warning" style="opacity: 0.9; font-size: 60px;">
                <i class="bi bi-git"></i>
            </div>
            <h3 class="fw-bold mb-2">Tính năng đang phát triển độc lập</h3>
            <p class="text-secondary mb-4 body-md mx-auto" style="max-width: 480px;">
                Trang <code>' . htmlspecialchars($page) . '</code> (<b>' . $featureName . '</b>) do <b>' . $actor . '</b> đảm nhận. Chức năng này sẽ hoạt động sau khi ghép mã nguồn chung của nhóm.
            </p>
            <a href="index.php?page=home" class="btn btn-primary-yumgo px-4 py-2 fs-6">
                Quay lại trang chủ
            </a>
        </div>';

        require_once __DIR__ . '/views/layouts/footer.php';
        break;

    default:
        header('Location: index.php?page=home');
        exit;
}