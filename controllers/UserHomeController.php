<?php
/**
 * YumGO - Controller điều phối trang chủ user.
 */

if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

require_once dirname(__DIR__) . '/models/Category.php';
require_once dirname(__DIR__) . '/models/Food.php';

class UserHomeController {
    private $categoryModel;
    private $foodModel;

    public function __construct(PDO $pdo) {
        $this->categoryModel = new Category($pdo);
        $this->foodModel = new Food($pdo);
    }

    public function index() {
        $categories = $this->categoryModel->getAllActive();
        $featuredFoods = $this->foodModel->getFeatured(8);
        $bestSellerFoods = $this->foodModel->getBestSellers(8);
        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId > 0) {
            $recentFoods = $this->foodModel->getRecentlyViewedForUser($currentUserId, 6);
        } else {
            $recentIds = $this->parseIdList($_GET['recent_ids'] ?? '');
            $recentFoods = $this->foodModel->getByIds(array_slice($recentIds, 0, 6));
        }

        $testimonials = [
            [
                'name' => 'Nguyễn Minh Anh',
                'role' => 'Sinh viên',
                'stars' => 5,
                'content' => 'Đồ ăn đến nhanh hơn mong đợi. Gà rán phủ sốt Hàn Quốc vẫn còn nóng giòn, hương vị đậm đà rất đáng thử.',
                'avatar_letter' => 'A',
                'avatar_url' => 'https://i.pravatar.cc/160?img=47'
            ],
            [
                'name' => 'Lê Thanh Hải',
                'role' => 'Lập trình viên',
                'stars' => 5,
                'content' => 'Giao diện đặt món nhanh và mượt. Side drawer xem nhanh giỏ hàng rất tiện, không phải chuyển trang liên tục.',
                'avatar_letter' => 'H',
                'avatar_url' => 'https://i.pravatar.cc/160?img=12'
            ],
            [
                'name' => 'Trần Thu Thảo',
                'role' => 'Nhân viên văn phòng',
                'stars' => 5,
                'content' => 'Từ khi biết đến YumGO, việc đặt bữa trưa đơn giản hơn nhiều. Giao đúng 20 phút, món vẫn nóng và đóng gói gọn.',
                'avatar_letter' => 'T',
                'avatar_url' => 'https://i.pravatar.cc/160?img=32'
            ]
        ];

        $title = 'YumGO - Đặt món ăn ngon, giao hàng siêu tốc';
        $metaDescription = 'YumGO giúp bạn khám phá món ngon, thêm giỏ nhanh và đặt đồ ăn dễ dàng trên giao diện mobile-first.';
        $metaKeywords = 'YumGO, đặt món ăn online, món ngon, giao hàng nhanh, food delivery';
        $canonicalUrl = BASE_URL . '/index.php?page=home';

        require_once dirname(__DIR__) . '/views/layouts/header.php';
        require_once dirname(__DIR__) . '/views/user/home.php';
        require_once dirname(__DIR__) . '/views/layouts/footer.php';
    }

    private function parseIdList(string $rawIds): array {
        if ($rawIds === '') {
            return [];
        }

        $ids = [];
        foreach (explode(',', $rawIds) as $rawId) {
            $id = filter_var(trim($rawId), FILTER_VALIDATE_INT);
            if ($id !== false && $id > 0) {
                $ids[] = (int)$id;
            }
        }

        return array_slice(array_values(array_unique($ids)), 0, 8);
    }

    private function getCurrentUserId(): int {
        if (!empty($_SESSION['user']['id'])) {
            return (int)$_SESSION['user']['id'];
        }

        return 0;
    }
}
