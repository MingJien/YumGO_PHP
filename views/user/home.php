<?php
/**
 * YumGO - View Trang chủ (home.php)
 * Thiết kế lại hoàn toàn theo triết lý "Premium Food Delivery Startup" - WOW trong 3 giây đầu tiên.
 */

// Chặn truy cập trực tiếp
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}
?>

<!-- 1. Hero Section Premium (Chiếm trọn 100vh) -->
<section class="hero-premium d-flex align-items-center py-5">
    <div class="container px-3">
        <div class="row align-items-center gy-5">
            <!-- Left Column: Copy & Actions -->
            <div class="col-lg-6 position-relative z-2">
                <!-- Slogan Badge -->
                <span class="d-inline-flex align-items-center gap-1 badge rounded-pill bg-white px-3 py-2 text-dark border mb-4 shadow-sm" style="font-size: 13px; font-weight: 600;">
                    <span class="text-danger">🔥</span> Giao đồ ăn số 1 tại địa phương
                </span>
                
                <!-- Headline cực lớn -->
                <h1 class="display-hero fw-bold text-dark mb-3" style="font-size: 48px; line-height: 1.15; letter-spacing: -0.04em;">
                    Đặt món yêu thích<br>
                    <span style="color: var(--yumgo-primary);">chỉ trong vài phút.</span>
                </h1>
                
                <!-- Subtext mô tả súc tích dưới 20 từ -->
                <p class="body-lg text-secondary mb-4" style="max-width: 480px; font-size: 16px; line-height: 1.5;">
                    Hàng trăm món ăn ngon từ các nhà hàng uy tín hàng đầu. Giao nhanh, an toàn và cực kỳ tiện lợi.
                </p>
                
                <!-- Search Box lớn cao 56px -->
                <form action="index.php" method="GET" class="mb-4" style="max-width: 480px;">
                    <input type="hidden" name="page" value="foods">
                    <div class="input-group border rounded-pill bg-white p-1 shadow-md" style="overflow: hidden; height: 56px;">
                        <span class="input-group-text bg-transparent border-0 pe-1 ps-3 text-secondary">
                            <i class="bi bi-search fs-5"></i>
                        </span>
                        <input type="text" 
                               name="search" 
                               class="form-control border-0 ps-2 bg-transparent text-dark body-md" 
                               placeholder="Bạn muốn ăn gì hôm nay?" 
                               style="outline: none; box-shadow: none;"
                               required>
                        <button type="submit" class="btn btn-primary-yumgo rounded-pill px-4 py-2 fw-semibold d-flex align-items-center justify-content-center" style="font-size: 14px; min-height: 48px;">
                            Tìm món
                        </button>
                    </div>
                </form>
                
                <!-- CTA Buttons -->
                <div class="d-flex align-items-center gap-3">
                    <a href="index.php?page=foods" class="btn btn-primary-yumgo rounded-pill px-4 py-3 shadow-md">
                        Đặt món ngay <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                    <a href="#featured-section" class="btn btn-link text-secondary text-decoration-none fw-semibold body-md px-3 hover-primary">
                        Khám phá thực đơn
                    </a>
                </div>
            </div>
            
            <!-- Right Column: Visual Asset & Floating Glassmorphic Cards -->
            <div class="col-lg-6 position-relative d-flex justify-content-center">
                <div class="position-relative" style="width: 100%; max-width: 460px; height: 420px;">
                    <!-- Main Food Image Container -->
                    <div class="rounded-lg overflow-hidden w-100 h-100 shadow-modal border position-relative z-1" 
                         style="background: linear-gradient(135deg, #FFF9F5 0%, #FFF0E6 100%);">
                        <img src="uploads/banners/hero_combo.png" class="w-100 h-100" style="object-fit: cover;" alt="YumGO Premium Combo">
                    </div>
                    
                    <!-- Floating Card 1: Rating -->
                    <div class="position-absolute glass-card rounded-md p-2 d-flex align-items-center gap-2 floating-1 z-2"
                         style="width: 140px; top: 30px; left: -30px;">
                        <span class="fs-4 text-warning">⭐</span>
                        <div>
                            <span class="text-dark fw-bold d-block" style="font-size: 13px;">4.9 Rating</span>
                            <span class="text-muted" style="font-size: 10px;">(12k+ đánh giá)</span>
                        </div>
                    </div>
                    
                    <!-- Floating Card 2: Speed -->
                    <div class="position-absolute glass-card rounded-md p-2 d-flex align-items-center gap-2 floating-2 z-2"
                         style="width: 150px; bottom: 60px; left: -40px;">
                        <span class="fs-4 text-primary">🚴</span>
                        <div>
                            <span class="text-dark fw-bold d-block" style="font-size: 13px;">15 Phút</span>
                            <span class="text-muted" style="font-size: 10px;">Giao nhanh siêu tốc</span>
                        </div>
                    </div>
                    
                    <!-- Floating Card 3: Free Ship -->
                    <div class="position-absolute glass-card rounded-md p-2 d-flex align-items-center gap-2 floating-3 z-2"
                         style="width: 150px; top: 60px; right: -30px;">
                        <span class="fs-4 text-success">🎁</span>
                        <div>
                            <span class="text-dark fw-bold d-block" style="font-size: 13px;">Freeship</span>
                            <span class="text-muted" style="font-size: 10px;">Đơn hàng từ 150k</span>
                        </div>
                    </div>
                    
                    <!-- Floating Card 4: Orders today -->
                    <div class="position-absolute glass-card rounded-md p-2 d-flex align-items-center gap-2 floating-4 z-2"
                         style="width: 140px; bottom: 80px; right: -20px;">
                        <span class="fs-4 text-danger">🔥</span>
                        <div>
                            <span class="text-dark fw-bold d-block" style="font-size: 13px;">500+ Đơn</span>
                            <span class="text-muted" style="font-size: 10px;">Đã bán hôm nay</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. Trust Section (Minimalist metrics under Hero) -->
