<?php
/**
 * YumGO - Model Category (Danh mục món ăn)
 * Chứa các thao tác nghiệp vụ và truy vấn CSDL liên quan đến bảng categories.
 */

// Chặn truy cập trực tiếp
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

class Category {
    private $db;

    /**
     * Khởi tạo Model với đối tượng kết nối PDO
     * @param PDO $pdo Đối tượng kết nối CSDL
     */
    public function __construct(PDO $pdo) {
        $this->db = $pdo;
    }

    /**
     * Lấy tất cả danh mục chưa bị xóa mềm (is_deleted = 0)
     * Phục vụ hiển thị thanh trượt danh mục (Category Strip) trên trang chủ và danh sách món
     * 
     * @return array Danh sách các danh mục
     */
    public function getAllActive(): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM `categories` WHERE `is_deleted` = 0 ORDER BY `id` ASC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Lỗi trong Category::getAllActive: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy chi tiết danh mục theo ID (Chỉ lấy danh mục chưa bị xóa mềm)
     * 
     * @param int $id ID của danh mục
     * @return array|false Mảng thông tin danh mục hoặc false nếu không tồn tại/đã bị xóa
     */
    public function getById(int $id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM `categories` WHERE `id` = :id AND `is_deleted` = 0 LIMIT 1");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Lỗi trong Category::getById: " . $e->getMessage());
            return false;
        }
    }
}
