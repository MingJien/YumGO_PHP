<?php
/**
 * YumGO - Điểm khởi tạo và Điều hướng chính (Main Router)
 * Tệp này điều phối mọi yêu cầu truy cập thông qua tham số $_GET['page'].
 */

// 1. Khởi chạy Session toàn cục
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Nhúng hệ thống xác thực và trợ giúp (tự động nhúng database.php và config.php)
require_once __DIR__ . '/includes/auth.php';

// 3. Khởi tạo đối tượng PDO cục bộ từ lớp Database (Singleton) của TV1
// Điều này giúp giữ nguyên tính tương thích cho các Model và Controller hiện tại sử dụng biến $pdo.
try {
    $pdo = Database::getConnection();
} catch (Exception $e) {
    die("Lỗi kết nối cơ sở dữ liệu: " . htmlspecialchars($e->getMessage()));
}

// 4. Đọc tham số điều hướng, mặc định là trang chủ ('home')
$page = isset($_GET['page']) ? trim($_GET['page']) : 'home';

// 5. Định tuyến các yêu cầu (Routing Logic)
switch ($page) {
    
    // Luồng Trang chủ
    case 'home':
        require_once __DIR__ . '/controllers/UserHomeController.php';
        $controller = new UserHomeController($pdo);
        $controller->index();
        break;

    // Luồng Thực đơn / Danh sách món
    case 'foods':
        require_once __DIR__ . '/controllers/FoodController.php';
        $controller = new FoodController($pdo);
        $controller->listing();
        break;

    // Luồng Chi tiết món ăn
    case 'food-detail':
        require_once __DIR__ . '/controllers/FoodController.php';
        $controller = new FoodController($pdo);
        $controller->detail();
        break;

    // Luồng Xem Giỏ hàng
    case 'cart':
        require_once __DIR__ . '/controllers/CartController.php';
        $controller = new CartController($pdo);
        $controller->index();
        break;

    // Các luồng POST/GET xử lý Giỏ hàng (Cart Actions)
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

    // Các luồng thuộc Thành viên 3 & 4 (Dưới góc độ tích hợp)
    case 'checkout':
    case 'order-history':
    case 'admin-dashboard':
        // Hiển thị màn hình chờ tích hợp đẹp mắt cho các chức năng của thành viên khác
        $title = "Đang chờ tích hợp - YumGO";
        require_once __DIR__ . '/views/layouts/header.php';
        
        $actor = ($page === 'admin-dashboard') ? 'Thành viên 4 (Admin & Shipper)' : 'Thành viên 3 (Checkout & Order)';
        $featureName = ($page === 'checkout') ? 'Thanh toán đơn hàng (Checkout)' : (($page === 'order-history') ? 'Lịch sử đơn hàng' : 'Bảng điều khiển quản trị (Admin Dashboard)');
        
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
                Quay lại Trang chủ
            </a>
        </div>';
        
        require_once __DIR__ . '/views/layouts/footer.php';
        break;

    // Chuyển hướng về trang chủ nếu gõ sai route (404 Fallback)
    default:
        header("Location: index.php?page=home");
        exit;
}