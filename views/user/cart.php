<?php
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$errorMsg = $_SESSION['cart_error'] ?? null;
unset($_SESSION['cart_error']);

$successMessages = [
    'added' => 'Đã thêm món ăn vào giỏ hàng thành công.',
    'updated' => 'Cập nhật số lượng thành công.',
    'removed' => 'Đã xóa món ăn khỏi giỏ hàng.',
];
$successMsg = $successMessages[$_GET['msg'] ?? ''] ?? null;
?>

<div class="container px-3 py-4">
    <div class="mb-4">
        <h1 class="display-lg mb-1">Giỏ hàng của bạn</h1>
        <p class="body-md text-secondary">Tick chọn món muốn thanh toán, sau đó kiểm tra lại số lượng trước khi đặt hàng.</p>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-md" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($successMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-md" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <?php
        $emptyIcon = 'bi-bag-x';
        $emptyTitle = 'Giỏ hàng đang trống';
        $emptyDesc = 'Chưa có món ăn nào trong giỏ. Hãy quay lại thực đơn để chọn các món ngon từ YumGO.';
        $emptyBtnText = 'Đặt món ngay';
        $emptyBtnUrl = 'index.php?page=foods';
        require __DIR__ . '/partials/empty-state.php';
        ?>
    <?php else: ?>
        <div class="row gy-4">
            <div class="col-lg-8">
                <div class="card rounded-md border shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive d-none d-md-block">
                            <table class="table align-middle m-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3 text-secondary text-uppercase fw-bold" style="font-size: 11px; width: 56px;">Chọn</th>
                                        <th class="py-3 text-secondary text-uppercase fw-bold" style="font-size: 11px;">Món ăn</th>
                                        <th class="py-3 text-secondary text-uppercase fw-bold" style="font-size: 11px; width: 120px;">Đơn giá</th>
                                        <th class="py-3 text-secondary text-uppercase fw-bold text-center" style="font-size: 11px; width: 140px;">Số lượng</th>
                                        <th class="py-3 text-secondary text-uppercase fw-bold text-end" style="font-size: 11px; width: 120px;">Tổng</th>
                                        <th class="pe-4 py-3" style="width: 56px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>
                                        <?php
                                        $imageFile = !empty($item['image']) ? $item['image'] : '';
                                        $imageExists = !empty($imageFile) && file_exists(PATH_ROOT . '/uploads/foods/' . $imageFile);
                                        ?>
                                        <tr class="border-bottom" data-food-id="<?= htmlspecialchars((string)$item['food_id']) ?>">
                                            <td class="ps-4 py-3">
                                                <input class="form-check-input cart-select-item" type="checkbox" value="<?= (int)$item['food_id'] ?>" data-total="<?= (float)$item['item_total'] ?>">
                                            </td>
                                            <td class="py-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded-md overflow-hidden" style="width: 60px; height: 60px; flex-shrink: 0;">
                                                        <?php if ($imageExists): ?>
                                                            <img src="uploads/foods/<?= htmlspecialchars($imageFile) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                                        <?php else: ?>
                                                            <div class="d-flex align-items-center justify-content-center w-100 h-100" style="background: linear-gradient(135deg, #FFF0E6 0%, #FFD4B3 100%); font-size: 24px;">
                                                                <i class="bi bi-egg-fried"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="title-md m-0"><?= htmlspecialchars($item['name']) ?></h6>
                                                        <span class="fs-7 text-muted">YumGO Premium</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3"><span class="body-md fw-semibold"><?= number_format($item['final_price'], 0, ',', '.') ?>đ</span></td>
                                            <td class="py-3 text-center">
                                                <div class="d-inline-flex align-items-center border rounded-pill p-1" style="background: var(--yumgo-canvas-soft); border-color: var(--yumgo-hairline) !important;">
                                                    <form action="index.php?page=cart-update" method="POST" class="m-0">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="food_id" value="<?= (int)$item['food_id'] ?>">
                                                        <input type="hidden" name="quantity" value="<?= (int)$item['quantity'] - 1 ?>">
                                                        <button type="submit" class="btn btn-sm btn-light border-0 rounded-circle fw-bold p-0" style="width: 24px; height: 24px; line-height: 1;">-</button>
                                                    </form>
                                                    <span class="px-3 fw-bold body-md"><?= (int)$item['quantity'] ?></span>
                                                    <form action="index.php?page=cart-update" method="POST" class="m-0">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="food_id" value="<?= (int)$item['food_id'] ?>">
                                                        <input type="hidden" name="quantity" value="<?= (int)$item['quantity'] + 1 ?>">
                                                        <button type="submit" class="btn btn-sm btn-light border-0 rounded-circle fw-bold p-0" style="width: 24px; height: 24px; line-height: 1;">+</button>
                                                    </form>
                                                </div>
                                            </td>
                                            <td class="py-3 text-end"><span class="price-display fw-bold"><?= number_format($item['item_total'], 0, ',', '.') ?>đ</span></td>
                                            <td class="pe-4 py-3 text-end">
                                                <a href="index.php?page=cart-remove&id=<?= (int)$item['food_id'] ?>&csrf_token=<?= urlencode(csrfToken()) ?>" class="border-0 bg-transparent text-decoration-none fs-5 hover-scale" title="Xóa món" style="color: #ff4d4d !important;">
                                                    <i class="bi bi-x-circle"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-block d-md-none p-3">
                            <?php foreach ($cartItems as $item): ?>
                                <?php
                                $imageFile = !empty($item['image']) ? $item['image'] : '';
                                $imageExists = !empty($imageFile) && file_exists(PATH_ROOT . '/uploads/foods/' . $imageFile);
                                ?>
                                <div class="d-flex align-items-center gap-3 py-3 border-bottom position-relative" data-food-id="<?= htmlspecialchars((string)$item['food_id']) ?>">
                                    <input class="form-check-input cart-select-item" type="checkbox" value="<?= (int)$item['food_id'] ?>" data-total="<?= (float)$item['item_total'] ?>">
                                    <div class="rounded-md overflow-hidden" style="width: 70px; height: 70px; flex-shrink: 0;">
                                        <?php if ($imageExists): ?>
                                            <img src="uploads/foods/<?= htmlspecialchars($imageFile) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center w-100 h-100" style="background: linear-gradient(135deg, #FFF0E6 0%, #FFD4B3 100%); font-size: 28px;">
                                                <i class="bi bi-egg-fried"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden pe-4">
                                        <h6 class="title-md text-truncate mb-1 pe-2"><?= htmlspecialchars($item['name']) ?></h6>
                                        <div class="d-flex align-items-center justify-content-between mt-2">
                                            <span class="price-display fw-bold"><?= number_format($item['item_total'], 0, ',', '.') ?>đ</span>
                                            <div class="d-inline-flex align-items-center border rounded-pill p-1" style="background: var(--yumgo-canvas-soft); border-color: var(--yumgo-hairline) !important;">
                                                <form action="index.php?page=cart-update" method="POST" class="m-0">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="food_id" value="<?= (int)$item['food_id'] ?>">
                                                    <input type="hidden" name="quantity" value="<?= (int)$item['quantity'] - 1 ?>">
                                                    <button type="submit" class="btn btn-sm btn-light border-0 rounded-circle fw-bold p-0" style="width: 24px; height: 24px; line-height: 1;">-</button>
                                                </form>
                                                <span class="px-2 fw-bold" style="font-size: 13px;"><?= (int)$item['quantity'] ?></span>
                                                <form action="index.php?page=cart-update" method="POST" class="m-0">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="food_id" value="<?= (int)$item['food_id'] ?>">
                                                    <input type="hidden" name="quantity" value="<?= (int)$item['quantity'] + 1 ?>">
                                                    <button type="submit" class="btn btn-sm btn-light border-0 rounded-circle fw-bold p-0" style="width: 24px; height: 24px; line-height: 1;">+</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="index.php?page=cart-remove&id=<?= (int)$item['food_id'] ?>&csrf_token=<?= urlencode(csrfToken()) ?>" class="position-absolute top-2 end-0 text-decoration-none fs-5" title="Xóa món" style="top: 10px; color: #ff4d4d !important;">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card rounded-md border shadow-sm bg-canvas p-4">
                    <h5 class="fw-bold mb-3 display-md border-bottom pb-2">Hóa đơn tạm tính</h5>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-secondary body-md">Đã chọn (<span id="cart-unique-count">0</span> món)</span>
                        <span class="fw-semibold body-lg text-dark" id="cart-selected-subtotal">0đ</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="text-secondary body-md">Phí vận chuyển</span>
                        <span class="text-muted body-md">Tính tại checkout</span>
                    </div>
                    <hr style="border-color: var(--yumgo-hairline);">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold text-dark body-lg">Tổng thanh toán</span>
                        <span class="price-display fs-4 fw-bold" id="cart-selected-total">0đ</span>
                    </div>
                    <a href="#" id="cart-checkout-link" class="btn btn-primary-yumgo w-100 py-3 d-flex align-items-center justify-content-center gap-2 disabled" aria-disabled="true" style="font-size: 16px;">
                        Chọn món để thanh toán <i class="bi bi-arrow-right-short fs-4"></i>
                    </a>
                    <p class="text-muted text-center mt-3 mb-0" style="font-size: 11px;">
                        <i class="bi bi-info-circle me-1"></i> Mã giảm giá và phí giao hàng sẽ được áp dụng tại trang tiếp theo.
                    </p>
                </div>
            </div>
        </div>

        <?php if (!empty($cartSuggestionFoods)): ?>
            <section class="cart-suggestions mt-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-3">
                    <div>
                        <span class="home-kicker">Gợi ý ăn kèm</span>
                        <h2 class="display-md mb-1">Thêm món cho đủ vị</h2>
                        <p class="body-md text-secondary mb-0">Các món còn hàng, ưu tiên khác nhóm với giỏ hiện tại.</p>
                    </div>
                    <a href="index.php?page=foods&availability=available" class="text-primary fw-semibold text-decoration-none">Xem thêm món</a>
                </div>
                <div class="row g-3 g-md-4">
                    <?php foreach ($cartSuggestionFoods as $food): ?>
                        <?php require __DIR__ . '/partials/food-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const boxes = Array.from(document.querySelectorAll('.cart-select-item'));
    const subtotalEl = document.getElementById('cart-selected-subtotal');
    const totalEl = document.getElementById('cart-selected-total');
    const countEl = document.getElementById('cart-unique-count');
    const checkoutLink = document.getElementById('cart-checkout-link');
    const formatMoney = (value) => new Intl.NumberFormat('vi-VN').format(value) + 'đ';

    const sync = () => {
        const selected = boxes.filter((box) => box.checked);
        const ids = selected.map((box) => box.value);
        const total = selected.reduce((sum, box) => sum + Number(box.dataset.total || 0), 0);
        if (subtotalEl) subtotalEl.textContent = formatMoney(total);
        if (totalEl) totalEl.textContent = formatMoney(total);
        if (countEl) countEl.textContent = selected.length;
        if (checkoutLink) {
            checkoutLink.href = ids.length ? 'index.php?page=checkout&items=' + encodeURIComponent(ids.join(',')) : '#';
            checkoutLink.classList.toggle('disabled', ids.length === 0);
            checkoutLink.setAttribute('aria-disabled', ids.length === 0 ? 'true' : 'false');
            checkoutLink.firstChild.textContent = ids.length ? 'Tiến hành đặt hàng ' : 'Chọn món để thanh toán ';
        }
    };

    boxes.forEach((box) => box.addEventListener('change', sync));
    sync();
});
</script>
