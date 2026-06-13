<?php
$title = 'Thanh toán - YumGO';
require_once 'views/layouts/header.php';

$shippingFee = 15000;
$estimatedTotal = $subtotal + $shippingFee;
$checkoutUser = isset($checkoutUser) && is_array($checkoutUser) ? $checkoutUser : ($_SESSION['user'] ?? []);
$checkoutName = (string)($checkoutUser['name'] ?? '');
$checkoutPhone = (string)($checkoutUser['phone'] ?? '');
$checkoutAddress = (string)($checkoutUser['address'] ?? '');
$selectedItemsValue = isset($selectedIds) && $selectedIds ? implode(',', $selectedIds) : '';
$availableVouchers = isset($availableVouchers) && is_array($availableVouchers) ? $availableVouchers : [];
$checkoutError = $_SESSION['checkout_error'] ?? '';
$checkoutOld = $_SESSION['checkout_old'] ?? [];
unset($_SESSION['checkout_error'], $_SESSION['checkout_old']);
if (is_array($checkoutOld) && $checkoutOld) {
    $checkoutName = (string)($checkoutOld['full_name'] ?? $checkoutName);
    $checkoutPhone = (string)($checkoutOld['phone'] ?? $checkoutPhone);
    $checkoutAddress = (string)($checkoutOld['address'] ?? $checkoutAddress);
}
?>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-md-7">
            <h4 class="mb-3">Thông tin giao hàng</h4>
            <?php if ($checkoutError !== ''): ?>
                <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                    <div><?= htmlspecialchars($checkoutError) ?></div>
                </div>
            <?php endif; ?>
            <form
                action="index.php?page=process-checkout"
                method="POST"
                data-checkout-form="1"
                data-subtotal="<?= htmlspecialchars(number_format($subtotal, 0, ',', '.')) ?>đ"
                data-shipping="<?= htmlspecialchars(number_format($shippingFee, 0, ',', '.')) ?>đ"
                data-total="<?= htmlspecialchars(number_format($estimatedTotal, 0, ',', '.')) ?>đ"
            >
                <?= csrfField() ?>
                <input type="hidden" name="selected_items" value="<?= htmlspecialchars($selectedItemsValue) ?>">

                <div class="mb-3">
                    <label for="fullName" class="form-label">Họ và tên</label>
                    <input type="text" class="form-control" id="fullName" name="full_name" value="<?= htmlspecialchars($checkoutName) ?>" autocomplete="name" required>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Số điện thoại</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($checkoutPhone) ?>" pattern="[0-9]{10,11}" autocomplete="tel" required>
                </div>
                <div class="mb-3">
                    <label for="address" class="form-label">Địa chỉ nhận hàng</label>
                    <textarea class="form-control" id="address" name="address" rows="3" autocomplete="street-address" required><?= htmlspecialchars($checkoutAddress) ?></textarea>
                    <small class="text-muted d-block mt-1">Bạn có thể nhập vị trí khác địa chỉ tài khoản. Khoảng cách và phí ship sẽ tính theo địa chỉ này.</small>
                    <small class="text-muted" id="checkout-delivery-status">Phí ship sẽ được tính tự động theo địa chỉ.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hình thức nhận hàng</label>
                    <div class="d-flex flex-wrap gap-3">
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="delivery_type" value="delivery" checked>
                            <span class="form-check-label">Giao hàng tận nơi</span>
                        </label>
                        <label class="form-check">
                            <input class="form-check-input" type="radio" name="delivery_type" value="pickup">
                            <span class="form-check-label">Tôi đến lấy</span>
                        </label>
                    </div>
                    <small class="text-muted">Nếu chọn đến lấy, phí ship sẽ là 0đ.</small>
                </div>
                <div class="mb-3">
                    <label for="note" class="form-label">Ghi chú giao hàng</label>
                    <textarea class="form-control" id="note" name="note" rows="2" placeholder="Ví dụ: gọi trước khi giao, ít đá, không cay..."></textarea>
                    <small class="text-muted">Ghi chú được lưu dạng TEXT trong cột orders.note để admin và shipper đọc khi xử lý đơn.</small>
                </div>

                <h4 class="mb-3 mt-4">Phương thức thanh toán</h4>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="payment_method" id="paymentCOD" value="COD" checked>
                    <label class="form-check-label" for="paymentCOD">Thanh toán khi nhận hàng (COD)</label>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="radio" name="payment_method" id="paymentTransfer" value="Banking">
                    <label class="form-check-label" for="paymentTransfer">Chuyển khoản ngân hàng</label>
                </div>

                <div class="card p-3 mb-4" style="background: var(--yumgo-canvas-soft); border-color: var(--yumgo-hairline) !important;">
                    <label for="voucher_code" class="form-label fw-bold">Mã giảm giá</label>
                    <select class="form-select" name="voucher_code" id="voucher_code">
                        <option value="">Không áp dụng voucher</option>
                        <?php foreach ($availableVouchers as $voucher): ?>
                            <?php
                            $discountPreview = $voucher['type'] === 'percent'
                                ? min($subtotal, $subtotal * ((float)$voucher['value'] / 100))
                                : min($subtotal, (float)$voucher['value']);
                            ?>
                            <option value="<?= htmlspecialchars($voucher['code']) ?>" data-discount="<?= (float)$discountPreview ?>">
                                <?= htmlspecialchars($voucher['code']) ?> - giảm <?= number_format($discountPreview, 0, ',', '.') ?>đ
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted mt-1 d-block">Chỉ hiện voucher còn hạn và đủ điều kiện theo tạm tính.</small>
                </div>

                <button class="btn btn-primary-yumgo w-100 py-2 mt-3" id="checkout-submit-btn" type="submit">Xác nhận đặt hàng</button>
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
                                <small class="text-muted">Số lượng: <?= (int)$item['quantity'] ?></small>
                            </div>
                            <span class="text-muted"><?= number_format($item['itemTotal'], 0, ',', '.') ?>đ</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex justify-content-between mt-3">
                    <span>Tổng tạm tính:</span>
                    <strong><?= number_format($subtotal, 0, ',', '.') ?>đ</strong>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <span>Phí vận chuyển:</span>
                    <strong id="checkout-shipping-fee"><?= number_format($shippingFee, 0, ',', '.') ?>đ</strong>
                </div>
                <div class="d-flex justify-content-between mt-2 text-success">
                    <span>Voucher giảm:</span>
                    <strong id="checkout-voucher-discount">0đ</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between text-success">
                    <span>Thành tiền:</span>
                    <strong id="checkout-final-total"><?= number_format($estimatedTotal, 0, ',', '.') ?>đ</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const voucher = document.getElementById('voucher_code');
    const discountEl = document.getElementById('checkout-voucher-discount');
    const totalEl = document.getElementById('checkout-final-total');
    const shippingEl = document.getElementById('checkout-shipping-fee');
    const addressEl = document.getElementById('address');
    const deliveryStatusEl = document.getElementById('checkout-delivery-status');
    const form = document.querySelector('form[data-checkout-form="1"]');
    const submitBtn = document.getElementById('checkout-submit-btn');
    const deliveryTypeEls = document.querySelectorAll('input[name="delivery_type"]');
    let shipping = <?= (float)$shippingFee ?>;
    let deliveryBlocked = false;
    const subtotal = <?= (float)$subtotal ?>;
    const quoteUrl = '<?= htmlspecialchars(BASE_URL . '/api/delivery_quote.php', ENT_QUOTES) ?>';
    const fmt = (value) => new Intl.NumberFormat('vi-VN').format(Math.max(0, value)) + 'đ';

    const sync = () => {
        const selected = voucher?.selectedOptions?.[0];
        const discount = Number(selected?.dataset.discount || 0);
        if (discountEl) discountEl.textContent = '-' + fmt(discount);
        if (shippingEl) shippingEl.textContent = fmt(shipping);
        if (totalEl) totalEl.textContent = fmt(subtotal + shipping - discount);
        if (submitBtn) {
            submitBtn.disabled = deliveryBlocked;
            submitBtn.textContent = deliveryBlocked ? 'Địa chỉ vượt phạm vi giao hàng' : 'Xác nhận đặt hàng';
        }
        if (form) {
            form.dataset.shipping = fmt(shipping);
            form.dataset.total = fmt(subtotal + shipping - discount);
        }
    };

    let quoteTimer = null;
    const requestQuote = () => {
        const address = (addressEl?.value || '').trim();
        const deliveryType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'delivery';
        if (deliveryType === 'pickup') {
            shipping = 0;
            deliveryBlocked = false;
            if (deliveryStatusEl) deliveryStatusEl.textContent = 'Bạn chọn đến lấy tại quán, không tính phí ship.';
            if (deliveryStatusEl) deliveryStatusEl.className = 'text-success';
            sync();
            return;
        }
        if (!address) {
            shipping = <?= (float)$shippingFee ?>;
            deliveryBlocked = false;
            if (deliveryStatusEl) deliveryStatusEl.textContent = 'Phí ship sẽ được tính tự động theo địa chỉ.';
            if (deliveryStatusEl) deliveryStatusEl.className = 'text-muted';
            sync();
            return;
        }

        if (deliveryStatusEl) deliveryStatusEl.textContent = 'Đang tính phí ship...';
        if (deliveryStatusEl) deliveryStatusEl.className = 'text-muted';
        const url = quoteUrl + '?' + new URLSearchParams({ format: 'json', address, delivery_type: deliveryType }).toString();
        fetch(url, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data) => {
                if (data && data.shipping_fee !== null && data.shipping_fee !== undefined) {
                    shipping = Number(data.shipping_fee) || 0;
                }
                deliveryBlocked = data?.delivery_status === 'too_far';
                if (deliveryStatusEl) {
                    const distance = data?.distance_km !== null && data?.distance_km !== undefined
                        ? ' - ' + Number(data.distance_km).toLocaleString('vi-VN', { maximumFractionDigits: 2 }) + 'km'
                        : '';
                    deliveryStatusEl.textContent = (data?.message || data?.delivery_status_label || 'Đã tính phí ship.') + distance;
                    deliveryStatusEl.className = deliveryBlocked ? 'text-danger fw-semibold' : 'text-success';
                }
                sync();
            })
            .catch(() => {
                deliveryBlocked = false;
                if (deliveryStatusEl) deliveryStatusEl.textContent = 'Chưa tính được phí ship, hệ thống sẽ tính lại khi đặt hàng.';
                if (deliveryStatusEl) deliveryStatusEl.className = 'text-warning';
                sync();
            });
    };

    const scheduleQuote = () => {
        clearTimeout(quoteTimer);
        quoteTimer = setTimeout(requestQuote, 500);
    };

    voucher?.addEventListener('change', sync);
    addressEl?.addEventListener('input', scheduleQuote);
    addressEl?.addEventListener('blur', requestQuote);
    deliveryTypeEls.forEach((input) => input.addEventListener('change', requestQuote));
    form?.addEventListener('submit', (event) => {
        if (deliveryBlocked) {
            event.preventDefault();
            if (deliveryStatusEl) {
                deliveryStatusEl.textContent = 'Địa chỉ vượt quá phạm vi 10km. Vui lòng chọn đến lấy hoặc nhập vị trí khác.';
                deliveryStatusEl.className = 'text-danger fw-semibold';
            }
            addressEl?.focus();
        }
    });
    sync();
    requestQuote();
});
</script>

<?php require_once 'views/layouts/footer.php'; ?>
