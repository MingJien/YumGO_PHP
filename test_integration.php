<?php
/**
 * YumGO - Web-based Integration Verification Page
 * Chạy trên Trình duyệt để kiểm tra tính đúng đắn của Database & Models.
 */

// 1. Khởi chạy session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Nhúng các file config & database
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/models/Category.php';
require_once __DIR__ . '/models/Food.php';

try {
    $pdo = Database::getConnection();
} catch (Exception $e) {
    $pdo = null;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YumGO - Kiểm Thử Tích Hợp</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
    <div class="container">
        <div class="card shadow-sm mx-auto" style="max-width: 800px;">
            <div class="card-header bg-dark text-white py-3">
                <h4 class="m-0 fw-bold"><i class="bi bi-shield-check me-2 text-success"></i>YumGO - Bảng Kiểm Thử Tích Hợp (Dev Mode)</h4>
            </div>
            <div class="card-body">
                
                <!-- 1. Database Connection Check -->
                <div class="mb-4">
                    <h5 class="fw-bold border-bottom pb-2 text-primary">1. Kiểm tra Kết nối Database (PDO)</h5>
                    <?php if (isset($pdo) && $pdo instanceof PDO): ?>
                        <div class="alert alert-success d-flex align-items-center py-2" role="alert">
                            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                            <div>
                                Kết nối thành công! Hệ quản trị CSDL: <strong><?php echo htmlspecialchars($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)); ?></strong>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger py-2" role="alert">
                            <i class="bi bi-x-circle-fill me-2"></i> Kết nối thất bại hoặc biến $pdo chưa được khởi tạo.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. Category Model Check -->
                <div class="mb-4">
                    <h5 class="fw-bold border-bottom pb-2 text-primary">2. Kiểm tra Model Category</h5>
                    <?php 
                    try {
                        $catModel = new Category($pdo);
                        $categories = $catModel->getAllActive();
                        ?>
                        <div class="alert alert-success py-2 mb-2">
                            <i class="bi bi-check-circle-fill me-2"></i> Lấy dữ liệu thành công! Tìm thấy <strong><?php echo count($categories); ?></strong> danh mục đang hoạt động.
                        </div>
                        <ul class="list-group">
                            <?php foreach ($categories as $cat): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    <span>ID: <strong><?php echo $cat['id']; ?></strong> | Tên: <strong><?php echo htmlspecialchars($cat['name']); ?></strong></span>
                                    <span class="badge bg-secondary rounded-pill"><?php echo htmlspecialchars($cat['image']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger py-2">Lỗi Category Model: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                    ?>
                </div>

                <!-- 3. Food Model Check -->
                <div class="mb-4">
                    <h5 class="fw-bold border-bottom pb-2 text-primary">3. Kiểm tra Model Food</h5>
                    <?php 
                    try {
                        $foodModel = new Food($pdo);
                        $featuredFoods = $foodModel->getFeatured(4);
                        ?>
                        <div class="alert alert-success py-2 mb-2">
                            <i class="bi bi-check-circle-fill me-2"></i> Lấy dữ liệu thành công! Tìm thấy <strong><?php echo count($featuredFoods); ?></strong> món nổi bật (Featured).
                        </div>
                        <ul class="list-group">
                            <?php foreach ($featuredFoods as $food): 
                                $price = $food['is_sale'] ? ($food['price'] * (1 - $food['discount_percent']/100)) : $food['price'];
                            ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($food['name']); ?></strong> 
                                        <span class="badge bg-light text-dark border ms-1"><?php echo htmlspecialchars($food['category_name']); ?></span>
                                        <?php if ($food['is_hot']): ?><span class="badge bg-danger ms-1">HOT 🔥</span><?php endif; ?>
                                        <?php if ($food['is_sale']): ?><span class="badge bg-warning text-dark ms-1">Giảm -<?php echo $food['discount_percent']; ?>%</span><?php endif; ?>
                                    </div>
                                    <span class="fw-bold text-primary"><?php echo number_format($price, 0, ',', '.'); ?>đ</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger py-2">Lỗi Food Model: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                    ?>
                </div>

            </div>
            <div class="card-footer bg-light text-center py-3 text-secondary" style="font-size: 13px;">
                Tệp này dùng cho mục đích kiểm thử cục bộ. Bạn nên xóa tệp này trước khi triển khai thực tế (Production).
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
