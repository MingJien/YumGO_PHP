<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/database.php';

$page = $_GET['page'] ?? 'home';

if ($page === 'apply-voucher') {
    require_once __DIR__ . '/app/controllers/VoucherController.php';
    VoucherController::applyJson(
        Database::getConnection(),
        trim((string)($_GET['code'] ?? $_POST['code'] ?? '')),
        (float)($_GET['subtotal'] ?? $_POST['subtotal'] ?? 0)
    );
    exit;
}

if (file_exists(__DIR__ . '/app/views/includes/header.php')) {
    require_once __DIR__ . '/app/views/includes/header.php';
}

switch ($page) {
    case 'home':
        if (file_exists(__DIR__ . '/app/views/home.php')) {
            require_once __DIR__ . '/app/views/home.php';
        } else {
            echo 'Chào mừng đến với YumGO! (Trang chủ đang chờ TV2 dựng)';
        }
        break;

    case 'foods':
        if (file_exists(__DIR__ . '/app/views/foods.php')) {
            require_once __DIR__ . '/app/views/foods.php';
        } else {
            echo 'Danh sách món ăn đang chờ TV2 dựng';
        }
        break;

    case 'food-detail':
        if (file_exists(__DIR__ . '/app/views/food_detail.php')) {
            require_once __DIR__ . '/app/views/food_detail.php';
        } else {
            echo 'Chi tiết món ăn đang chờ TV2 dựng';
        }
        break;

    case 'cart':
        if (file_exists(__DIR__ . '/app/views/cart.php')) {
            require_once __DIR__ . '/app/views/cart.php';
        } else {
            echo 'Giỏ hàng đang chờ TV2 dựng';
        }
        break;

    default:
        http_response_code(404);
        echo '404 - Trang không tồn tại';
        break;
}

if (file_exists(__DIR__ . '/app/views/includes/footer.php')) {
    require_once __DIR__ . '/app/views/includes/footer.php';
}
