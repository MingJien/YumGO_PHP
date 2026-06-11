<?php
/**
 * YumGO - Controller điều phối thực đơn và chi tiết món ăn
 */

if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

require_once dirname(__DIR__) . '/models/Category.php';
require_once dirname(__DIR__) . '/models/Food.php';

class FoodController {
    private $categoryModel;
    private $foodModel;

    public function __construct(PDO $pdo) {
        $this->categoryModel = new Category($pdo);
        $this->foodModel = new Food($pdo);
    }

    public function listing() {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $search = mb_substr($search, 0, 80);

        $categoryId = null;
        if (isset($_GET['category_id'])) {
            $val = filter_var($_GET['category_id'], FILTER_VALIDATE_INT);
            if ($val !== false && $val > 0) {
                $categoryId = $val;
            }
        }

        $allowedAvailability = ['all', 'available'];
        $availability = isset($_GET['availability']) ? trim($_GET['availability']) : 'all';
        if (!in_array($availability, $allowedAvailability, true)) {
            $availability = 'all';
        }

        $allowedStatus = ['all', 'sale', 'hot'];
        $status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'all';
        }

        $allowedSort = ['newest', 'price_asc', 'price_desc', 'hot'];
        $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'newest';
        }

        $limit = 8;
        $page = 1;
        if (isset($_GET['p'])) {
            $val = filter_var($_GET['p'], FILTER_VALIDATE_INT);
            if ($val !== false && $val > 0) {
                $page = $val;
            }
        }
        $offset = ($page - 1) * $limit;

        $filters = [
            'category_id' => $categoryId,
            'search' => $search,
            'availability' => $availability,
            'status' => $status,
            'sort' => $sort
        ];

        $foods = $this->foodModel->getList($filters, $offset, $limit);
        $totalFoods = $this->foodModel->getTotalCount($filters);
        $totalPages = ceil($totalFoods / $limit);
        if ($totalPages < 1) {
            $totalPages = 1;
        }

        $categories = $this->categoryModel->getAllActive();
        $title = 'Thực đơn món ngon - YumGO';
        $metaDescription = 'Khám phá thực đơn YumGO, tìm kiếm món ăn, lọc món còn hàng, HOT, SALE và sắp xếp theo giá.';
        $metaKeywords = 'thực đơn YumGO, tìm món ăn, món hot, món sale, đặt đồ ăn';
        $canonicalUrl = BASE_URL . '/index.php?page=foods';

        require_once dirname(__DIR__) . '/views/layouts/header.php';
        require_once dirname(__DIR__) . '/views/user/foods.php';
        require_once dirname(__DIR__) . '/views/layouts/footer.php';
    }

    public function favorites() {
        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId > 0) {
            $favoriteFoods = $this->foodModel->getFavoritesForUser($currentUserId);
        } else {
            $ids = $this->parseIdList($_GET['ids'] ?? '');
            $favoriteFoods = $this->foodModel->getByIds($ids);
        }

        // Phân trang món yêu thích
        $limit = 8;
        $totalFoods = count($favoriteFoods);
        $totalPages = ceil($totalFoods / $limit);
        if ($totalPages < 1) {
            $totalPages = 1;
        }

        $page = isset($_GET['p']) ? filter_var($_GET['p'], FILTER_VALIDATE_INT) : 1;
        if (!$page || $page < 1) {
            $page = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $limit;
        $favoriteFoodsPage = array_slice($favoriteFoods, $offset, $limit);

        $title = 'Món yêu thích - YumGO';
        $metaDescription = 'Danh sách món yêu thích của bạn trên YumGO, lưu bằng trình duyệt và không cần đăng nhập.';
        $metaKeywords = 'món yêu thích YumGO, favorite foods, món ăn đã lưu';
        $canonicalUrl = BASE_URL . '/index.php?page=favorites';

        require_once dirname(__DIR__) . '/views/layouts/header.php';
        require_once dirname(__DIR__) . '/views/user/favorites.php';
        require_once dirname(__DIR__) . '/views/layouts/footer.php';
    }

    public function toggleFavorite() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $foodId = isset($_POST['food_id']) ? filter_var($_POST['food_id'], FILTER_VALIDATE_INT) : 0;
        $currentUserId = $this->getCurrentUserId();

        if ($currentUserId <= 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để lưu món yêu thích.']);
            exit;
        }

        if ($foodId <= 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Món ăn không hợp lệ.']);
            exit;
        }

        $isFavorite = $this->foodModel->toggleFavorite($currentUserId, $foodId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'is_favorite' => $isFavorite,
            'message' => $isFavorite ? 'Đã lưu món vào yêu thích.' : 'Đã bỏ món khỏi yêu thích.'
        ]);
        exit;
    }

    public function syncFavorites() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId <= 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $rawIds = isset($_POST['ids']) ? trim((string)$_POST['ids']) : '';
        $localIds = $this->parseIdList($rawIds);

        $mergedIds = $this->foodModel->syncFavoritesForUser($currentUserId, $localIds);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'ids' => $mergedIds
        ]);
        exit;
    }

    public function suggest() {
        $keyword = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $foods = $this->foodModel->searchSuggestions($keyword, 5);
        $suggestions = [];

        foreach ($foods as $food) {
            $price = (float)$food['price'];
            if ((int)$food['is_sale'] === 1) {
                $price = $price * (1 - ((int)$food['discount_percent'] / 100));
            }

            $suggestions[] = [
                'id' => (int)$food['id'],
                'name' => $food['name'],
                'category_name' => $food['category_name'],
                'price' => number_format($price, 0, ',', '.') . 'đ',
                'is_available' => (int)$food['is_available'] === 1,
                'url' => 'index.php?page=food-detail&id=' . (int)$food['id']
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'items' => $suggestions], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function detail() {
        $id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

        if (!$id || $id <= 0) {
            header('Location: index.php?page=home');
            exit;
        }

        $food = $this->foodModel->getById($id);

        if (!$food) {
            $title = 'Không tìm thấy món ăn - YumGO';
            $emptyTitle = 'Không tìm thấy món ăn';
            $emptyDesc = 'Món ăn bạn yêu cầu không tồn tại hoặc đã bị ẩn khỏi hệ thống.';
            $emptyBtnText = 'Quay lại thực đơn';
            $emptyBtnUrl = 'index.php?page=foods';
            require_once dirname(__DIR__) . '/views/layouts/header.php';
            require_once dirname(__DIR__) . '/views/user/partials/empty-state.php';
            require_once dirname(__DIR__) . '/views/layouts/footer.php';
            return;
        }

        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId > 0) {
            $this->foodModel->recordRecentlyViewed($currentUserId, (int)$food['id'], 8);
        }

        $relatedFoods = $this->foodModel->getRelated((int)$food['category_id'], (int)$food['id'], 4);
        if ($currentUserId > 0) {
            $recentFoods = $this->foodModel->getRecentlyViewedForUser($currentUserId, 4, (int)$food['id']);
        } else {
            $recentIds = array_filter($this->parseIdList($_GET['recent_ids'] ?? ''), fn($recentId) => (int)$recentId !== (int)$food['id']);
            $recentFoods = $this->foodModel->getByIds(array_slice($recentIds, 0, 4));
        }

        $title = htmlspecialchars($food['name']) . ' - YumGO';
        $plainDescription = trim(strip_tags((string)($food['description'] ?? '')));
        if ($plainDescription === '') {
            $plainDescription = 'Đặt ' . $food['name'] . ' tại YumGO, món nóng giao nhanh và giá rõ ràng.';
        }
        $metaDescription = mb_substr($plainDescription, 0, 155);
        $metaKeywords = $food['name'] . ', ' . ($food['category_name'] ?? 'món ăn') . ', YumGO, đặt món ăn';
        $canonicalUrl = BASE_URL . '/index.php?page=food-detail&id=' . (int)$food['id'];

        $imageUrl = BASE_URL . '/uploads/banners/hero_combo.png';
        if (!empty($food['image']) && file_exists(PATH_ROOT . '/uploads/foods/' . $food['image'])) {
            $imageUrl = BASE_URL . '/uploads/foods/' . rawurlencode($food['image']);
        }
        $offerPrice = (float)$food['price'];
        if ((int)$food['is_sale'] === 1) {
            $offerPrice = $offerPrice * (1 - ((int)$food['discount_percent'] / 100));
        }
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $food['name'],
            'image' => $imageUrl,
            'description' => $plainDescription,
            'category' => $food['category_name'] ?? 'Món ăn',
            'brand' => [
                '@type' => 'Brand',
                'name' => 'YumGO'
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => number_format($offerPrice, 0, '.', ''),
                'priceCurrency' => 'VND',
                'availability' => ((int)$food['is_available'] === 1) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $canonicalUrl
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'reviewCount' => '128'
            ]
        ];

        require_once dirname(__DIR__) . '/views/layouts/header.php';
        require_once dirname(__DIR__) . '/views/user/food-detail.php';
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

        return array_slice(array_values(array_unique($ids)), 0, 24);
    }

    private function getCurrentUserId(): int {
        if (!empty($_SESSION['user']['id'])) {
            return (int)$_SESSION['user']['id'];
        }

        return 0;
    }
}
