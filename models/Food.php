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

            // Sắp xếp mặc định: Mới nhất lên đầu
            $sql .= " ORDER BY f.id DESC LIMIT :limit OFFSET :offset";

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
}

