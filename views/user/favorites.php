<?php
/**
 * YumGO - Trang mon yeu thich luu bang localStorage.
 */

if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}
?>

<div class="container px-3 py-4 favorites-page" data-favorites-page="1">
    <div class="favorite-hero mb-4">
        <div>
            <span class="home-kicker">Bộ sưu tập cá nhân</span>
            <h1 class="display-lg mb-2">Món yêu thích</h1>
            <p class="body-md text-secondary mb-0">Danh sách này được lưu trên trình duyệt của bạn, không cần đăng nhập.</p>
        </div>
        <a href="index.php?page=foods" class="btn btn-primary-yumgo px-4 py-2">
            <i class="bi bi-search-heart me-1"></i> Tìm thêm món
        </a>
    </div>

    <?php if (!empty($favoriteFoodsPage)): ?>
        <div class="row g-3 g-md-4">
            <?php foreach ($favoriteFoodsPage as $food): ?>
                <?php require __DIR__ . '/partials/food-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <?php
        function buildFavoritesUrl(int $pageNo): string {
            $params = ['page' => 'favorites'];
            if (isset($_GET['ids'])) {
                $params['ids'] = $_GET['ids'];
            }
            $params['p'] = $pageNo;
            return 'index.php?' . http_build_query($params);
        }
        ?>

        <?php if ($totalPages > 1): ?>
            <nav class="d-flex justify-content-center mt-5" aria-label="Phân trang món yêu thích">
                <ul class="pagination gap-2 border-0 m-0">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <?php
                        $isActivePage = ((int)$p === (int)$page);
                        $linkParams = buildFavoritesUrl($p);
                        ?>
                        <li class="page-item border-0 <?php echo $isActivePage ? 'active' : ''; ?>">
                            <a class="page-link d-flex align-items-center justify-content-center border fw-semibold text-decoration-none rounded-sm"
                               style="width: 40px; height: 40px;"
                               href="<?php echo htmlspecialchars($linkParams); ?>">
                                <?php echo $p; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div id="favoritesEmptyState">
            <?php
            $emptyIcon = 'bi-heart';
            $emptyTitle = '❤️ Chưa có món yêu thích nào';
            $emptyDesc = 'Bấm biểu tượng trái tim trên món ăn để lưu lại những món bạn muốn quay lại sau.';
            $emptyBtnText = 'Khám phá món ăn';
            $emptyBtnUrl = 'index.php?page=foods';
            require __DIR__ . '/partials/empty-state.php';
            ?>
        </div>
    <?php endif; ?>
</div>
