<?php
/**
 * YumGO - Model Food (Món ăn)
 * Chứa các thao tác nghiệp vụ và truy vấn CSDL liên quan đến bảng foods.
 */

// Chặn truy cập trực tiếp
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

class Food {
    private $db;
    private static $recentTableReady = false;
    private static $favoritesTableReady = false;

    /**
     * Khởi tạo Model với đối tượng kết nối PDO
     * @param PDO $pdo Đối tượng kết nối CSDL
     */
    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Lấy chi tiết món ăn theo ID (Chỉ lấy món chưa bị xóa mềm và thuộc danh mục chưa bị xóa)
     * 
     * @param int $id ID của món ăn
     * @return array|false Thông tin món ăn hoặc false nếu không tìm thấy/đã bị xóa
     */
    public function getById(int $id) {
        try {
            $stmt = $this->db->prepare("
                SELECT f.*, c.name as category_name 
                FROM `foods` f
                INNER JOIN `categories` c ON f.category_id = c.id
                WHERE f.id = :id 
                  AND f.is_deleted = 0 
                  AND c.is_deleted = 0 
                LIMIT 1
            ");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::getById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy danh sách món ăn nổi bật (Featured Foods)
     * Ưu tiên món có nhãn HOT (is_hot = 1) hoặc SALE (is_sale = 1) và còn hàng (is_available = 1)
     * 
     * @param int $limit Số lượng món tối đa muốn lấy
     * @return array Danh sách các món ăn nổi bật
     */
    public function getFeatured(int $limit = 4): array {
        try {
            $stmt = $this->db->prepare("
                SELECT f.*, c.name as category_name 
                FROM `foods` f
                INNER JOIN `categories` c ON f.category_id = c.id
                WHERE f.is_deleted = 0 
                  AND c.is_deleted = 0 
                  AND f.is_available = 1
                ORDER BY f.is_hot DESC, f.is_sale DESC, f.id DESC 
                LIMIT :limit
            ");
            // Vì EMULATE_PREPARES tắt, phải bind PARAM_INT cho LIMIT
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::getFeatured: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lay danh sach mon ban chay gia lap tu du lieu hien co, khong phu thuoc order cua TV3.
     */
    public function getBestSellers(int $limit = 8): array {
        try {
            $stmt = $this->db->prepare("
                SELECT f.*, c.name as category_name
                FROM `foods` f
                INNER JOIN `categories` c ON f.category_id = c.id
                WHERE f.is_deleted = 0
                  AND c.is_deleted = 0
                ORDER BY f.is_available DESC, f.is_hot DESC, f.is_sale DESC, f.id DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::getBestSellers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Goi y tim kiem nhanh theo ten mon, tra ve mon hop le va con hien thi.
     */
    public function searchSuggestions(string $keyword, int $limit = 5): array {
        $keyword = trim(mb_substr($keyword, 0, 80));
        if (mb_strlen($keyword) < 2) {
            return [];
        }

        try {
            $stmt = $this->db->prepare("
                SELECT f.id, f.name, f.price, f.image, f.is_sale, f.discount_percent, f.is_available, c.name as category_name
                FROM `foods` f
                INNER JOIN `categories` c ON f.category_id = c.id
                WHERE f.is_deleted = 0
                  AND c.is_deleted = 0
                  AND f.name LIKE :keyword
                ORDER BY f.is_available DESC, f.is_hot DESC, f.id DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::searchSuggestions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy danh sách món ăn kèm bộ lọc, tìm kiếm và phân trang
     * 
     * @param array $filters Bộ lọc chứa 'category_id' và 'search' (keyword)
     * @param int $offset Điểm bắt đầu phân trang
     * @param int $limit Số lượng bản ghi trên mỗi trang
     * @return array Danh sách các món ăn thỏa mãn điều kiện
     */
    public function getList(array $filters = [], int $offset = 0, int $limit = 8): array {
        try {
            $sql = "
                SELECT f.*, c.name as category_name 
                FROM `foods` f
                INNER JOIN `categories` c ON f.category_id = c.id
                WHERE f.is_deleted = 0 
                  AND c.is_deleted = 0
            ";
            $params = [];

            // Lọc theo Category ID
            if (!empty($filters['category_id'])) {
                $sql .= " AND f.category_id = :category_id";
                $params['category_id'] = $filters['category_id'];
            }

            // Tìm kiếm theo tên món ăn (không phân biệt chữ hoa chữ thường)
            if (!empty($filters['search'])) {
                $sql .= " AND f.name LIKE :search";
                $params['search'] = '%' . trim($filters['search']) . '%';
            }

            if (!empty($filters['availability']) && $filters['availability'] === 'available') {
                $sql .= " AND f.is_available = 1";
            }

            if (!empty($filters['status']) && $filters['status'] === 'sale') {
                $sql .= " AND f.is_sale = 1";
            } elseif (!empty($filters['status']) && $filters['status'] === 'hot') {
                $sql .= " AND f.is_hot = 1";
            }

            $sortMap = [
                'newest' => 'f.id DESC',
                'price_asc' => 'CASE WHEN f.is_sale = 1 THEN f.price * (1 - f.discount_percent / 100) ELSE f.price END ASC, f.id DESC',
                'price_desc' => 'CASE WHEN f.is_sale = 1 THEN f.price * (1 - f.discount_percent / 100) ELSE f.price END DESC, f.id DESC',
                'hot' => 'f.is_hot DESC, f.is_sale DESC, f.id DESC'
            ];
            $sort = $filters['sort'] ?? 'newest';
            $orderBy = $sortMap[$sort] ?? $sortMap['newest'];

            $sql .= " ORDER BY {$orderBy} LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);

            // Bind các tham số lọc nếu có
            if (isset($params['category_id'])) {
                $stmt->bindValue(':category_id', $params['category_id'], PDO::PARAM_INT);
            }
            if (isset($params['search'])) {
                $stmt->bindValue(':search', $params['search'], PDO::PARAM_STR);
            }
            
            // Bind phân trang bắt buộc kiểu INT
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::getList: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy tổng số lượng món ăn thỏa mãn bộ lọc (Phục vụ tính số trang hiển thị)
     * 
     * @param array $filters Bộ lọc chứa 'category_id' và 'search'
     * @return int Tổng số lượng món ăn
     */
    public function getTotalCount(array $filters = []): int {
        try {
            $sql = "
                SELECT COUNT(f.id) 
                FROM `foods` f
                INNER JOIN `categories` c ON f.category_id = c.id
                WHERE f.is_deleted = 0 
                  AND c.is_deleted = 0
            ";
            $params = [];

            if (!empty($filters['category_id'])) {
                $sql .= " AND f.category_id = :category_id";
                $params['category_id'] = $filters['category_id'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND f.name LIKE :search";
                $params['search'] = '%' . trim($filters['search']) . '%';
            }

            if (!empty($filters['availability']) && $filters['availability'] === 'available') {
                $sql .= " AND f.is_available = 1";
            }

            if (!empty($filters['status']) && $filters['status'] === 'sale') {
                $sql .= " AND f.is_sale = 1";
            } elseif (!empty($filters['status']) && $filters['status'] === 'hot') {
                $sql .= " AND f.is_hot = 1";
            }

            $stmt = $this->db->prepare($sql);

            if (isset($params['category_id'])) {
                $stmt->bindValue(':category_id', $params['category_id'], PDO::PARAM_INT);
            }
            if (isset($params['search'])) {
                $stmt->bindValue(':search', $params['search'], PDO::PARAM_STR);
            }

            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::getTotalCount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Lấy nhiều món theo danh sách ID đã validate, giữ đúng thứ tự ID đầu vào.
     */
    public function getByIds(array $ids): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
        if (empty($ids)) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->db->prepare("
                SELECT f.*, c.name as category_name
                FROM `foods` f
                INNER JOIN `categories` c ON f.category_id = c.id
                WHERE f.id IN ($placeholders)
                  AND f.is_deleted = 0
                  AND c.is_deleted = 0
            ");
            foreach ($ids as $index => $id) {
                $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
            }
            $stmt->execute();
            $foods = $stmt->fetchAll();

            $foodsById = [];
            foreach ($foods as $food) {
                $foodsById[(int)$food['id']] = $food;
            }

            $orderedFoods = [];
            foreach ($ids as $id) {
                if (isset($foodsById[$id])) {
                    $orderedFoods[] = $foodsById[$id];
                }
            }

            return $orderedFoods;
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::getByIds: " . $e->getMessage());
            return [];
        }
    }

    public function recordRecentlyViewed(int $userId, int $foodId, int $limit = 8): void {
        if ($userId <= 0 || $foodId <= 0) {
            return;
        }

        try {
            $this->ensureRecentTable();

            $stmt = $this->db->prepare("
                INSERT INTO `user_recent_foods` (`user_id`, `food_id`, `viewed_at`)
                VALUES (:user_id, :food_id, NOW())
                ON DUPLICATE KEY UPDATE `viewed_at` = VALUES(`viewed_at`)
            ");
            $stmt->execute([
                'user_id' => $userId,
                'food_id' => $foodId
            ]);

            $limit = max(1, min($limit, 24));
            $cleanupStmt = $this->db->prepare("
                SELECT `food_id`
                FROM `user_recent_foods`
                WHERE `user_id` = :user_id
                ORDER BY `viewed_at` DESC, `food_id` DESC
                LIMIT 100 OFFSET {$limit}
            ");
            $cleanupStmt->execute(['user_id' => $userId]);
            $oldFoodIds = array_map('intval', $cleanupStmt->fetchAll(PDO::FETCH_COLUMN));

            if (!empty($oldFoodIds)) {
                $placeholders = implode(',', array_fill(0, count($oldFoodIds), '?'));
                $deleteStmt = $this->db->prepare("
                    DELETE FROM `user_recent_foods`
                    WHERE `user_id` = ? AND `food_id` IN ($placeholders)
                ");
                $deleteStmt->bindValue(1, $userId, PDO::PARAM_INT);
                foreach ($oldFoodIds as $index => $oldFoodId) {
                    $deleteStmt->bindValue($index + 2, $oldFoodId, PDO::PARAM_INT);
                }
                $deleteStmt->execute();
            }
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::recordRecentlyViewed: " . $e->getMessage());
        }
    }

    public function getRecentlyViewedForUser(int $userId, int $limit = 6, ?int $excludeFoodId = null): array {
        if ($userId <= 0) {
            return [];
        }

        try {
            $this->ensureRecentTable();

            $limit = max(1, min($limit, 12));
            $whereExclude = '';
            if ($excludeFoodId !== null && $excludeFoodId > 0) {
                $whereExclude = ' AND f.id <> :exclude_food_id';
            }

            $stmt = $this->db->prepare("
                SELECT f.*, c.name as category_name
                FROM `user_recent_foods` urf
                INNER JOIN `foods` f ON urf.food_id = f.id
                INNER JOIN `categories` c ON f.category_id = c.id
                WHERE urf.user_id = :user_id
                  AND f.is_deleted = 0
                  AND c.is_deleted = 0
                  {$whereExclude}
                ORDER BY urf.viewed_at DESC, f.id DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            if ($whereExclude !== '') {
                $stmt->bindValue(':exclude_food_id', $excludeFoodId, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::getRecentlyViewedForUser: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Gợi ý món ăn kèm trong giỏ, ưu tiên khác danh mục với các món đang có.
     */
    public function getCartSuggestions(array $cartFoodIds, int $limit = 4): array {
        $cartFoodIds = array_values(array_unique(array_filter(array_map('intval', $cartFoodIds), fn($id) => $id > 0)));

        try {
            $categoryIds = [];
            if (!empty($cartFoodIds)) {
                $cartPlaceholders = implode(',', array_fill(0, count($cartFoodIds), '?'));
                $stmt = $this->db->prepare("SELECT DISTINCT category_id FROM `foods` WHERE id IN ($cartPlaceholders)");
                foreach ($cartFoodIds as $index => $id) {
                    $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
                }
                $stmt->execute();
                $categoryIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
            }

            $where = "f.is_deleted = 0 AND c.is_deleted = 0 AND f.is_available = 1";
            $params = [];

            if (!empty($cartFoodIds)) {
                $excludePlaceholders = [];
                foreach ($cartFoodIds as $index => $id) {
                    $key = ':exclude_' . $index;
                    $excludePlaceholders[] = $key;
                    $params[$key] = $id;
                }
                $where .= " AND f.id NOT IN (" . implode(',', $excludePlaceholders) . ")";
            }

            $categoryBoost = '0';
            if (!empty($categoryIds)) {
                $categoryPlaceholders = [];
                foreach ($categoryIds as $index => $id) {
                    $key = ':category_' . $index;
                    $categoryPlaceholders[] = $key;
                    $params[$key] = $id;
                }
                $categoryBoost = "CASE WHEN f.category_id NOT IN (" . implode(',', $categoryPlaceholders) . ") THEN 1 ELSE 0 END";
            }

            $stmt = $this->db->prepare("
                SELECT f.*, c.name as category_name
                FROM `foods` f
                INNER JOIN `categories` c ON f.category_id = c.id
                WHERE {$where}
                ORDER BY {$categoryBoost} DESC, f.is_hot DESC, f.is_sale DESC, f.id DESC
                LIMIT :limit
            ");

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::getCartSuggestions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy danh sách món ăn liên quan (cùng danh mục, loại trừ món hiện tại)
     * 
     * @param int $categoryId ID danh mục
     * @param int $excludeId ID món ăn cần loại trừ
     * @param int $limit Số lượng món tối đa
     * @return array Danh sách món ăn liên quan
     */
    public function getRelated(int $categoryId, int $excludeId, int $limit = 4): array {
        try {
            $stmt = $this->db->prepare("
                SELECT f.*, c.name as category_name 
                FROM `foods` f
                INNER JOIN `categories` c ON f.category_id = c.id
                WHERE f.category_id = :category_id 
                  AND f.id <> :exclude_id
                  AND f.is_deleted = 0 
                  AND c.is_deleted = 0 
                  AND f.is_available = 1
                ORDER BY f.is_hot DESC, f.id DESC 
                LIMIT :limit
            ");
            $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::getRelated: " . $e->getMessage());
            return [];
        }
    }

    private function ensureRecentTable(): void {
        if (self::$recentTableReady) {
            return;
        }

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `user_recent_foods` (
                `user_id` INT UNSIGNED NOT NULL,
                `food_id` INT UNSIGNED NOT NULL,
                `viewed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`user_id`, `food_id`),
                INDEX `idx_user_recent_foods_viewed_at` (`user_id`, `viewed_at`),
                CONSTRAINT `fk_user_recent_foods_user`
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
                    ON DELETE CASCADE,
                CONSTRAINT `fk_user_recent_foods_food`
                    FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        self::$recentTableReady = true;
    }

    private function ensureFavoritesTable(): void {
        if (self::$favoritesTableReady) {
            return;
        }

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `user_favorites` (
                `user_id` INT UNSIGNED NOT NULL,
                `food_id` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`user_id`, `food_id`),
                CONSTRAINT `fk_user_favorites_user`
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
                    ON DELETE CASCADE,
                CONSTRAINT `fk_user_favorites_food`
                    FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        self::$favoritesTableReady = true;
    }

    public function getFavoritesForUser(int $userId): array {
        if ($userId <= 0) {
            return [];
        }

        try {
            $this->ensureFavoritesTable();

            $stmt = $this->db->prepare("
                SELECT f.*, c.name as category_name
                FROM `user_favorites` uf
                INNER JOIN `foods` f ON uf.food_id = f.id
                INNER JOIN `categories` c ON f.category_id = c.id
                WHERE uf.user_id = :user_id
                  AND f.is_deleted = 0
                  AND c.is_deleted = 0
                ORDER BY uf.created_at DESC, f.id DESC
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::getFavoritesForUser: " . $e->getMessage());
            return [];
        }
    }

    public function toggleFavorite(int $userId, int $foodId): bool {
        if ($userId <= 0 || $foodId <= 0) {
            return false;
        }

        try {
            $this->ensureFavoritesTable();

            $stmt = $this->db->prepare("
                SELECT 1 FROM `user_favorites`
                WHERE `user_id` = :user_id AND `food_id` = :food_id
                LIMIT 1
            ");
            $stmt->execute([
                'user_id' => $userId,
                'food_id' => $foodId
            ]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                $deleteStmt = $this->db->prepare("
                    DELETE FROM `user_favorites`
                    WHERE `user_id` = :user_id AND `food_id` = :food_id
                ");
                $deleteStmt->execute([
                    'user_id' => $userId,
                    'food_id' => $foodId
                ]);
                return false;
            } else {
                $insertStmt = $this->db->prepare("
                    INSERT INTO `user_favorites` (`user_id`, `food_id`, `created_at`)
                    VALUES (:user_id, :food_id, NOW())
                ");
                $insertStmt->execute([
                    'user_id' => $userId,
                    'food_id' => $foodId
                ]);
                return true;
            }
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::toggleFavorite: " . $e->getMessage());
            return false;
        }
    }

    public function syncFavoritesForUser(int $userId, array $localIds): array {
        if ($userId <= 0) {
            return [];
        }

        try {
            $this->ensureFavoritesTable();

            $localIds = array_values(array_unique(array_filter(array_map('intval', $localIds), fn($id) => $id > 0)));

            if (!empty($localIds)) {
                $stmt = $this->db->prepare("SELECT `food_id` FROM `user_favorites` WHERE `user_id` = :user_id");
                $stmt->execute(['user_id' => $userId]);
                $dbIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

                $newIds = array_diff($localIds, $dbIds);

                if (!empty($newIds)) {
                    $insertStmt = $this->db->prepare("
                        INSERT IGNORE INTO `user_favorites` (`user_id`, `food_id`, `created_at`)
                        VALUES (:user_id, :food_id, NOW())
                    ");
                    foreach ($newIds as $foodId) {
                        $checkStmt = $this->db->prepare("SELECT 1 FROM `foods` WHERE `id` = :food_id AND `is_deleted` = 0 LIMIT 1");
                        $checkStmt->execute(['food_id' => $foodId]);
                        if ($checkStmt->fetchColumn()) {
                            $insertStmt->execute([
                                'user_id' => $userId,
                                'food_id' => $foodId
                            ]);
                        }
                    }
                }
            }

            $stmt = $this->db->prepare("
                SELECT uf.food_id 
                FROM `user_favorites` uf
                INNER JOIN `foods` f ON uf.food_id = f.id
                INNER JOIN `categories` c ON f.category_id = c.id
                WHERE uf.user_id = :user_id
                  AND f.is_deleted = 0
                  AND c.is_deleted = 0
                ORDER BY uf.created_at DESC
            ");
            $stmt->execute(['user_id' => $userId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (PDOException $e) {
            error_log("Lỗi trong Food::syncFavoritesForUser: " . $e->getMessage());
            return [];
        }
    }
}
