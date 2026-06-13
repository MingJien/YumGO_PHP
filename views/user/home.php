<?php
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$heroFoods = array_values(array_filter(array_slice($featuredFoods ?? [], 0, 4), static fn(array $item): bool => !empty($item['image'])));
if (count($heroFoods) < 3) {
    $heroFoods = array_values(array_slice(array_merge($heroFoods, $bestSellerFoods ?? []), 0, 4));
}

$heroSlides = [];
$heroLines = [
    'Giòn nóng vừa tay, mở hộp là thơm cả bàn ăn.',
    'Bữa ngon đổi vị, giao nhanh để bạn kịp mọi lịch hẹn.',
    'Món quen được làm mới bằng sốt đậm đà và phần ăn đầy đặn.',
    'Chọn nhanh hôm nay, YumGO lo phần còn lại thật gọn.'
];

foreach (array_slice($heroFoods, 0, 4) as $index => $item) {
    $heroSlides[] = [
        'name' => (string)($item['name'] ?? 'Món ngon YumGO'),
        'image' => (string)($item['image'] ?? ''),
        'description' => $heroLines[$index] ?? 'Món ngon nóng hổi, giao tới đúng lúc bạn cần.',
        'url' => 'index.php?page=food-detail&id=' . (int)($item['id'] ?? 0),
    ];
}

if (!$heroSlides) {
    $heroSlides[] = [
        'name' => 'Combo YumGO',
        'image' => '',
        'description' => 'Bữa ngon nóng hổi, giao tới đúng lúc bạn cần.',
        'url' => 'index.php?page=foods',
    ];
}

function homeFoodImage(array $food): string {
    $image = (string)($food['image'] ?? '');
    if ($image !== '' && file_exists(PATH_ROOT . '/uploads/foods/' . $image)) {
        return 'uploads/foods/' . htmlspecialchars($image);
    }

    return 'uploads/banners/hero_combo.png';
}

function homeFoodPrice(array $food): float {
    $price = (float)($food['price'] ?? 0);
    if ((int)($food['is_sale'] ?? 0) === 1) {
        return max(0, $price * (1 - ((float)($food['discount_percent'] ?? 0) / 100)));
    }

    return $price;
}
?>

