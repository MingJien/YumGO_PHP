<?php
/**
 * YumGO - View Chi tiết món ăn (food-detail.php)
 */

// Chặn truy cập trực tiếp
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// Bắt đầu đọc dữ liệu chi tiết món ăn đã validate từ controller
$isAvailable = (int)$food['is_available'] === 1;
$isHot = (int)$food['is_hot'] === 1;
$isSale = (int)$food['is_sale'] === 1;

// Tính giá bán thực tế
$unitPrice = $food['price'];
if ($isSale) {
    $unitPrice = $food['price'] * (1 - $food['discount_percent'] / 100);
}
?>

<!-- Custom CSS specific to detail page (sticky CTA and animations) -->
<style>
    .detail-hero-img-wrapper {
        position: relative;
        width: 100%;
        height: 240px;
        overflow: hidden;
    }
    .detail-hero-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Sticky Bottom CTA for mobile (< 992px) */
    .sticky-cta-bar {
        position: fixed;
        bottom: 0; /* Gắn sát đáy vì thanh Bottom Nav đã được gỡ bỏ */
        left: 0;
        width: 100%;
        background-color: var(--yumgo-canvas);
        border-top: 1px solid var(--yumgo-hairline);
        padding: 12px 16px;
        box-shadow: var(--shadow-nav);
        z-index: 999;
    }
    
    @media (min-width: 768px) {
        .detail-hero-img-wrapper {
            height: 360px;
            border-radius: 16px;
            box-shadow: var(--shadow-card);
        }
    }
    @media (min-width: 992px) {
        .sticky-cta-bar {
            position: static;
            border: none;
            padding: 0;
            box-shadow: none;
            background: transparent;
        }
    }
    /* Đảm bảo nội dung không bị che khuất bởi thanh bám đáy di động */
    @media (max-width: 991px) {
        .container.py-4 {
            padding-bottom: 80px !important;
        }
    }
</style>

