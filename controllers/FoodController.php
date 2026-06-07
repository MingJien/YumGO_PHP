<?php
/**
 * YumGO - Controller điều phối Thực đơn và Chi tiết món ăn
 */

// Chặn truy cập trực tiếp
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

require_once dirname(__DIR__) . '/models/Category.php';
require_once dirname(__DIR__) . '/models/Food.php';

class FoodController {
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
     * Hiển thị trang danh sách món ăn (Thực đơn) kèm Tìm kiếm, Lọc và Phân trang
     */
    public function listing() {
        // 1. Nhận và lọc sạch dữ liệu đầu vào (Input Validation & Sanitization)
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        $categoryId = null;
        if (isset($_GET['category_id'])) {
            $val = filter_var($_GET['category_id'], FILTER_VALIDATE_INT);
            if ($val !== false && $val > 0) {
                $categoryId = $val;
            }
        }

        // Cấu hình phân trang
        $limit = 8; // 8 món trên 1 trang
        $page = 1;
        if (isset($_GET['p'])) {
            $val = filter_var($_GET['p'], FILTER_VALIDATE_INT);
            if ($val !== false && $val > 0) {
                $page = $val;
            }
        }
        $offset = ($page - 1) * $limit;

        // 2. Gom bộ lọc
        $filters = [
            'category_id' => $categoryId,
            'search' => $search
        ];

        // 3. Gọi model truy vấn dữ liệu
        $foods = $this->foodModel->getList($filters, $offset, $limit);
        $totalFoods = $this->foodModel->getTotalCount($filters);
        $totalPages = ceil($totalFoods / $limit);
        if ($totalPages < 1) {
            $totalPages = 1;
        }

        // Lấy danh sách danh mục để hiển thị trong thanh lọc
        $categories = $this->categoryModel->getAllActive();

        // 4. Thiết lập tiêu đề trang
        $title = "Thực Đơn Món Ngon - YumGO";

        // 5. Nhúng Layout và View tương ứng theo Integration Contract
        require_once dirname(__DIR__) . '/views/layouts/header.php';
        require_once dirname(__DIR__) . '/views/user/foods.php';
        require_once dirname(__DIR__) . '/views/layouts/footer.php';
    }

    /**
     * Hiển thị trang chi tiết một món ăn
     */
    public function detail() {
        // 1. Nhận và xác thực ID món ăn
        $id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
        
        if (!$id || $id <= 0) {
            // Chuyển hướng về trang chủ nếu ID không hợp lệ
            header("Location: index.php?page=home");
            exit;
        }

        // 2. Gọi model lấy chi tiết món ăn
        $food = $this->foodModel->getById($id);

        // 3. Xử lý trường hợp không tìm thấy món ăn (hoặc món ăn/danh mục đã bị xóa)
        if (!$food) {
            $title = "Không tìm thấy món ăn - YumGO";
            require_once dirname(__DIR__) . '/views/layouts/header.php';
            require_once dirname(__DIR__) . '/views/user/partials/empty-state.php'; // Hiển thị màn hình trống
            require_once dirname(__DIR__) . '/views/layouts/footer.php';
            return;
        }

        // Lấy danh sách các món ăn liên quan trong cùng danh mục (tối đa 4 món)
        $relatedFoods = $this->foodModel->getRelated((int)$food['category_id'], (int)$food['id'], 4);

        // 4. Đặt tiêu đề cho trang theo tên món
        $title = htmlspecialchars($food['name']) . " - YumGO";

        // 5. Nhúng Layout và View tương ứng theo Integration Contract
        require_once dirname(__DIR__) . '/views/layouts/header.php';
        require_once dirname(__DIR__) . '/views/user/food-detail.php';
        require_once dirname(__DIR__) . '/views/layouts/footer.php';
    }
}
