Welcome to YumGO! (Trang chủ đang chờ TV2 dựng)
";
            }
            break;

        case 'food-detail':
            if (file_exists(__DIR__ . '/app/views/food_detail.php')) {
                require_once __DIR__ . '/app/views/food_detail.php';
            }
            break;

        case 'cart':
            if (file_exists(__DIR__ . '/app/views/cart.php')) {
                require_once __DIR__ . '/app/views/cart.php';
            }
            break;

        default:
            echo "404 - Trang không tồn tại
";
            break;
    }

    // 3. NHÚNG FOOTER CHUNG CỦA KHÁCH HÀNG
    if (file_exists(__DIR__ . '/app/views/includes/footer.php')) {
        require_once __DIR__ . '/app/views/includes/footer.php';
    }
} else {
    // Xử lý các trang đặc biệt không dùng chung layout khách hàng (Ví dụ: các trang xử lý API hoặc chuyển hướng trực tiếp)
    echo "Trang phân hệ riêng hoặc API";
}