<div class="container px-3 py-4">
    <!-- Back Button link -->
    <div class="mb-3">
        <a href="javascript:history.back()" class="text-secondary text-decoration-none fw-semibold body-md d-inline-flex align-items-center">
            <i class="bi bi-arrow-left me-1"></i> Quay lại thực đơn
        </a>
    </div>

    <div class="row gy-4">
        <!-- Left Column: Large Cover Image -->
        <div class="col-lg-6">
            <div class="detail-hero-img-wrapper <?php echo !$isAvailable ? 'food-unavailable' : ''; ?>">
                <!-- HOT Badge -->
                <?php if ($isHot && $isAvailable): ?>
                    <span class="badge-hot" style="font-size: 13px; padding: 6px 12px;"><i class="bi bi-fire me-1"></i>HOT 🔥</span>
                <?php endif; ?>

                <!-- SALE Badge -->
                <?php if ($isSale && $isAvailable): ?>
                    <span class="badge-sale" style="font-size: 13px; padding: 6px 12px;">Giảm <?php echo (int)$food['discount_percent']; ?>%</span>
                <?php endif; ?>

                <!-- Image / Gradient fallback -->
                <?php 
                $imageFile = !empty($food['image']) ? $food['image'] : '';
                $imageExists = !empty($imageFile) && file_exists(PATH_ROOT . '/uploads/foods/' . $imageFile);
                
                if ($imageExists): 
                ?>
                    <img src="uploads/foods/<?php echo $imageFile; ?>" 
                         alt="<?php echo htmlspecialchars($food['name']); ?>" 
                         class="detail-hero-img">
                <?php else: 
                    // Emoji selection
                    $catName = mb_strtolower($food['category_name'] ?? '');
                    $emoji = '🍔';
                    if (str_contains($catName, 'gà') || str_contains($catName, 'chicken')) { $emoji = '🍗'; }
                    elseif (str_contains($catName, 'pizza')) { $emoji = '🍕'; }
                    elseif (str_contains($catName, 'trà sữa') || str_contains($catName, 'sữa')) { $emoji = '🧋'; }
                    elseif (str_contains($catName, 'mì') || str_contains($catName, 'ý')) { $emoji = '🍝'; }
                ?>
                    <div class="d-flex align-items-center justify-content-center w-100 h-100" style="background: linear-gradient(135deg, #FFF0E6 0%, #FFD4B3 100%);">
                        <span style="font-size: 6rem;"><?php echo $emoji; ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Detail Content and Form -->
        <div class="col-lg-6 d-flex flex-column justify-content-between">
            <div>
                <!-- Category badge -->
                <span class="badge text-primary rounded-pill mb-2 fw-semibold px-3 py-1" style="background-color: var(--yumgo-primary-soft);">
                    <?php echo htmlspecialchars($food['category_name'] ?? 'Món ăn'); ?>
                </span>
                
                <!-- Food Name -->
                <h1 class="display-lg mb-2 mt-1"><?php echo htmlspecialchars($food['name']); ?></h1>
                
                <!-- Pricing block -->
                <div class="d-flex align-items-baseline gap-2 mb-3">
                    <span class="price-display fs-4" id="unitPriceDisplay" data-price="<?php echo $unitPrice; ?>">
                        <?php echo number_format($unitPrice, 0, ',', '.'); ?>đ
                    </span>
                    <?php if ($isSale): ?>
                        <span class="price-old fs-6">
                            <?php echo number_format($food['price'], 0, ',', '.'); ?>đ
                        </span>
                    <?php endif; ?>
                </div>

                <hr style="border-color: var(--yumgo-hairline);">

                <!-- Description -->
                <div class="mb-4">
                    <h6 class="fw-bold text-uppercase text-muted" style="font-size: 11px; letter-spacing: 0.5px;">Mô tả món ăn</h6>
                    <p class="body-md text-secondary" style="white-space: pre-line;">
                        <?php echo htmlspecialchars($food['description'] ?? 'Món ăn được chuẩn bị từ nguồn nguyên liệu sạch, công thức đặc trưng của YumGO.'); ?>
                    </p>
                </div>
            </div>

            <!-- Form Add to Cart -->
            <?php if ($isAvailable): ?>
                <form action="index.php?page=cart-add" method="POST" id="addToCartForm">
                    <input type="hidden" name="food_id" value="<?php echo $food['id']; ?>">
                    
                    <!-- Quantity Selector (Pill Shape) -->
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <span class="fw-semibold body-md">Số lượng:</span>
                        <div class="d-flex align-items-center border rounded-pill bg-canvas py-1 px-2 shadow-sm" style="width: 120px;">
                            <button type="button" class="btn border-0 p-0 text-primary fw-bold px-2 fs-5" id="btnMinus" aria-label="Giảm">-</button>
                            <input type="number" 
                                   name="quantity" 
                                   id="inputQuantity" 
                                   class="form-control border-0 text-center bg-transparent p-0 m-0 fw-bold" 
                                   style="box-shadow: none;"
                                   value="1" 
                                   min="1" 
                                   readonly>
                            <button type="button" class="btn border-0 p-0 text-primary fw-bold px-2 fs-5" id="btnPlus" aria-label="Tăng">+</button>
                        </div>
                    </div>

                    <!-- CTA Bar (Responsive Sticky bottom on mobile, inline on desktop) -->
                    <div class="sticky-cta-bar">
                        <div class="container d-flex gap-2 p-0 justify-content-center">
                            <!-- Show total price dynamically in the button -->
                            <button type="submit" class="btn-primary-yumgo w-100 py-3 shadow-md" style="height: 52px; font-size: 16px;" id="btnSubmitCart">
                                Thêm vào giỏ &bull; <span id="totalPriceDisplay"><?php echo number_format($unitPrice, 0, ',', '.'); ?>đ</span>
                            </button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <!-- Unavailable Notice -->
                <div class="alert alert-secondary rounded-md text-center py-3" role="alert">
                    <i class="bi bi-emoji-frown me-2"></i> Món ăn này hiện đang tạm hết hàng. Vui lòng quay lại sau!
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Related Foods Section -->
    <?php if (!empty($relatedFoods)): ?>
        <div class="mt-5 pt-4 border-top" style="border-color: var(--yumgo-hairline) !important;">
            <h3 class="display-md mb-4 text-dark fw-bold" style="font-size: 22px;">Có thể bạn cũng thích <span class="text-danger">❤️</span></h3>
            <div class="row g-3 g-md-4">
                <?php 
                foreach ($relatedFoods as $food) {
                    require __DIR__ . '/partials/food-card.php';
                }
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript for dynamic quantity & subtotal calc -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnMinus = document.getElementById('btnMinus');
    const btnPlus = document.getElementById('btnPlus');
    const inputQty = document.getElementById('inputQuantity');
    const unitPriceDisplay = document.getElementById('unitPriceDisplay');
    const totalPriceDisplay = document.getElementById('totalPriceDisplay');

    if (!btnMinus || !btnPlus || !inputQty || !unitPriceDisplay || !totalPriceDisplay) {
        return;
    }

    const unitPrice = parseFloat(unitPriceDisplay.getAttribute('data-price'));

    // Helper to format currency VND
    function formatCurrency(number) {
        return new Intl.NumberFormat('vi-VN').format(number) + 'đ';
    }

    // Update quantity and recalculate subtotal
    function updateQuantity(amount) {
        let currentVal = parseInt(inputQty.value) || 1;
        currentVal += amount;
        if (currentVal < 1) currentVal = 1;
        inputQty.value = currentVal;
        
        // Calculate new subtotal
        const newTotal = currentVal * unitPrice;
        totalPriceDisplay.textContent = formatCurrency(newTotal);
    }

    btnMinus.addEventListener('click', () => updateQuantity(-1));
    btnPlus.addEventListener('click', () => updateQuantity(1));
});
</script>
