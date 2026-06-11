<?php
/**
 * YumGO - View danh sach mon an / thuc don.
 */

if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$buildFoodsUrl = function (array $overrides = []) use ($search, $categoryId, $availability, $status, $sort) {
    $params = [
        'page' => 'foods',
        'search' => $search,
        'category_id' => $categoryId,
        'availability' => $availability,
        'status' => $status,
        'sort' => $sort
    ];

    foreach ($overrides as $key => $value) {
        $params[$key] = $value;
    }

    foreach ($params as $key => $value) {
        if ($value === null || $value === '' || $value === 'all' || ($key === 'sort' && $value === 'newest')) {
            unset($params[$key]);
        }
    }

    return 'index.php?' . http_build_query($params);
};
?>

<div class="container px-3 py-4">
    <div class="mb-4">
        <h1 class="display-lg mb-1">Khám phá thực đơn</h1>
        <p class="body-md text-secondary">Tìm món theo khẩu vị, lọc món còn hàng, săn món HOT hoặc SALE ngay hôm nay.</p>
    </div>

    <form action="index.php" method="GET" class="mb-4">
        <input type="hidden" name="page" value="foods">

        <div class="row g-2 align-items-center position-relative">
            <div class="col">
                <div class="input-group border rounded-md bg-canvas shadow-sm" style="overflow: hidden; height: 48px;">
                    <span class="input-group-text bg-transparent border-0 pe-2 ps-3">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text"
                           name="search"
                           class="form-control border-0 ps-1 bg-transparent body-md text-dark yumgo-search-input"
                           placeholder="Tìm kiếm gà rán, pizza, trà sữa..."
                           value="<?php echo htmlspecialchars($search); ?>"
                           aria-label="Tìm kiếm món ăn">
                    <?php if (!empty($search)): ?>
                        <a href="<?php echo htmlspecialchars($buildFoodsUrl(['search' => '', 'p' => null])); ?>"
                           class="btn border-0 bg-transparent text-muted px-3 d-flex align-items-center justify-content-center"
                           title="Xóa tìm kiếm">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-auto">
                <button type="submit" class="btn btn-primary-yumgo h-100 px-4" style="height: 48px !important;">
                    Tìm
                </button>
            </div>
            <div class="search-suggestion-box" aria-live="polite"></div>
        </div>

        <div class="advanced-filter-panel mt-3">
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label filter-label" for="categoryFilter">Danh mục</label>
                    <select class="form-select rounded-md" id="categoryFilter" name="category_id">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat['id']; ?>" <?php echo ((int)$cat['id'] === (int)$categoryId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label filter-label" for="availabilityFilter">Tình trạng</label>
                    <select class="form-select rounded-md" id="availabilityFilter" name="availability">
                        <option value="all" <?php echo $availability === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                        <option value="available" <?php echo $availability === 'available' ? 'selected' : ''; ?>>Còn hàng</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label filter-label" for="statusFilter">Nhãn</label>
                    <select class="form-select rounded-md" id="statusFilter" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                        <option value="hot" <?php echo $status === 'hot' ? 'selected' : ''; ?>>HOT</option>
                        <option value="sale" <?php echo $status === 'sale' ? 'selected' : ''; ?>>SALE</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label filter-label" for="sortFilter">Sắp xếp</label>
                    <select class="form-select rounded-md" id="sortFilter" name="sort">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                        <option value="hot" <?php echo $sort === 'hot' ? 'selected' : ''; ?>>Hot trước</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                    </select>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="submit" class="btn btn-primary-yumgo px-4 py-2">
                    <i class="bi bi-sliders me-1"></i> Áp dụng lọc
                </button>
                <a href="index.php?page=foods" class="btn btn-light rounded-pill px-4 py-2 fw-semibold">
                    Xóa bộ lọc
                </a>
            </div>
        </div>
    </form>

    <div class="mb-4">
        <div class="category-strip">
            <a href="<?php echo htmlspecialchars($buildFoodsUrl(['category_id' => null, 'p' => null])); ?>"
               class="category-pill <?php echo empty($categoryId) ? 'active' : ''; ?>">
                <i class="bi bi-grid me-1"></i>Tất cả
            </a>

            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <?php
                    $isActive = ($categoryId !== null && (int)$cat['id'] === (int)$categoryId);
                    $catName = mb_strtolower($cat['name']);
                    $icon = 'bi-egg-fried';
                    if (str_contains($catName, 'gà')) {
                        $icon = 'bi-fire';
                    } elseif (str_contains($catName, 'pizza')) {
                        $icon = 'bi-circle';
                    } elseif (str_contains($catName, 'trà sữa') || str_contains($catName, 'sữa')) {
                        $icon = 'bi-cup-straw';
                    } elseif (str_contains($catName, 'mì') || str_contains($catName, 'ý')) {
                        $icon = 'bi-basket';
                    }
                    ?>
                    <a href="<?php echo htmlspecialchars($buildFoodsUrl(['category_id' => (int)$cat['id'], 'p' => null])); ?>"
                       class="category-pill <?php echo $isActive ? 'active' : ''; ?>">
                        <i class="bi <?php echo $icon; ?> me-1"></i><?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 g-md-4">
        <?php if (!empty($foods)): ?>
            <?php foreach ($foods as $food): ?>
                <?php require __DIR__ . '/partials/food-card.php'; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <?php
            $emptyIcon = 'bi-search-heart';
            $emptyTitle = 'Không tìm thấy món phù hợp';
            $emptyDesc = 'Không có món ăn nào khớp với từ khóa hoặc bộ lọc hiện tại. Hãy thử nới bộ lọc hoặc xem toàn bộ thực đơn.';
            $emptyBtnText = 'Xem tất cả món ăn';
            $emptyBtnUrl = 'index.php?page=foods';
            require __DIR__ . '/partials/empty-state.php';
            ?>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1 && !empty($foods)): ?>
        <nav class="d-flex justify-content-center mt-5" aria-label="Phân trang thực đơn">
            <ul class="pagination gap-2 border-0 m-0">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php
                    $isActivePage = ((int)$p === (int)$page);
                    $linkParams = $buildFoodsUrl(['p' => $p]);
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
</div>
