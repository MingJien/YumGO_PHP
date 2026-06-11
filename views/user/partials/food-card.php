<?php
/**
 * YumGO - Food card dùng chung.
 */

if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

if (!isset($food)) {
    return;
}

$isAvailable = (int)$food['is_available'] === 1;
$isHot = (int)$food['is_hot'] === 1;
$isSale = (int)$food['is_sale'] === 1;

$priceDisplay = $food['price'];
if ($isSale) {
    $priceDisplay = $food['price'] * (1 - $food['discount_percent'] / 100);
}

$imageFile = !empty($food['image']) ? $food['image'] : '';
$imageExists = !empty($imageFile) && file_exists(PATH_ROOT . '/uploads/foods/' . $imageFile);
$categoryName = $food['category_name'] ?? 'Món ăn';
?>

<div class="col-6 col-md-4 col-lg-3 mb-4">
    <div class="food-card <?php echo !$isAvailable ? 'food-unavailable' : ''; ?>">
        <div class="food-card-img-wrapper">
            <button type="button"
                    class="favorite-toggle"
                    data-food-id="<?php echo (int)$food['id']; ?>"
                    aria-label="Lưu món yêu thích">
                <i class="bi bi-heart"></i>
            </button>

            <?php if ($isHot && $isAvailable): ?>
                <span class="badge-hot"><i class="bi bi-fire me-1"></i>HOT</span>
            <?php endif; ?>

            <?php if ($isSale && $isAvailable): ?>
                <span class="badge-sale">-<?php echo (int)$food['discount_percent']; ?>%</span>
            <?php endif; ?>

            <?php if ($imageExists): ?>
                <a href="index.php?page=food-detail&id=<?php echo (int)$food['id']; ?>">
                    <img src="uploads/foods/<?php echo htmlspecialchars($imageFile); ?>"
                         alt="<?php echo htmlspecialchars($food['name']); ?>"
                         class="food-card-img"
                         loading="lazy">
                </a>
            <?php else: ?>
                <a href="index.php?page=food-detail&id=<?php echo (int)$food['id']; ?>" class="d-block w-100 h-100 position-absolute" style="top:0; left:0;">
                    <div class="food-card-placeholder">
                        <i class="bi bi-egg-fried"></i>
                    </div>
                </a>
            <?php endif; ?>
        </div>

        <div class="p-3 d-flex flex-column justify-content-between" style="min-height: 120px;">
            <div>
                <span class="text-uppercase text-muted fw-semibold" style="font-size: 10px; letter-spacing: 0.5px;">
                    <?php echo htmlspecialchars($categoryName); ?>
                </span>

                <h5 class="title-md text-truncate mb-1 mt-1">
                    <a href="index.php?page=food-detail&id=<?php echo (int)$food['id']; ?>" class="text-dark text-decoration-none hover-primary">
                        <?php echo htmlspecialchars($food['name']); ?>
                    </a>
                </h5>

                <div class="d-flex align-items-center gap-2 mb-1" style="font-size: 11px; font-weight: 500;">
                    <span class="text-warning d-flex align-items-center">
                        <i class="bi bi-star-fill me-1"></i>4.<?php echo (7 + ((int)$food['id'] * 3) % 3); ?>
                    </span>
                    <span class="text-muted opacity-50">|</span>
                    <span class="text-muted d-flex align-items-center">
                        <i class="bi bi-fire text-danger me-1"></i>Đã bán <?php echo (20 + ((int)$food['id'] * 13) % 150); ?>+
                    </span>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-2">
                <div class="d-flex flex-column">
                    <span class="price-display">
                        <?php echo number_format($priceDisplay, 0, ',', '.'); ?>đ
                    </span>

                    <?php if ($isSale): ?>
                        <span class="price-old">
                            <?php echo number_format($food['price'], 0, ',', '.'); ?>đ
                        </span>
                    <?php endif; ?>
                </div>

                <div>
                    <?php if ($isAvailable): ?>
                        <form action="index.php?page=cart-add" method="POST" class="m-0">
                            <input type="hidden" name="food_id" value="<?php echo (int)$food['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn-add-quick" title="Thêm vào giỏ" aria-label="Thêm nhanh vào giỏ">
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