<section class="home-editorial-shell">
    <div class="home-editorial-frame">
        <section class="home-hero-editorial">
            <div class="home-hero-copy">
                <span class="home-kicker">YumGO hôm nay</span>
                <h1 id="homeHeroTitle"><?= htmlspecialchars($heroSlides[0]['name']) ?></h1>
                <p id="homeHeroText"><?= htmlspecialchars($heroSlides[0]['description']) ?></p>
                <div class="home-hero-actions">
                    <a class="btn btn-primary-yumgo home-btn-primary" href="index.php?page=foods">Đặt món ngay</a>
                    <a class="home-btn-light text-decoration-none" href="#mon-gioi-thieu">Xem món nổi bật</a>
                </div>
            </div>

            <div class="home-plate-wrap" data-home-hero-slider='<?= htmlspecialchars(json_encode($heroSlides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>'>
                <div class="home-plate-halo"></div>
                <?php $firstHeroImage = $heroSlides[0]['image'] !== '' ? 'uploads/foods/' . $heroSlides[0]['image'] : 'uploads/banners/hero_combo.png'; ?>
                <a id="homeHeroLink" href="<?= htmlspecialchars($heroSlides[0]['url']) ?>" class="home-plate-link">
                    <img id="homeHeroImage" class="home-plate-img" src="<?= htmlspecialchars($firstHeroImage) ?>" alt="<?= htmlspecialchars($heroSlides[0]['name']) ?>">
                </a>
            </div>
        </section>

        <div class="home-divider"></div>

        <section class="home-menu-highlights" id="mon-gioi-thieu">
            <div class="home-section-title">
                <span></span>
                <h2>Món giới thiệu</h2>
                <span></span>
            </div>

            <div class="home-featured-stats">
                <div>
                    <strong>15-20 phút</strong>
                    <span>Giao nhanh</span>
                </div>
                <div>
                    <strong>4.7/5</strong>
                    <span>Khách hàng đánh giá</span>
                </div>
            </div>

            <div class="home-highlight-row">
                <?php foreach (array_slice($featuredFoods ?? [], 0, 4) as $food): ?>
                    <article class="home-highlight-card">
                        <a class="home-highlight-image" href="index.php?page=food-detail&id=<?= (int)$food['id'] ?>">
                            <img src="<?= homeFoodImage($food) ?>" alt="<?= htmlspecialchars($food['name']) ?>">
                        </a>
                        <h3><?= htmlspecialchars($food['name']) ?></h3>
                        <p><?= htmlspecialchars(mb_substr((string)($food['description'] ?? 'Món ngon YumGO được chuẩn bị nóng hổi mỗi ngày.'), 0, 82)) ?></p>
                        <div class="home-card-bottom">
                            <strong><?= number_format(homeFoodPrice($food), 0, ',', '.') ?>đ</strong>
                            <?php if ((int)($food['is_available'] ?? 0) === 1): ?>
                                <form action="index.php?page=cart-add" method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="food_id" value="<?= (int)$food['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" aria-label="Thêm vào giỏ"><i class="bi bi-plus-lg"></i></button>
                                </form>
                            <?php else: ?>
                                <span class="home-sold-out">Tạm hết</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="home-about-band">
            <div class="home-about-grid">
                <div class="home-story-card">
                    <h3>Bữa ăn nóng, thao tác gọn</h3>
                    <p>YumGO gom món dễ chọn, giỏ hàng rõ ràng và trạng thái đơn cập nhật liên tục để bạn yên tâm chờ bữa ngon.</p>
                    <ul>
                        <li>Đặt món chỉ trong vài bước.</li>
                        <li>Hỗ trợ tra cứu đơn hàng nhanh.</li>
                        <li>Shipper cập nhật sự cố minh bạch.</li>
                    </ul>
                </div>
                <div class="home-chef-panel">
                    <span class="home-kicker">Danh mục</span>
                    <h3>Đổi món theo đúng tâm trạng.</h3>
                    <p>Chọn nhanh theo danh mục, lưu món yêu thích và quay lại những món đã xem gần đây bất cứ lúc nào.</p>
                    <div class="home-mini-promises">
                        <?php foreach (array_slice($categories ?? [], 0, 4) as $category): ?>
                            <div><i class="bi bi-egg-fried"></i><span><?= htmlspecialchars($category['name']) ?></span></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="home-food-items">
            <div class="home-section-heading">
                <span class="home-kicker">Bán chạy</span>
                <h2>Những món được chọn nhiều</h2>
                <p>Các món nổi bật được sắp xếp để bạn quyết định nhanh hơn trong những ngày bận rộn.</p>
            </div>
            <div class="home-food-grid">
                <?php foreach (array_slice($bestSellerFoods ?? [], 0, 8) as $food): ?>
                    <article class="home-food-tile">
                        <a class="home-food-tile-img" href="index.php?page=food-detail&id=<?= (int)$food['id'] ?>">
                            <img src="<?= homeFoodImage($food) ?>" alt="<?= htmlspecialchars($food['name']) ?>">
                        </a>
                        <h3><?= htmlspecialchars($food['name']) ?></h3>
                        <strong><?= number_format(homeFoodPrice($food), 0, ',', '.') ?>đ</strong>
                        <form action="index.php?page=cart-add" method="POST" class="mt-3">
                            <?= csrfField() ?>
                            <input type="hidden" name="food_id" value="<?= (int)$food['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit">Thêm món</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a class="home-see-more d-inline-flex justify-content-center text-decoration-none" href="index.php?page=foods">Xem toàn bộ thực đơn</a>
            </div>
        </section>

        <?php if (!empty($recentFoods)): ?>
            <section class="home-recent-section" data-recent-section="1">
                <div class="home-section-heading">
                    <span class="home-kicker">Hoạt động gần đây</span>
                    <h2>Món bạn vừa xem</h2>
                </div>
                <div class="row">
                    <?php foreach (array_slice($recentFoods, 0, 4) as $food): ?>
                        <?php require 'views/user/partials/food-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="home-review-section">
            <h2>Khách hàng nói gì</h2>
            <div class="home-review-grid">
                <?php foreach ($testimonials ?? [] as $review): ?>
                    <article class="home-review-card">
                        <div class="home-review-stars"><?= str_repeat('<i class="bi bi-star-fill"></i>', (int)$review['stars']) ?></div>
                        <p><?= htmlspecialchars($review['content']) ?></p>
                        <h3><?= htmlspecialchars($review['name']) ?></h3>
                        <span><?= htmlspecialchars($review['role']) ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const slider = document.querySelector('[data-home-hero-slider]');
    if (!slider) return;

    let slides = [];
    try {
        slides = JSON.parse(slider.dataset.homeHeroSlider || '[]');
    } catch (error) {
        slides = [];
    }
    if (slides.length <= 1) return;

    const image = document.getElementById('homeHeroImage');
    const link = document.getElementById('homeHeroLink');
    const title = document.getElementById('homeHeroTitle');
    const text = document.getElementById('homeHeroText');
    let index = 0;

    window.setInterval(() => {
        index = (index + 1) % slides.length;
        const slide = slides[index];
        image.classList.add('is-changing');
        window.setTimeout(() => {
            image.src = slide.image ? `uploads/foods/${slide.image}` : 'uploads/banners/hero_combo.png';
            image.alt = slide.name;
            link.href = slide.url || 'index.php?page=foods';
            title.textContent = slide.name;
            text.textContent = slide.description;
            image.classList.remove('is-changing');
        }, 220);
    }, 3600);
});
</script>
