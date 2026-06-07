<?php
/**
 * YumGO - View Danh sách món ăn / Thực đơn (foods.php)
 */

// Chặn truy cập trực tiếp
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}
?>

<div class="container px-3 py-4">
    <!-- Header Page Title -->
    <div class="mb-4">
        <h1 class="display-lg mb-1">Khám Phá Thực Đơn</h1>
        <p class="body-md text-secondary">Tìm kiếm món ăn yêu thích và đặt ngay hôm nay!</p>
    </div>

    <!-- Search and Filter Form -->
    <form action="index.php" method="GET" class="mb-4">
        <!-- Ràng buộc luồng Router chính -->
        <input type="hidden" name="page" value="foods">
        
        <?php if (!empty($categoryId)): ?>
            <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($categoryId); ?>">
        <?php endif; ?>

        <div class="row g-2 align-items-center">
            <!-- Search bar (Height: 48px, radius: 12px) -->
            <div class="col">
                <div class="input-group border rounded-md bg-canvas shadow-sm" style="overflow: hidden; height: 48px;">
                    <span class="input-group-text bg-transparent border-0 pe-2 ps-3">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           class="form-control border-0 ps-1 bg-transparent body-md text-dark" 
                           placeholder="Tìm kiếm gà rán, pizza, trà sữa..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           aria-label="Tìm kiếm">
                    <?php if (!empty($search)): ?>
                        <a href="index.php?page=foods<?php echo $categoryId ? '&category_id=' . $categoryId : ''; ?>" 
                           class="btn border-0 bg-transparent text-muted px-3 d-flex align-items-center justify-content-center" 
                           title="Xóa tìm kiếm">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Submit Search button -->
            <div class="col-auto">
                <button type="submit" class="btn btn-primary-yumgo h-100 px-4" style="height: 48px !important;">
                    Tìm
                </button>
            </div>
        </div>
    </form>

    <!-- Category Filter Chips -->
    <div class="mb-4">
        <div class="category-strip">
            <!-- "Tất cả" chip link -->
            <a href="index.php?page=foods<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
               class="category-pill <?php echo empty($categoryId) ? 'active' : ''; ?>">
                🍔 Tất Cả
            </a>
            <?php 
            if (!empty($categories)):
                foreach ($categories as $cat):
                    $isActive = ($categoryId !== null && (int)$cat['id'] === (int)$categoryId);
                    // Determine emoji
                    $catName = mb_strtolower($cat['name']);
                    $emoji = '🍽️';
                    if (str_contains($catName, 'gà')) { $emoji = '🍗'; }
                    elseif (str_contains($catName, 'pizza')) { $emoji = '🍕'; }
                    elseif (str_contains($catName, 'trà sữa') || str_contains($catName, 'sữa')) { $emoji = '🧋'; }
                    elseif (str_contains($catName, 'mì') || str_contains($catName, 'ý')) { $emoji = '🍝'; }
            ?>
                    <a href="index.php?page=foods&category_id=<?php echo $cat['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="category-pill <?php echo $isActive ? 'active' : ''; ?>">
                        <?php echo $emoji . ' ' . htmlspecialchars($cat['name']); ?>
                    </a>
            <?php 
                endforeach;
            endif; 
            ?>
        </div>
    </div>

    <!-- Foods Grid List -->
    <div class="row g-3 g-md-4">
        <?php 
        if (!empty($foods)):
            foreach ($foods as $food) {
                require __DIR__ . '/partials/food-card.php';
            }
        else:
            // Empty State (No Search Result / No Foods in category)
            $emptyIcon = 'bi-search-heart';
            $emptyTitle = 'Không tìm thấy kết quả!';
            $emptyDesc = 'Không tìm thấy món ăn nào khớp với từ khóa "' . htmlspecialchars($search) . '" hoặc danh mục được lọc. Thử tìm kiếm từ khóa khác xem sao nhé.';
            $emptyBtnText = 'Xem tất cả món ăn';
            $emptyBtnUrl = 'index.php?page=foods';
            require __DIR__ . '/partials/empty-state.php';
        endif; 
        ?>
    </div>

    <!-- Pagination Controls (Grid of square rounded-sm buttons) -->
    <?php if ($totalPages > 1 && !empty($foods)): ?>
        <nav class="d-flex justify-content-center mt-5">
            <ul class="pagination gap-2 border-0 m-0">
                <!-- Page numbers loops -->
                <?php 
                for ($p = 1; $p <= $totalPages; $p++):
                    $isActivePage = ((int)$p === (int)$page);
                    
                    // Build query string keeping search & category filter intact
                    $linkParams = "?page=foods";
                    if (!empty($search)) {
                        $linkParams .= "&search=" . urlencode($search);
                    }
                    if ($categoryId) {
                        $linkParams .= "&category_id=" . $categoryId;
                    }
                    $linkParams .= "&p=" . $p;
                ?>
                    <li class="page-item border-0">
                        <a class="page-link d-flex align-items-center justify-content-center border fw-semibold text-decoration-none rounded-sm" 
                           style="width: 40px; height: 40px; 
                                  background-color: <?php echo $isActivePage ? 'var(--yumgo-primary)' : 'var(--yumgo-canvas)'; ?>; 
                                  color: <?php echo $isActivePage ? 'var(--yumgo-on-primary)' : 'var(--yumgo-ink)'; ?>;
                                  border-color: <?php echo $isActivePage ? 'var(--yumgo-primary)' : 'var(--yumgo-hairline)'; ?>;"
                           href="index.php<?php echo $linkParams; ?>">
                            <?php echo $p; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
