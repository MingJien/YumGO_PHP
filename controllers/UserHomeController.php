<?php
/**
 * YumGO - Controller điều phối Trang chủ User
 */

// Chặn truy cập trực tiếp
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

require_once dirname(__DIR__) . '/models/Category.php';
require_once dirname(__DIR__) . '/models/Food.php';

class UserHomeController {
    private $categoryModel;
    private $foodModel;

    /**
     * Khởi tạo Controller với kết nối database
     * @param PDO $pdo Đối tượng kết nối CSDL
     */
    public function __construct(PDO $pdo) {
        $this->categoryModel = new Category($pdo);
        $this->foodModel = new Food($pdo);
    }

    /**
     * Hiển thị trang chủ khách hàng
     */
    public function index() {
        // 1. Lấy danh sách danh mục hoạt động
        $categories = $this->categoryModel->getAllActive();

        // 2. Lấy danh sách món ăn nổi bật (mặc định lấy tối đa 4 món)
        $featuredFoods = $this->foodModel->getFeatured(4);

        // Giả lập danh sách nhận xét khách hàng (Testimonials) cho trang chủ
        $testimonials = [
            [
                'name' => 'Nguyễn Minh Anh',
                'role' => 'Sinh viên',
                'stars' => 5,
                'content' => 'Đồ ăn đến nhanh hơn mong đợi! Gà rán phủ sốt Hàn Quốc vẫn còn nóng hổi giòn rụm, hương vị đậm đà rất đáng thử.',
                'avatar_letter' => 'A'
            ],
            [
                'name' => 'Lê Thanh Hải',
                'role' => 'Lập trình viên',
                'stars' => 5,
                'content' => 'Giao diện đặt món cực kỳ nhanh và mượt mà. Đặc biệt là Side-Drawer xem nhanh giỏ hàng rất tiện lợi, không phải chuyển trang liên tục.',
                'avatar_letter' => 'H'
            ],
            [
                'name' => 'Trần Thu Thảo',
                'role' => 'Nhân viên văn phòng',
                'stars' => 5,
                'content' => 'Từ khi biết đến YumGO, việc đặt bữa trưa trở nên đơn giản hơn nhiều. Giao đúng 20 phút nóng hổi, nhân viên ship rất thân thiện.',
                'avatar_letter' => 'T'
            ]
        ];

        // 3. Đặt tiêu đề cho trang
        $title = "YumGO - Đặt Món Ăn Ngon, Giao Hàng Siêu Tốc";

        // 4. Nhúng Layout và View tương ứng theo Integration Contract
        require_once dirname(__DIR__) . '/views/layouts/header.php';
        require_once dirname(__DIR__) . '/views/user/home.php';
        require_once dirname(__DIR__) . '/views/layouts/footer.php';
    }
}
