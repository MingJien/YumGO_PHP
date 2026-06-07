<?php
/**
 * YumGO - View Giỏ hàng (cart.php)
 */

// Chặn truy cập trực tiếp
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// Kiểm tra thông báo lỗi trong Session và xóa ngay sau khi đọc
$errorMsg = null;
if (isset($_SESSION['cart_error'])) {
    $errorMsg = $_SESSION['cart_error'];
    unset($_SESSION['cart_error']);
}

// Kiểm tra thông báo thành công từ URL
$msgType = $_GET['msg'] ?? '';
$successMsg = null;
if ($msgType === 'added') {
    $successMsg = "Đã thêm món ăn vào giỏ hàng thành công!";
} elseif ($msgType === 'updated') {
    $successMsg = "Cập nhật số lượng thành công!";
} elseif ($msgType === 'removed') {
    $successMsg = "Đã xóa món ăn khỏi giỏ hàng.";
}
?>

<div class="container px-3 py-4">
    <!-- Header Title -->
    <div class="mb-4">
        <h1 class="display-lg mb-1">Giỏ Hàng Của Bạn</h1>
        <p class="body-md text-secondary">Kiểm tra lại thực đơn đã chọn trước khi thanh toán.</p>
    </div>

    <!-- Alert Notices -->
    <?php if ($successMsg): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-md" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?php echo htmlspecialchars($successMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-md" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($errorMsg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($cartItems)): ?>
        <!-- Render Empty Cart view when there are no items -->
        <?php 
        $emptyIcon = 'bi-bag-x';
        $emptyTitle = 'Giỏ hàng trống!';
        $emptyDesc = 'Không có món ăn nào trong giỏ. Hãy quay lại thực đơn để chọn các món ngon lành từ YumGO nhé!';
        $emptyBtnText = 'Khám phá thực đơn';
        $emptyBtnUrl = 'index.php?page=foods';
        require __DIR__ . '/partials/empty-state.php';
        ?>
    <?php else: ?>
        <div class="row gy-4">
            <!-- Left Side: List of Items (8 Columns) -->
            <div class="col-lg-8">
                <div class="card rounded-md border shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive d-none d-md-block">
                            <!-- Desktop View: Grid Table -->
                            <table class="table align-middle m-0" style="border-collapse: collapse;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3 text-secondary text-uppercase fw-bold" style="font-size: 11px;">Món ăn</th>
                                        <th class="py-3 text-secondary text-uppercase fw-bold" style="font-size: 11px; width: 120px;">Đơn giá</th>
                                        <th class="py-3 text-secondary text-uppercase fw-bold text-center" style="font-size: 11px; width: 140px;">Số lượng</th>
                                        <th class="py-3 text-secondary text-uppercase fw-bold text-end" style="font-size: 11px; width: 120px;">Tổng</th>
                                        <th class="pe-4 py-3" style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>
                                        <tr class="border-bottom">
                                            <!-- Product Details -->
                                            <td class="ps-4 py-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <!-- Miniature Image with ratio check -->
                                                    <div class="rounded-md overflow-hidden" style="width: 60px; height: 60px; flex-shrink: 0;">
                                                        <?php 
                                                        $imageFile = !empty($item['image']) ? $item['image'] : '';
                                                        $imageExists = !empty($imageFile) && file_exists(PATH_ROOT . '/uploads/foods/' . $imageFile);
                                                        
                                                        if ($imageExists): 
                                                        ?>
                                                            <img src="uploads/foods/<?php echo $imageFile; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                                        <?php else: ?>
                                                            <div class="d-flex align-items-center justify-content-center w-100 h-100" style="background: linear-gradient(135deg, #FFF0E6 0%, #FFD4B3 100%); font-size: 24px;">
                                                                🍔
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <!-- Name & Description -->
                                                    <div>
                                                        <h6 class="title-md m-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                        <span class="fs-7 text-muted">YumGO Premium</span>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <!-- Price -->
                                            <td class="py-3">
                                                <span class="body-md fw-semibold"><?php echo number_format($item['final_price'], 0, ',', '.'); ?>đ</span>
                                            </td>
                                            
                                            <!-- Quantity update buttons -->
                                            <td class="py-3 text-center">
                                                <div class="d-inline-flex align-items-center border rounded-pill bg-light p-1">
                                                    <!-- Minus Form -->
                                                    <form action="index.php?page=cart-update" method="POST" class="m-0">
                                                        <input type="hidden" name="food_id" value="<?php echo $item['food_id']; ?>">
                                                        <input type="hidden" name="quantity" value="<?php echo $item['quantity'] - 1; ?>">
                                                        <button type="submit" class="btn btn-sm btn-light border-0 rounded-circle fw-bold p-0" style="width: 24px; height: 24px; line-height: 1;">-</button>
                                                    </form>
                                                    
                                                    <span class="px-3 fw-bold body-md"><?php echo $item['quantity']; ?></span>
                                                    
                                                    <!-- Plus Form -->
                                                    <form action="index.php?page=cart-update" method="POST" class="m-0">
                                                        <input type="hidden" name="food_id" value="<?php echo $item['food_id']; ?>">
                                                        <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                                        <button type="submit" class="btn btn-sm btn-light border-0 rounded-circle fw-bold p-0" style="width: 24px; height: 24px; line-height: 1;">+</button>
                                                    </form>
                                                </div>
                                            </td>
                                            
                                            <!-- Subtotal -->
                                            <td class="py-3 text-end">
                                                <span class="price-display fw-bold"><?php echo number_format($item['item_total'], 0, ',', '.'); ?>đ</span>
                                            </td>
                                            
                                            <!-- Remove action -->
                                            <td class="pe-4 py-3 text-end">
                                                <a href="index.php?page=cart-remove&id=<?php echo $item['food_id']; ?>" class="text-danger border-0 bg-transparent text-decoration-none fs-5 hover-scale" title="Xóa món">
                                                    <i class="bi bi-x-circle"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View: Card List (< 768px) -->
                        <div class="d-block d-md-none p-3">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="d-flex align-items-center gap-3 py-3 border-bottom position-relative">
                                    <!-- Miniature image -->
                                    <div class="rounded-md overflow-hidden" style="width: 70px; height: 70px; flex-shrink: 0;">
                                        <?php 
                                        $imageFile = !empty($item['image']) ? $item['image'] : '';
                                        $imageExists = !empty($imageFile) && file_exists(PATH_ROOT . '/uploads/foods/' . $imageFile);
                                        
                                        if ($imageExists): 
                                        ?>
                                            <img src="uploads/foods/<?php echo $imageFile; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center w-100 h-100" style="background: linear-gradient(135deg, #FFF0E6 0%, #FFD4B3 100%); font-size: 28px;">
                                                🍔
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Item specifications -->
                                    <div class="flex-grow-1 overflow-hidden pe-4">
                                        <h6 class="title-md text-truncate mb-1 pe-2"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <div class="d-flex align-items-center justify-content-between mt-2">
                                            <!-- Subtotal price -->
                                            <span class="price-display fw-bold"><?php echo number_format($item['item_total'], 0, ',', '.'); ?>đ</span>
                                            
                                            <!-- Quantity buttons pill -->
                                            <div class="d-inline-flex align-items-center border rounded-pill bg-light p-1">
                                                <form action="index.php?page=cart-update" method="POST" class="m-0">
                                                    <input type="hidden" name="food_id" value="<?php echo $item['food_id']; ?>">
                                                    <input type="hidden" name="quantity" value="<?php echo $item['quantity'] - 1; ?>">
                                                    <button type="submit" class="btn btn-sm btn-light border-0 rounded-circle fw-bold p-0" style="width: 24px; height: 24px; line-height: 1;">-</button>
                                                </form>
                                                
                                                <span class="px-2 fw-bold" style="font-size: 13px;"><?php echo $item['quantity']; ?></span>
                                                
                                                <form action="index.php?page=cart-update" method="POST" class="m-0">
                                                    <input type="hidden" name="food_id" value="<?php echo $item['food_id']; ?>">
                                                    <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                                    <button type="submit" class="btn btn-sm btn-light border-0 rounded-circle fw-bold p-0" style="width: 24px; height: 24px; line-height: 1;">+</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete cross button -->
                                    <a href="index.php?page=cart-remove&id=<?php echo $item['food_id']; ?>" 
                                       class="text-danger position-absolute top-2 end-0 text-decoration-none fs-5"
                                       title="Xóa món"
                                       style="top: 10px;">
                                        <i class="bi bi-x-circle"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Order Summary Card (4 Columns) -->
            <div class="col-lg-4">
                <div class="card rounded-md border shadow-sm bg-canvas p-4">
                    <h5 class="fw-bold mb-3 display-md border-bottom pb-2">Hóa đơn tạm tính</h5>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-secondary body-md">Tạm tính (<?php echo count($cartItems); ?> món)</span>
                        <span class="fw-semibold body-lg text-dark"><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="text-secondary body-md">Phí vận chuyển</span>
                        <span class="text-muted body-md">Tính tại checkout</span>
                    </div>

                    <hr style="border-color: var(--yumgo-hairline);">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold text-dark body-lg">Tổng thanh toán</span>
                        <span class="price-display fs-4 fw-bold"><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</span>
                    </div>

                    <!-- Integration Checkout Button (Redirection to TV3) -->
                    <a href="index.php?page=checkout" class="btn btn-primary-yumgo w-100 py-3 d-flex align-items-center justify-content-center gap-2" style="font-size: 16px;">
                        Tiến hành đặt hàng <i class="bi bi-arrow-right-short fs-4"></i>
                    </a>

                    <!-- Voucher notice -->
                    <p class="text-muted text-center mt-3 mb-0" style="font-size: 11px;">
                        <i class="bi bi-info-circle me-1"></i> Mã giảm giá (Vouchers) và phí giao hàng sẽ được áp dụng tại trang tiếp theo.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
