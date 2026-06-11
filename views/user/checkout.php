<?php
$title = "Thanh toán - YumGO";
require_once 'views/layouts/header.php';
$shippingFee = 15000;
$estimatedTotal = $subtotal + $shippingFee;
$checkoutUser = isset($checkoutUser) && is_array($checkoutUser) ? $checkoutUser : ($_SESSION['user'] ?? []);
$checkoutName = (string)($checkoutUser['name'] ?? '');
$checkoutPhone = (string)($checkoutUser['phone'] ?? '');
$checkoutAddress = (string)($checkoutUser['address'] ?? '');
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-7">
            <h4 class="mb-3">Thông tin giao hàng</h4>
            <form
                action="index.php?page=process-checkout"
                method="POST"
                data-checkout-form="1"
                data-subtotal="<?= htmlspecialchars(number_format($subtotal, 0, ',', '.')) ?> đ"
                data-shipping="<?= htmlspecialchars(number_format($shippingFee, 0, ',', '.')) ?> đ"
                data-total="<?= htmlspecialchars(number_format($estimatedTotal, 0, ',', '.')) ?> đ"
            >
                <div class="mb-3">
                    <label for="fullName" class="form-label">Họ và tên</label>
                    <input type="text" class="form-control" id="fullName" name="full_name" value="<?= htmlspecialchars($checkoutName) ?>" autocomplete="name" required>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Số điện thoại</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($checkoutPhone) ?>" pattern="[0-9]{10,11}" title="Vui lòng nhập số điện thoại hợp lệ" autocomplete="tel" required>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Địa chỉ giao hàng</label>
                    <textarea class="form-control" id="address" name="address" rows="3" autocomplete="street-address" required><?= htmlspecialchars($checkoutAddress) ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="note" class="form-label">Ghi chú (Tùy chọn)</label>
                    <textarea class="form-control" id="note" name="note" rows="2"></textarea>
                </div>

                <h4 class="mb-3 mt-4">Phương thức thanh toán</h4>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="payment_method" id="paymentCOD" value="COD" checked>
                    <label class="form-check-label" for="paymentCOD">
                        Thanh toán khi nhận hàng (COD)
                    </label>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="radio" name="payment_method" id="paymentTransfer" value="Banking">
                    <label class="form-check-label" for="paymentTransfer">
                        Chuyển khoản ngân hàng
                    </label>
                </div>

                <div class="card p-3 mb-4" style="background: var(--yumgo-canvas-soft); border-color: var(--yumgo-hairline) !important;">
                    <label for="voucher_code" class="form-label fw-bold">Mã giảm giá (Voucher)</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="voucher_code" id="voucher_code" placeholder="Nhập mã ưu đãi...">
                    </div>
                    <small class="text-muted mt-1 d-block">Mã giảm giá sẽ được kiểm tra ở bước cuối cùng.</small>
                </div>

                <button class="btn btn-primary-yumgo w-100 py-2 mt-3" type="submit">Xác nhận đặt hàng</button>
            </form>
        </div>
        
        <div class="col-md-5">
            <div class="card shadow-sm p-4">
                <h4 class="mb-3">Tóm tắt đơn hàng</h4>
                <ul class="list-group mb-3">
                    <?php foreach ($cartItems as $item): ?>
                        <li class="list-group-item d-flex justify-content-between lh-sm">
                            <div>
                                <h6 class="my-0"><?= htmlspecialchars($item['food']['name']) ?></h6>
                                <small class="text-muted">SL: <?= $item['quantity'] ?></small>
                            </div>
                            <span class="text-muted"><?= number_format($item['itemTotal'], 0, ',', '.') ?> đ</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex justify-content-between mt-3">
                    <span>Tổng tạm tính:</span>
                    <strong><?= number_format($subtotal, 0, ',', '.') ?> đ</strong>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <span>Phí vận chuyển:</span>
                    <strong><?= number_format($shippingFee, 0, ',', '.') ?> đ</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between text-success">
                    <span>Thành tiền (Dự kiến):</span>
                    <strong><?= number_format($estimatedTotal, 0, ',', '.') ?> đ</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'views/layouts/footer.php'; ?>
