<?php
/**
 * YumGO - User homepage.
 */

if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$heroFood = !empty($featuredFoods) ? $featuredFoods[0] : null;
$heroImage = 'uploads/banners/hero_combo.png';
if ($heroFood && !empty($heroFood['image']) && file_exists(PATH_ROOT . '/uploads/foods/' . $heroFood['image'])) {
    $heroImage = 'uploads/foods/' . $heroFood['image'];
}

$highlightFoods = array_slice($featuredFoods ?? [], 0, 4);
$storyFoods = array_slice($featuredFoods ?? [], 4, 4);
if (count($storyFoods) < 4) {
    $storyFoods = array_slice(array_reverse($featuredFoods ?? []), 0, 4);
}
?>

<section class="home-editorial-shell">
    <div class="home-editorial-frame">
        <section class="home-hero-editorial">
            <div class="home-hero-copy">
                <span class="home-kicker">Bếp YumGO</span>
                <h1>Trải nghiệm trọn vị, giao nhanh từng bữa.</h1>
                <p>Món nóng hổi, ảnh thật, giá rõ ràng. Đặt là giao ngay!</p>
                <div class="home-hero-actions">
                    <a href="#home-menu-highlights" class="btn home-btn-light">Xem món ngon</a>
                    <a href="index.php?page=foods" class="btn btn-primary-yumgo home-btn-primary">Đặt món ngay</a>
                </div>
            </div>

            <div class="home-plate-wrap" aria-label="Món ăn nổi bật YumGO">
                <div class="home-plate-halo"></div>
                <img src="<?php echo htmlspecialchars($heroImage); ?>" alt="Món ăn nổi bật của YumGO" class="home-plate-img">
                <div class="home-float-note home-float-note-top">
                    <strong>15-20 phút</strong>
                    <span>Giao nhanh</span>
                </div>
                <div class="home-float-note home-float-note-bottom">
                    <strong>4.9/5</strong>
                    <span>Khách hàng đánh giá</span>
                </div>
            </div>
        </section>

        <div class="home-divider"></div>

        <section class="home-menu-highlights" id="home-menu-highlights">
            <div class="home-section-title">
                <span></span>
                <h2>Món nổi bật</h2>
                <span></span>
            </div>

            <?php if (!empty($highlightFoods)): ?>
                <div class="home-highlight-row">
                    <?php foreach ($highlightFoods as $food):
                        $isSale = (int)$food['is_sale'] === 1;
                        $price = $isSale ? ($food['price'] * (1 - $food['discount_percent'] / 100)) : $food['price'];
                        $imageFile = !empty($food['image']) ? $food['image'] : '';
                        $imageExists = !empty($imageFile) && file_exists(PATH_ROOT . '/uploads/foods/' . $imageFile);
                    ?>
                        <article class="home-highlight-card">
                            <a href="index.php?page=food-detail&id=<?php echo (int)$food['id']; ?>" class="home-highlight-image">
                                <?php if ($imageExists): ?>
                                    <img src="uploads/foods/<?php echo htmlspecialchars($imageFile); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>">
                                <?php else: ?>
                                    <span><?php echo htmlspecialchars(mb_substr($food['name'], 0, 1)); ?></span>
                                <?php endif; ?>
                            </a>
                            <h3><?php echo htmlspecialchars($food['name']); ?></h3>
                            <p><?php echo htmlspecialchars(mb_substr($food['description'] ?? 'Món ngon được chọn lọc mỗi ngày.', 0, 72)); ?></p>
                            <div class="home-card-bottom">
                                <strong><?php echo number_format($price, 0, ',', '.'); ?>đ</strong>
                                <?php if ((int)$food['is_available'] === 1): ?>
                                    <form action="index.php?page=cart-add" method="POST">
                                        <input type="hidden" name="food_id" value="<?php echo (int)$food['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" aria-label="Thêm vào giỏ hàng"><i class="bi bi-plus-lg"></i></button>
                                    </form>
                                <?php else: ?>
                                    <span class="home-sold-out">Hết hàng</span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php
                $emptyIcon = 'bi-egg-fried';
                $emptyTitle = 'Chưa có món nổi bật';
                $emptyDesc = 'YumGO đang cập nhật thực đơn mới. Bạn có thể xem tất cả món đang có sẵn.';
                $emptyBtnText = 'Xem thực đơn';
                $emptyBtnUrl = 'index.php?page=foods';
                require __DIR__ . '/partials/empty-state.php';
                ?>
            <?php endif; ?>
        </section>

        <?php if (!empty($bestSellerFoods)): ?>
            <section class="home-best-seller-section">
                <div class="home-section-heading">
                    <h2><i class="bi bi-fire best-seller-fire me-2"></i>Món bán chạy</h2>
                    
                </div>
                <div class="row g-3 g-md-4">
                    <?php foreach (array_slice($bestSellerFoods, 0, 8) as $food): ?>
                        <?php require __DIR__ . '/partials/food-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="home-about-band" id="home-about">
            <div class="home-section-title">
                <span></span>
                <h2>Vì sao chọn YumGO</h2>
                <span></span>
            </div>
            <div class="home-about-grid">
                <div class="home-story-card">
                    <h3>Câu chuyện của chúng tôi</h3>
                    <p>YumGO tập trung vào các bữa ăn nhanh, nóng và dễ đặt. Mỗi món hiện trên trang đều được lọc theo trạng thái còn bán và danh mục hợp lệ.</p>
                    <ul>
                        <li>Giá và tạm tính luôn tính lại ở backend.</li>
                        <li>Giỏ hàng chỉ lưu món và số lượng.</li>
                    </ul>
                </div>
                <div class="home-chef-panel">
                    <h3>Cam kết phục vụ</h3>
                    <p>Giao nhanh, món rõ nguồn gốc, thao tác gọn trên cả điện thoại và desktop.</p>
                    <div class="home-mini-promises">
                        <div><i class="bi bi-lightning-charge"></i><span>Nhanh</span></div>
                        <div><i class="bi bi-shield-check"></i><span>An toàn</span></div>
                        <div><i class="bi bi-ticket-perforated"></i><span>Ưu đãi</span></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-food-items">
            <div class="home-section-heading">
                <h2>Món ăn hôm nay</h2>
            </div>
            <div class="home-food-grid">
                <?php foreach ($storyFoods as $food):
                    $isAvailable = (int)$food['is_available'] === 1;
                    $isSale = (int)$food['is_sale'] === 1;
                    $price = $isSale ? ($food['price'] * (1 - $food['discount_percent'] / 100)) : $food['price'];
                    $imageFile = !empty($food['image']) ? $food['image'] : '';
                    $imageExists = !empty($imageFile) && file_exists(PATH_ROOT . '/uploads/foods/' . $imageFile);
                ?>
                    <article class="home-food-tile <?php echo !$isAvailable ? 'is-disabled' : ''; ?>">
                        <a href="index.php?page=food-detail&id=<?php echo (int)$food['id']; ?>" class="home-food-tile-img">
                            <?php if ($imageExists): ?>
                                <img src="uploads/foods/<?php echo htmlspecialchars($imageFile); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>">
                            <?php else: ?>
                                <span><?php echo htmlspecialchars(mb_substr($food['name'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </a>
                        <h3><?php echo htmlspecialchars($food['name']); ?></h3>
                        <strong><?php echo number_format($price, 0, ',', '.'); ?>đ</strong>
                        <?php if ($isAvailable): ?>
                            <form action="index.php?page=cart-add" method="POST">
                                <input type="hidden" name="food_id" value="<?php echo (int)$food['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit">Đặt ngay</button>
                            </form>
                        <?php else: ?>
                            <span class="home-sold-out">Tạm hết hàng</span>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="index.php?page=foods" class="btn btn-primary-yumgo home-see-more">Xem thêm</a>
            </div>
        </section>

        <section class="home-recent-section" data-recent-section="1">
            <div class="home-section-heading">
                <h2>Món bạn vừa xem</h2>
            </div>

            <?php if (!empty($recentFoods)): ?>
                <div class="row g-3 g-md-4">
                    <?php foreach (array_slice($recentFoods, 0, 4) as $food): ?>
                        <?php require __DIR__ . '/partials/food-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="recent-empty-note">
                    <i class="bi bi-clock-history"></i>
                    <span>Xem một vài món để YumGO lưu lại danh sách gần đây cho lần ghé sau.</span>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!empty($testimonials)): ?>
            <section class="home-review-section">
                <div class="home-divider"></div>
                <h2>Khách hàng đánh giá</h2>
                <div class="home-review-grid">
                    <?php foreach ($testimonials as $t): ?>
                        <article class="home-review-card">
                            <div class="home-review-head">
                                <div class="home-review-avatar">
                                    <?php if (!empty($t['avatar_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($t['avatar_url']); ?>" alt="<?php echo htmlspecialchars($t['name']); ?>" loading="lazy">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($t['avatar_letter']); ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3><?php echo htmlspecialchars($t['name']); ?></h3>
                                    <span><?php echo htmlspecialchars($t['role']); ?></span>
                                </div>
                            </div>
                            <div class="home-stars">
                                <?php for ($i = 0; $i < (int)$t['stars']; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
                            </div>
                            <p><?php echo htmlspecialchars($t['content']); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>