<section class="border-top border-bottom py-4 bg-white">
    <div class="container px-3">
        <div class="row text-center gy-4">
            <div class="col-6 col-md-3">
                <div class="trust-metric-number">50.000+</div>
                <div class="text-secondary mt-1 body-md" style="font-size: 13px;">Đơn hàng thành công</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="trust-metric-number">300+</div>
                <div class="text-secondary mt-1 body-md" style="font-size: 13px;">Nhà hàng đối tác</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="trust-metric-number">4.9★</div>
                <div class="text-secondary mt-1 body-md" style="font-size: 13px;">Đánh giá trung bình</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="trust-metric-number">15 Phút</div>
                <div class="text-secondary mt-1 body-md" style="font-size: 13px;">Thời gian giao trung bình</div>
            </div>
        </div>
    </div>
</section>

<!-- 3. Food Categories / Popular Near You -->
<section class="py-5">
    <div class="container px-3">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h2 class="display-md m-0" style="font-size: 22px;">Khám Phá Danh Mục</h2>
            <a href="index.php?page=foods" class="text-primary text-decoration-none fw-semibold body-md">
                Xem thêm <i class="bi bi-arrow-right fs-7"></i>
            </a>
        </div>
        <div class="category-strip gsap-reveal-strip">
            <a href="index.php?page=foods" class="category-pill active">🍔 Tất Cả Món</a>
            <?php 
            if (!empty($categories)):
                foreach ($categories as $cat):
                    $catName = mb_strtolower($cat['name']);
                    $emoji = '🍽️';
                    if (str_contains($catName, 'gà')) { $emoji = '🍗'; }
                    elseif (str_contains($catName, 'pizza')) { $emoji = '🍕'; }
                    elseif (str_contains($catName, 'trà sữa') || str_contains($catName, 'sữa')) { $emoji = '🧋'; }
                    elseif (str_contains($catName, 'mì') || str_contains($catName, 'ý')) { $emoji = '🍝'; }
                    elseif (str_contains($catName, 'cơm')) { $emoji = '🍱'; }
                    elseif (str_contains($catName, 'vặt')) { $emoji = '🍟'; }
            ?>
                    <a href="index.php?page=foods&category_id=<?php echo $cat['id']; ?>" class="category-pill">
                        <?php echo $emoji . ' ' . htmlspecialchars($cat['name']); ?>
                    </a>
            <?php 
                endforeach;
            endif; 
            ?>
        </div>
    </div>
</section>

<!-- 4. Featured Foods Section (Món ăn nổi bật) -->
<section class="py-5 bg-white border-top border-bottom" id="featured-section">
    <div class="container px-3">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="display-md m-0" style="font-size: 22px;">Món Ăn Nổi Bật Hôm Nay <span class="text-danger">🔥</span></h2>
                <p class="body-md text-secondary m-0 mt-1">Những món ăn ngon lành bán chạy nhất được đề xuất cho bạn.</p>
            </div>
            <a href="index.php?page=foods" class="text-primary text-decoration-none fw-semibold body-md">
                Xem tất cả <i class="bi bi-chevron-right fs-7"></i>
            </a>
        </div>

        <div class="row g-3 g-md-4 gsap-reveal-grid">
            <?php 
            if (!empty($featuredFoods)):
                foreach ($featuredFoods as $food) {
                    require __DIR__ . '/partials/food-card.php';
                }
            else:
                $emptyIcon = 'bi-egg-fried';
                $emptyTitle = 'Chưa có món ăn nổi bật nào';
                $emptyDesc = 'Hệ thống đang chuẩn bị cập nhật thực đơn đặc biệt. Bạn có thể duyệt thực đơn chính để xem các món ăn khác nhé.';
                $emptyBtnText = 'Xem thực đơn chính';
                $emptyBtnUrl = 'index.php?page=foods';
                require __DIR__ . '/partials/empty-state.php';
            endif; 
            ?>
        </div>
    </div>
</section>

