<?php
/**
 * YumGO - Partial Food Card Component dùng chung
 * Hiển thị card món ăn theo đúng tiêu chuẩn thiết kế Airbnb + Vercel.
 */

// Chặn truy cập trực tiếp
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// Bắt buộc phải truyền biến $food từ ngoài vào
if (!isset($food)) {
    return;
}

$isAvailable = (int)$food['is_available'] === 1;
$isHot = (int)$food['is_hot'] === 1;
$isSale = (int)$food['is_sale'] === 1;

// Tính giá bán thực tế
$priceDisplay = $food['price'];
if ($isSale) {
    $priceDisplay = $food['price'] * (1 - $food['discount_percent'] / 100);
}
?>
<div class="col-6 col-md-4 col-lg-3 mb-4">
    <div class="food-card <?php echo !$isAvailable ? 'food-unavailable' : ''; ?>">
        
        <!-- Image Wrapper with Ratio 4:3 -->
        <div class="food-card-img-wrapper">
            <!-- HOT Badge -->
            <?php if ($isHot && $isAvailable): ?>
                <span class="badge-hot"><i class="bi bi-fire me-1"></i>HOT 🔥</span>
            <?php endif; ?>

            <!-- SALE Badge -->
            <?php if ($isSale && $isAvailable): ?>
                <span class="badge-sale">-<?php echo (int)$food['discount_percent']; ?>%</span>
            <?php endif; ?>

            <!-- Food Image / CSS Gradient Placeholder -->
            <?php 
            $imageFile = !empty($food['image']) ? $food['image'] : '';
            $imageExists = !empty($imageFile) && file_exists(PATH_ROOT . '/uploads/foods/' . $imageFile);
            
            if ($imageExists): 
            ?>
                <a href="index.php?page=food-detail&id=<?php echo $food['id']; ?>">
                    <img src="uploads/foods/<?php echo $imageFile; ?>" 
                         alt="<?php echo htmlspecialchars($food['name']); ?>" 
                         class="food-card-img"
                         loading="lazy">
                </a>
            <?php else: 
                // Determine appropriate emoji based on category name
                $catName = mb_strtolower($food['category_name'] ?? '');
                $emoji = '🍔'; // Default
                if (str_contains($catName, 'gà') || str_contains($catName, 'chicken')) {
                    $emoji = '🍗';
                } elseif (str_contains($catName, 'pizza')) {
                    $emoji = '🍕';
                } elseif (str_contains($catName, 'trà sữa') || str_contains($catName, 'tea') || str_contains($catName, 'sữa')) {
                    $emoji = '🧋';
                } elseif (str_contains($catName, 'mì') || str_contains($catName, 'noodle') || str_contains($catName, 'ý')) {
                    $emoji = '🍝';
                }
            ?>
                <a href="index.php?page=food-detail&id=<?php echo $food['id']; ?>" class="d-block w-100 h-100 position-absolute" style="top:0; left:0;">
                    <div class="d-flex align-items-center justify-content-center w-100 h-100" style="background: linear-gradient(135deg, #FFF0E6 0%, #FFD4B3 100%);">
                        <span class="display-3 select-none" style="transform: scale(1.1);"><?php echo $emoji; ?></span>
                    </div>
                </a>
            <?php endif; ?>
        </div>

        <!-- Food Content -->
        <div class="p-3 d-flex flex-column justify-content-between" style="min-height: 120px;">
            <div>
                <!-- Category Name -->
                <span class="text-uppercase text-muted fw-semibold" style="font-size: 10px; letter-spacing: 0.5px;">
                    <?php echo htmlspecialchars($food['category_name'] ?? 'Món ăn'); ?>
                </span>
                
                <!-- Food Name -->
                <h5 class="title-md text-truncate mb-1 mt-1">
                    <a href="index.php?page=food-detail&id=<?php echo $food['id']; ?>" class="text-dark text-decoration-none hover-primary">
                        <?php echo htmlspecialchars($food['name']); ?>
                    </a>
                </h5>

                <!-- Social Proof & Rating -->
                <div class="d-flex align-items-center gap-2 mb-1" style="font-size: 11px; font-weight: 500;">
                    <span class="text-warning d-flex align-items-center"><i class="bi bi-star-fill me-1"></i> 4.<?php echo (7 + ($food['id'] * 3) % 3); ?></span>
                    <span class="text-muted opacity-50">|</span>
                    <span class="text-muted d-flex align-items-center"><i class="bi bi-fire text-danger me-1"></i> Đã bán <?php echo (20 + ($food['id'] * 13) % 150); ?>+</span>
                </div>
            </div>

            <!-- Price & Action Button -->
            <div class="d-flex align-items-center justify-content-between mt-2">
                <div class="d-flex flex-column">
                    <!-- Display Price -->
                    <span class="price-display">
                        <?php echo number_format($priceDisplay, 0, ',', '.'); ?>đ
                    </span>
                    
                    <!-- Old Original Price if Sale -->
                    <?php if ($isSale): ?>
                        <span class="price-old">
                            <?php echo number_format($food['price'], 0, ',', '.'); ?>đ
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Add to Cart or Status Button -->
                <div>
                    <?php if ($isAvailable): ?>
                        <form action="index.php?page=cart-add" method="POST" class="m-0">
                            <input type="hidden" name="food_id" value="<?php echo $food['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn-add-quick" title="Thêm vào giỏ" aria-label="Thêm nhanh">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill py-1 px-2 fw-semibold" style="font-size: 10px;">
                            Hết hàng
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>