<!-- 5. Bento Grid / Benefits (Tại sao chọn YumGO) -->
<section class="py-5">
    <div class="container px-3">
        <div class="text-center mb-5">
            <h2 class="display-md" style="font-size: 24px;">Lợi Ích Từ YumGO</h2>
            <p class="body-md text-secondary" style="max-width: 480px; margin: 8px auto 0;">Chúng tôi cam kết chất lượng tốt nhất từ khâu chuẩn bị đến khi giao hàng.</p>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card h-100 rounded-md p-4 bento-card bento-delivery border-0">
                    <div class="fs-1 mb-3">🚚</div>
                    <h3 class="h6 fw-bold text-dark mb-2" style="font-size: 16px;">Giao Hàng Siêu Tốc</h3>
                    <p class="body-md text-secondary m-0" style="font-size: 13px;">Cam kết giao món nóng hổi đến tận tay bạn trong vòng 15-20 phút. Đảm bảo độ ngon trọn vẹn.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 rounded-md p-4 bento-card bento-hygiene border-0">
                    <div class="fs-1 mb-3">🧼</div>
                    <h3 class="h6 fw-bold text-dark mb-2" style="font-size: 16px;">Vệ Sinh An Toàn</h3>
                    <p class="body-md text-secondary m-0" style="font-size: 13px;">100% đối tác nhà hàng đạt chứng nhận an toàn thực phẩm. Nguyên liệu sạch, tươi ngon mỗi ngày.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 rounded-md p-4 bento-card bento-gift border-0">
                    <div class="fs-1 mb-3">🎁</div>
                    <h3 class="h6 fw-bold text-dark mb-2" style="font-size: 16px;">Ưu Đãi Hấp Dẫn</h3>
                    <p class="body-md text-secondary m-0" style="font-size: 13px;">Hàng ngàn mã giảm giá độc quyền cùng nhiều voucher freeship cực hot mỗi ngày.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 6. Customer Reviews Section (Testimonials) -->
<?php if (!empty($testimonials)): ?>
<section class="py-5 bg-white border-top border-bottom">
    <div class="container px-3">
        <div class="text-center mb-5">
            <h2 class="display-md" style="font-size: 24px;">Khách Hàng Nói Về YumGO <span class="text-danger">❤️</span></h2>
            <p class="body-md text-secondary" style="max-width: 480px; margin: 8px auto 0;">Hàng ngàn khách hàng đã đặt món và hài lòng với chất lượng dịch vụ của chúng tôi.</p>
        </div>
        
        <div class="testimonial-grid gsap-reveal-reviews">
            <?php foreach ($testimonials as $t): ?>
                <div class="card testimonial-card rounded-md p-4 border-0 shadow-sm d-flex flex-column justify-content-between h-100">
                    <div>
                        <!-- Rating Stars -->
                        <div class="text-warning mb-3">
                            <?php for ($i = 0; $i < $t['stars']; $i++): ?>
                                <i class="bi bi-star-fill" style="font-size: 13px;"></i>
                            <?php endfor; ?>
                        </div>
                        <!-- Content -->
                        <p class="body-md text-dark mb-4 italic" style="font-size: 14px; line-height: 1.5; font-style: italic;">
                            "<?php echo htmlspecialchars($t['content']); ?>"
                        </p>
                    </div>
                    
                    <!-- Customer Profile Info -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary-soft text-primary fw-bold d-flex align-items-center justify-content-center" 
                             style="width: 40px; height: 40px; font-size: 14px; background-color: var(--yumgo-primary-soft);">
                            <?php echo htmlspecialchars($t['avatar_letter']); ?>
                        </div>
                        <div>
                            <h6 class="title-md m-0" style="font-size: 14px;"><?php echo htmlspecialchars($t['name']); ?></h6>
                            <span class="text-secondary" style="font-size: 11px;"><?php echo htmlspecialchars($t['role']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 7. Mid Page CTA / Promotion Banner -->
<section class="py-5">
    <div class="container px-3">
        <div class="mid-cta-banner rounded-lg p-5 text-center text-white">
            <span class="badge rounded-pill bg-warning text-dark px-3 py-2 mb-3 fw-bold body-md" style="font-size: 12px;">ƯU ĐÃI ĐẶC BIỆT</span>
            <h2 class="display-lg text-white mb-2" style="font-size: 32px; letter-spacing: -0.02em;">Bạn đang thấy đói?</h2>
            <p class="body-md text-light opacity-75 mb-4 mx-auto" style="max-width: 480px;">
                Đặt món yêu thích ngay bây giờ để nhận ưu đãi giảm giá lên tới 20% cho đơn hàng đầu tiên của bạn.
            </p>
            <a href="index.php?page=foods" class="btn btn-primary-yumgo rounded-pill px-5 py-3 shadow-md fw-semibold" style="font-size: 15px; border: none; background-color: var(--yumgo-primary); color: #fff;">
                Nhận ưu đãi ngay
            </a>
        </div>
    </div>
</section>
