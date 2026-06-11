<?php
$title = "Tra cứu đơn hàng - YumGO";
require_once 'views/layouts/header.php';

$code = $_GET['code'] ?? '';
$phone = trim($_GET['phone'] ?? '');
$requiresVerification = $requiresVerification ?? false;
$errorMsg = $errorMsg ?? '';
?>

<style>
/* Custom Mobile Styles for Order Tracking */
.timeline-vertical-line {
    position: absolute;
    left: 10px;
    top: 10px;
    bottom: 10px;
    width: 2px;
    border-left: 2px dashed var(--yumgo-hairline, #dee2e6);
    z-index: 1;
}
.timeline-step {
    padding-left: 35px;
    min-height: 40px;
}
.timeline-dot-wrapper {
    position: absolute;
    left: 0;
    top: 2px;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
}
.timeline-dot.completed {
    width: 22px;
    height: 22px;
    background-color: #198754;
    color: #fff;
    font-size: 0.75rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.timeline-dot.pending {
    width: 12px;
    height: 12px;
    background-color: #fff;
    border: 2px solid var(--yumgo-hairline, #dee2e6);
    border-radius: 50%;
}
.timeline-label {
    font-size: 0.95rem;
}
.text-orange {
    color: var(--yumgo-primary, #fd7e14) !important;
}
.mobile-food-card {
    transition: transform 0.2s ease;
}
.mobile-food-card:active {
    transform: scale(0.98);
}

/* Custom SweetAlert2 for Reorder Popup */
.reorder-swal-popup {
    border-radius: 20px !important;
    padding: 24px !important;
    background: var(--yumgo-surface-card, #ffffff) !important;
    border: 1px solid var(--yumgo-hairline, #e5e7eb) !important;
}
.reorder-swal-popup .swal2-title {
    font-size: 1.3rem !important;
    font-weight: 700 !important;
    color: var(--yumgo-ink, #1a1a2e) !important;
    margin-top: 15px !important;
    margin-bottom: 8px !important;
}
.reorder-swal-popup .swal2-html-container {
    font-size: 0.95rem !important;
    color: var(--yumgo-body, #4a4a68) !important;
    margin-bottom: 24px !important;
}
.reorder-swal-popup .swal2-actions {
    display: flex !important;
    flex-direction: column !important;
    gap: 10px !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}
.reorder-swal-popup .swal2-actions button {
    width: 100% !important;
    margin: 0 !important;
    padding: 12px 16px !important;
    font-size: 0.95rem !important;
    font-weight: 600 !important;
    border-radius: 12px !important;
    transition: all 0.2s ease !important;
    height: 48px !important;
    box-sizing: border-box !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border: none !important;
}
.reorder-swal-popup .swal2-confirm {
    background-color: var(--yumgo-primary, #fd7e14) !important;
    color: #fff !important;
    box-shadow: 0 4px 12px rgba(253, 126, 20, 0.2) !important;
    order: 1;
}
.reorder-swal-popup .swal2-confirm:hover {
    background-color: var(--yumgo-primary-hover, #e85a00) !important;
}
.reorder-swal-popup .swal2-deny {
    background-color: var(--yumgo-canvas-soft, #f8f9fa) !important;
    color: var(--yumgo-ink, #1a1a2e) !important;
    border: 1px solid var(--yumgo-hairline, #e5e7eb) !important;
    order: 2;
}
.reorder-swal-popup .swal2-deny:hover {
    background-color: var(--yumgo-hairline, #e5e7eb) !important;
}
.reorder-swal-popup .swal2-cancel {
    background-color: transparent !important;
    color: var(--yumgo-ink, #1a1a2e) !important;
    border: 1px solid var(--yumgo-hairline, #e5e7eb) !important;
    order: 3;
}
.reorder-swal-popup .swal2-cancel:hover {
    background-color: var(--yumgo-canvas-soft, #f8f9fa) !important;
    color: var(--yumgo-ink, #1a1a2e) !important;
    border-color: var(--yumgo-hairline, #e5e7eb) !important;
}
body[data-theme="dark"] .reorder-swal-popup .swal2-cancel {
    color: var(--yumgo-ink) !important;
    border: 1px solid var(--yumgo-hairline) !important;
}
body[data-theme="dark"] .reorder-swal-popup .swal2-cancel:hover {
    background-color: var(--yumgo-hairline) !important;
    color: var(--yumgo-ink) !important;
}
</style>

<div class="container py-4 py-md-5 order-page order-tracking-page" style="min-height: 70vh;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if ($order): ?>
                <!-- GIAO DIỆN PC (DESKTOP) -->
                <div class="card shadow-sm mb-4 order-card d-none d-md-block" style="border-radius: 16px; border: 1px solid var(--yumgo-hairline); background: var(--yumgo-surface-card);">
                    <div class="card-body py-4">
                        <h4 class="mb-4 text-center fw-bold">Tra cứu trạng thái đơn hàng</h4>
                        
                        <!-- Phần hiển thị đơn hàng + Copy Button -->
                        <div class="alert alert-info border-0 mb-4 d-flex align-items-center justify-content-between gap-3 order-code-panel" style="background: var(--yumgo-primary-soft); color: var(--yumgo-ink); border-radius: 12px;">
                            <div>
                                <h5 class="mb-1 fw-bold text-dark">Thông tin đơn hàng</h5>
                                <p class="mb-0 text-secondary small">Mã đơn hàng của bạn</p>
                                <div class="mt-2 p-2 rounded shadow-sm border" style="display: inline-block; background: var(--yumgo-canvas); border-color: var(--yumgo-hairline) !important;">
                                    <strong style="color: var(--yumgo-ink);">Mã đơn hàng:</strong>
                                    <span id="orderCodeValue" class="text-primary fw-bold order-code-value font-monospace"><?= htmlspecialchars($order['order_code']) ?></span>
                                </div>
                            </div>
                            <button id="copyOrderCodeBtn" type="button" class="btn btn-outline-primary flex-shrink-0 order-action-btn" style="border-radius: 100px;">
                                <i class="bi bi-clipboard"></i> Copy mã đơn
                            </button>
                        </div>

                        <!-- Giao diện Tracking Timeline -->
                        <?php
                            $statusList = ['Placed', 'Preparing', 'Ready', 'Delivering', 'Delivered'];
                            $statusLabels = [
                                'Placed' => 'Đã đặt hàng',
                                'Preparing' => 'Đang chuẩn bị',
                                'Ready' => 'Sẵn sàng giao',
                                'Delivering' => 'Đang giao',
                                'Delivered' => 'Đã giao',
                                'Cancelled' => 'Đã hủy',
                                'Cancelled by Customer' => 'Khách đã hủy',
                                'Cancelled by Restaurant' => 'Nhà hàng đã hủy',
                            ];
                            $currentStatus = $order['status'];
                            $currentStatusLabel = $statusLabels[$currentStatus] ?? (strpos($currentStatus, 'Cancelled') !== false ? 'Đã hủy' : $currentStatus);
                            
                            $isCancelled = strpos($currentStatus, 'Cancelled') !== false;
                            $currentIndex = array_search($currentStatus, $statusList);
                        ?>

                        <div class="tracking-timeline order-timeline py-4 position-relative text-center">
                            <?php if ($isCancelled): ?>
                                <h4 class="text-danger mb-3"><i class="bi bi-x-circle-fill"></i> ĐƠN HÀNG ĐÃ BỊ HỦY</h4>
                                <p class="text-muted">Cập nhật: <?= htmlspecialchars($currentStatusLabel) ?></p>
                            <?php else: ?>
                                <h4 class="text-success mb-4"><i class="bi bi-check-circle-fill"></i> Trạng thái: <?= htmlspecialchars($currentStatusLabel) ?></h4>
                                <div class="d-flex justify-content-between position-relative fs-5 text-muted order-timeline-track">
                                    <div class="progress order-timeline-progress" style="height: 4px; position: absolute; top: 15px; left: 10%; right: 10%; z-index: 1;">
                                        <?php 
                                            $percent = ($currentIndex > 0) ? ($currentIndex / (count($statusList) - 1)) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percent ?>%"></div>
                                    </div>
                                    
                                    <?php foreach ($statusList as $index => $statusName): ?>
                                        <div class="position-relative order-timeline-step" style="z-index: 2; flex: 1;">
                                            <?php if ($currentIndex >= $index): ?>
                                                <i class="bi bi-check-circle-fill text-success fs-3"></i>
                                            <?php else: ?>
                                                <i class="bi bi-circle-fill text-light border rounded-circle fs-3 text-white"></i>
                                            <?php endif; ?>
                                            <div class="mt-2 order-timeline-label <?= ($currentIndex >= $index) ? 'fw-bold text-dark' : 'text-muted' ?>">
                                                <?= htmlspecialchars($statusLabels[$statusName] ?? $statusName) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Chi tiết đơn hàng -->
                        <div class="mt-4 pt-3 border-top">
                            <h5 class="mb-3 fw-bold text-dark" style="font-size: 1.1rem;">Thông tin chi tiết</h5>
                            <div class="row mb-4 order-detail-grid g-3">
                                <div class="col-md-7">
                                    <div class="p-3 rounded-3 border h-100 shadow-sm" style="background: var(--yumgo-primary-soft); border: 1px solid rgba(255, 102, 0, 0.25) !important; border-left: 5px solid var(--yumgo-primary) !important;">
                                        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2" style="font-size: 0.95rem; border-bottom: 1px solid rgba(255, 102, 0, 0.15); padding-bottom: 8px; color: var(--yumgo-primary);">
                                            <i class="bi bi-geo-alt-fill"></i> Thông tin nhận hàng
                                        </h6>
                                        <div class="d-flex flex-column gap-2" style="font-size: 0.9rem;">
                                            <div class="p-2 rounded d-flex align-items-center justify-content-between" style="background: var(--yumgo-canvas); border: 1px solid var(--yumgo-hairline);">
                                                <div>
                                                    <span class="text-secondary fw-medium" style="width: 110px; display: inline-block;">Người nhận:</span>
                                                    <strong class="text-dark-theme-ink" style="color: var(--yumgo-ink);"><?= htmlspecialchars($order['customer_name']) ?></strong>
                                                </div>
                                            </div>
                                            <div class="p-2 rounded d-flex align-items-center justify-content-between" style="background: var(--yumgo-canvas); border: 1px solid var(--yumgo-hairline);">
                                                <div>
                                                    <span class="text-secondary fw-medium" style="width: 110px; display: inline-block;">Số điện thoại:</span>
                                                    <strong class="text-dark-theme-ink" style="color: var(--yumgo-ink);"><?= htmlspecialchars($order['phone']) ?></strong>
                                                </div>
                                            </div>
                                            <div class="p-2 rounded" style="background: var(--yumgo-canvas); border: 1px solid var(--yumgo-hairline);">
                                                <div class="d-flex align-items-start">
                                                    <span class="text-secondary fw-medium" style="width: 110px; display: inline-block; flex-shrink: 0;">Địa chỉ giao:</span>
                                                    <span class="fw-semibold text-dark-theme-ink flex-grow-1" style="color: var(--yumgo-ink);"><?= nl2br(htmlspecialchars($order['address'])) ?></span>
                                                </div>
                                            </div>
                                            <?php if (!empty($order['note'])): ?>
                                                <div class="p-2 rounded bg-danger-subtle border border-danger-subtle">
                                                    <div class="d-flex align-items-start">
                                                        <span class="text-danger fw-medium" style="width: 110px; display: inline-block; flex-shrink: 0;">Ghi chú:</span>
                                                        <span class="text-danger fw-bold"><?= htmlspecialchars($order['note']) ?></span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="p-3 rounded-3 border h-100" style="background: var(--yumgo-canvas-soft); border-color: var(--yumgo-hairline) !important;">
                                        <h6 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2" style="font-size: 0.95rem; border-bottom: 1px solid var(--yumgo-hairline); padding-bottom: 8px;">
                                            <i class="bi bi-credit-card-2-front-fill text-primary"></i> Giao dịch & Thời gian
                                        </h6>
                                        <div class="d-flex flex-column gap-2" style="font-size: 0.9rem;">
                                            <div>
                                                <span class="text-secondary fw-medium" style="width: 130px; display: inline-block;">Phương thức:</span>
                                                <span class="badge bg-secondary-subtle text-secondary border px-2.5 py-1 fw-semibold"><?= htmlspecialchars($order['payment_method']) ?></span>
                                            </div>
                                            <div>
                                                <span class="text-secondary fw-medium" style="width: 130px; display: inline-block;">Ngày đặt hàng:</span>
                                                <strong class="text-dark"><?= date('d/m/Y H:i:s', strtotime($order['created_at'])) ?></strong>
                                            </div>
                                            <div>
                                                <span class="text-secondary fw-medium" style="width: 130px; display: inline-block;">Mã đơn hàng:</span>
                                                <code class="text-primary fw-bold font-monospace"><?= htmlspecialchars($order['order_code']) ?></code>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="order-items-table-wrap">
                            <table class="table align-middle border-bottom order-items-table">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Món ăn</th>
                                        <th class="text-center" style="width: 100px;">Số lượng</th>
                                        <th class="text-end pe-3" style="width: 150px;">Tạm tính</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): 
                                        $imageFile = $item['image'] ?? '';
                                        $imageExists = !empty($imageFile) && file_exists('uploads/foods/' . $imageFile);
                                        $imagePath = $imageExists ? 'uploads/foods/' . htmlspecialchars($imageFile) : '';
                                    ?>
                                    <tr>
                                        <td class="ps-3 py-2">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="food-img-wrapper flex-shrink-0" style="width: 50px; height: 50px; border-radius: 8px; overflow: hidden; background: var(--yumgo-hairline); border: 1px solid var(--yumgo-hairline);">
                                                    <?php if ($imageExists): ?>
                                                        <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($item['food_name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center fw-bold text-uppercase" style="font-size: 0.95rem; background: var(--yumgo-hairline); color: var(--yumgo-muted);">
                                                            <?= mb_substr($item['food_name'], 0, 1) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($item['food_name']) ?></div>
                                            </div>
                                        </td>
                                        <td class="text-center fw-medium"><?= $item['quantity'] ?></td>
                                        <td class="text-end pe-3 fw-semibold text-dark"><?= number_format($item['subtotal'], 0, ',', '.') ?> đ</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                            <div class="text-end order-total-box">
                                <p class="mb-1 text-secondary body-md">Tạm tính: <span class="fw-semibold text-dark"><?= number_format($order['subtotal'], 0, ',', '.') ?> đ</span></p>
                                <p class="mb-1 text-secondary body-md">Phí vận chuyển: <span class="fw-semibold text-dark"><?= number_format($order['shipping_fee'], 0, ',', '.') ?> đ</span></p>
                                <?php if ($order['discount_amount'] > 0): ?>
                                    <p class="mb-1 text-success body-md">Mã giảm giá (<?= htmlspecialchars($order['voucher_code']) ?>): -<?= number_format($order['discount_amount'], 0, ',', '.') ?> đ</p>
                                <?php endif; ?>
                                <h5 class="mt-2 text-danger fw-bold">Tổng thanh toán: <?= number_format($order['total'], 0, ',', '.') ?> đ</h5>
                            </div>
                        </div>

                        <!-- Khu vực Thao tác Hủy / Sửa Đơn ($canCancel, $canEdit) -->
                        <?php
                            $canCancel = in_array($currentStatus, ['Placed', 'Preparing']);
                            $canEdit = ($currentStatus === 'Placed' && $order['edit_count'] < 2 && strtotime($order['editable_until']) > time());
                        ?>
                        <?php if ($canCancel || $canEdit): ?>
                            <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2 order-action-group">
                                <?php if ($canEdit): ?>
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editOrderModal">
                                        <i class="bi bi-pencil-square"></i> Cập nhật thông tin
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($canCancel): ?>
                                    <form action="index.php?page=cancel-order" method="POST" class="cancel-order-form m-0">
                                        <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-trash"></i> Hủy đơn
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Nút Mua lại -->
                                <a href="index.php?page=reorder&code=<?= htmlspecialchars($code) ?>" class="btn btn-primary-yumgo px-4 btn-reorder" data-code="<?= htmlspecialchars($code) ?>" data-cart-count="<?= count($_SESSION['cart'] ?? []) ?>">
                                    <i class="bi bi-arrow-repeat"></i> Mua lại đơn này
                                </a>

                                <!-- Nút xuất hóa đơn luôn có -->
                                <a href="index.php?page=order-invoice&code=<?= htmlspecialchars($code) ?>" target="_blank" class="btn btn-secondary">
                                    <i class="bi bi-printer"></i> Hóa đơn PDF
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2 order-action-group">
                                <a href="index.php?page=reorder&code=<?= htmlspecialchars($code) ?>" class="btn btn-primary-yumgo px-4 btn-reorder" data-code="<?= htmlspecialchars($code) ?>" data-cart-count="<?= count($_SESSION['cart'] ?? []) ?>">
                                    <i class="bi bi-arrow-repeat"></i> Mua lại đơn này
                                </a>
                                <a href="index.php?page=order-invoice&code=<?= htmlspecialchars($code) ?>" target="_blank" class="btn btn-secondary">
                                    <i class="bi bi-printer"></i> In Hóa đơn PDF
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- GIAO DIỆN DI ĐỘNG (MOBILE) -->
                <div class="d-md-none mobile-tracking-section">
                    <!-- Nút quay lại -->
                    <div class="mb-3">
                        <a href="index.php?page=order-history" class="text-decoration-none text-secondary fw-semibold" style="font-size: 0.95rem;">
                            <i class="bi bi-arrow-left"></i> Chi tiết đơn hàng
                        </a>
                    </div>

                    <!-- Mobile Card -->
                    <div class="card shadow-sm mb-4" style="border-radius: 16px; border: 1px solid var(--yumgo-hairline); background: var(--yumgo-surface-card); overflow: hidden;">
                        <div class="card-body p-3">
                            <!-- Tiêu đề + Trạng thái -->
                            <div class="d-flex align-items-start justify-content-between mb-2">
                                <div>
                                    <h4 class="fw-bold mb-1 text-dark">#<?= htmlspecialchars($order['id']) ?></h4>
                                    <div class="fw-semibold" style="font-size: 0.9rem;">
                                        <?php
                                            $statusList = ['Placed', 'Preparing', 'Ready', 'Delivering', 'Delivered'];
                                            $statusLabels = [
                                                'Placed' => 'Đã đặt hàng',
                                                'Preparing' => 'Đang chuẩn bị',
                                                'Ready' => 'Sẵn sàng giao',
                                                'Delivering' => 'Đang giao',
                                                'Delivered' => 'Đã giao',
                                                'Cancelled' => 'Đã hủy',
                                                'Cancelled by Customer' => 'Khách đã hủy',
                                                'Cancelled by Restaurant' => 'Nhà hàng đã hủy',
                                            ];
                                            $currentStatus = $order['status'];
                                            $currentStatusLabel = $statusLabels[$currentStatus] ?? (strpos($currentStatus, 'Cancelled') !== false ? 'Đã hủy' : $currentStatus);
                                            $isCancelled = strpos($currentStatus, 'Cancelled') !== false;
                                            $currentIndex = array_search($currentStatus, $statusList);
                                        ?>
                                        <?php if ($isCancelled): ?>
                                            <span class="text-danger"><i class="bi bi-x-circle-fill"></i> <?= htmlspecialchars($currentStatusLabel) ?></span>
                                        <?php else: ?>
                                            <span class="text-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($currentStatusLabel) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button id="copyOrderCodeBtnMobile" type="button" class="btn btn-sm btn-outline-primary py-1 px-2 border" style="border-radius: 8px; font-size: 0.8rem;">
                                        <i class="bi bi-clipboard"></i> Copy mã
                                    </button>
                                    <div id="orderCodeValueMobile" class="d-none"><?= htmlspecialchars($order['order_code']) ?></div>
                                </div>
                            </div>
                            <div class="text-muted mb-3 small">
                                <i class="bi bi-calendar3"></i> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                            </div>

                            <!-- Trạng thái Timeline -->
                            <?php if ($isCancelled): ?>
                                <div class="alert alert-danger border-0 mb-4 text-center py-3 rounded-3" style="background: rgba(220, 53, 69, 0.08); color: #dc3545;">
                                    <i class="bi bi-x-circle-fill fs-3"></i>
                                    <h5 class="mt-2 fw-bold mb-1">Đơn hàng đã bị hủy</h5>
                                    <p class="mb-0 small text-secondary">Trạng thái: <?= htmlspecialchars($currentStatusLabel) ?></p>
                                </div>
                            <?php else: ?>
                                <div class="border-top pt-3 mb-4">
                                    <div class="timeline-title text-secondary fw-bold small mb-3 text-uppercase text-center" style="letter-spacing: 0.5px;">Trạng thái</div>
                                    <div class="d-flex justify-content-center">
                                        <div class="position-relative ps-2 timeline-steps-wrapper" style="width: fit-content; min-width: 170px;">
                                            <div class="timeline-vertical-line"></div>
                                            <?php foreach ($statusList as $index => $statusName): 
                                                $isCompletedStep = $currentIndex >= $index;
                                            ?>
                                                <div class="timeline-step mb-3 position-relative d-flex align-items-center">
                                                    <div class="timeline-dot-wrapper">
                                                        <?php if ($isCompletedStep): ?>
                                                            <div class="timeline-dot completed"><i class="bi bi-check-lg"></i></div>
                                                        <?php else: ?>
                                                            <div class="timeline-dot pending"></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="timeline-label-wrapper ps-2">
                                                        <span class="timeline-label <?= $isCompletedStep ? 'fw-bold text-dark' : 'text-muted' ?>">
                                                            <?= htmlspecialchars($statusLabels[$statusName] ?? $statusName) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Danh sách món ăn -->
                            <div class="border-top pt-3 mb-4">
                                <div class="items-title text-secondary fw-bold small mb-3 text-uppercase" style="letter-spacing: 0.5px;">Món ăn</div>
                                <?php foreach ($items as $item): 
                                    $imageFile = $item['image'] ?? '';
                                    $imageExists = !empty($imageFile) && file_exists('uploads/foods/' . $imageFile);
                                    $imagePath = $imageExists ? 'uploads/foods/' . htmlspecialchars($imageFile) : '';
                                ?>
                                    <div class="mobile-food-card d-flex align-items-center p-2 mb-2 rounded-3" style="background: var(--yumgo-canvas-soft); border: 1px solid var(--yumgo-hairline) !important;">
                                        <div class="food-img-wrapper me-3 flex-shrink-0" style="width: 50px; height: 50px; border-radius: 8px; overflow: hidden; background: var(--yumgo-hairline); border: 1px solid var(--yumgo-hairline);">
                                            <?php if ($imageExists): ?>
                                                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($item['food_name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="w-100 h-100 d-flex align-items-center justify-content-center fw-bold text-uppercase" style="font-size: 1.1rem; background: var(--yumgo-hairline); color: var(--yumgo-muted);">
                                                    <?= mb_substr($item['food_name'], 0, 1) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="food-info-wrapper flex-grow-1 min-w-0">
                                            <div class="food-name fw-semibold text-dark text-truncate" style="font-size: 0.9rem;"><?= htmlspecialchars($item['food_name']) ?></div>
                                            <div class="food-qty-price d-flex align-items-center justify-content-between mt-1 text-muted small">
                                                <span>x<?= $item['quantity'] ?></span>
                                                <span class="fw-bold text-dark"><?= number_format($item['subtotal'], 0, ',', '.') ?> đ</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Tổng cộng -->
                            <div class="mobile-pricing-summary p-3 rounded-3 mb-4" style="background: var(--yumgo-primary-soft, #fff8f0); border: 1px dashed var(--yumgo-primary, #fd7e14);">
                                <div class="d-flex justify-content-between mb-2 small text-secondary">
                                    <span>Tạm tính</span>
                                    <span><?= number_format($order['subtotal'], 0, ',', '.') ?> đ</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 small text-secondary">
                                    <span>Phí ship</span>
                                    <span><?= number_format($order['shipping_fee'], 0, ',', '.') ?> đ</span>
                                </div>
                                <?php if ($order['discount_amount'] > 0): ?>
                                    <div class="d-flex justify-content-between mb-2 small text-success">
                                        <span>Giảm giá (<?= htmlspecialchars($order['voucher_code']) ?>)</span>
                                        <span>-<?= number_format($order['discount_amount'], 0, ',', '.') ?> đ</span>
                                    </div>
                                <?php endif; ?>
                                <div class="border-top my-2" style="border-top-style: dashed !important; border-top-color: var(--yumgo-hairline) !important;"></div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark" style="font-size: 0.95rem;">Tổng cộng</span>
                                    <span class="fw-bold text-orange fs-5" style="color: var(--yumgo-primary, #fd7e14) !important;">
                                        <?= number_format($order['total'], 0, ',', '.') ?> đ
                                    </span>
                                </div>
                            </div>

                            <!-- Thông tin nhận hàng thu gọn -->
                            <div class="mobile-delivery-info mb-4 p-3 rounded-3 border shadow-sm" style="font-size: 0.85rem; background: var(--yumgo-primary-soft); border: 1px solid rgba(255, 102, 0, 0.25) !important; border-left: 5px solid var(--yumgo-primary) !important;">
                                <div class="fw-bold mb-2 pb-2 border-bottom d-flex align-items-center gap-2" style="font-size: 0.85rem; tracking: 0.5px; border-bottom-color: rgba(255, 102, 0, 0.15) !important; color: var(--yumgo-primary);">
                                    <i class="bi bi-geo-alt-fill"></i> THÔNG TIN NHẬN HÀNG
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div class="p-2 rounded text-dark" style="background: var(--yumgo-canvas); border: 1px solid var(--yumgo-hairline); color: var(--yumgo-ink) !important;">
                                        <span class="text-secondary fw-medium">Người nhận:</span> <strong style="color: var(--yumgo-ink);"><?= htmlspecialchars($order['customer_name']) ?></strong>
                                    </div>
                                    <div class="p-2 rounded text-dark" style="background: var(--yumgo-canvas); border: 1px solid var(--yumgo-hairline); color: var(--yumgo-ink) !important;">
                                        <span class="text-secondary fw-medium">SĐT:</span> <strong style="color: var(--yumgo-ink);"><?= htmlspecialchars($order['phone']) ?></strong>
                                    </div>
                                    <div class="p-2 rounded text-dark d-flex flex-wrap gap-1" style="background: var(--yumgo-canvas); border: 1px solid var(--yumgo-hairline); color: var(--yumgo-ink) !important;">
                                        <span class="text-secondary fw-medium">Địa chỉ:</span> <span class="fw-medium text-dark-subtle flex-grow-1 text-wrap" style="color: var(--yumgo-ink);"><?= htmlspecialchars($order['address']) ?></span>
                                    </div>
                                    <?php if (!empty($order['note'])): ?>
                                        <div class="p-2 rounded bg-danger-subtle border border-danger-subtle text-dark" style="color: #dc3545 !important;">
                                            <span class="text-danger fw-medium">Ghi chú:</span> <span class="text-danger fw-semibold"><?= htmlspecialchars($order['note']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="p-2 rounded text-dark" style="background: var(--yumgo-canvas); border: 1px solid var(--yumgo-hairline); color: var(--yumgo-ink) !important;">
                                        <span class="text-secondary fw-medium">Thanh toán:</span> <span class="badge bg-secondary-subtle text-secondary px-2 py-1 fw-semibold border"><?= htmlspecialchars($order['payment_method']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Nút thao tác dạng Stack dọc -->
                            <?php
                                $canCancel = in_array($currentStatus, ['Placed', 'Preparing']);
                                $canEdit = ($currentStatus === 'Placed' && $order['edit_count'] < 2 && strtotime($order['editable_until']) > time());
                            ?>
                            <div class="mobile-actions d-grid gap-2">
                                <?php if ($canEdit): ?>
                                    <button type="button" class="btn btn-warning py-2 w-100" data-bs-toggle="modal" data-bs-target="#editOrderModal" style="border-radius: 12px; font-weight: 600; font-size: 0.95rem;">
                                        <i class="bi bi-pencil-square"></i> Cập nhật thông tin
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($canCancel): ?>
                                    <form action="index.php?page=cancel-order" method="POST" class="cancel-order-form w-100 m-0">
                                        <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
                                        <button type="submit" class="btn btn-outline-danger py-2 w-100" style="border-radius: 12px; font-weight: 600; font-size: 0.95rem;">
                                            <i class="bi bi-trash"></i> Hủy đơn hàng
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Nút Mua lại -->
                                <a href="index.php?page=reorder&code=<?= htmlspecialchars($code) ?>" class="btn btn-primary-yumgo py-2 w-100 d-flex align-items-center justify-content-center gap-1 btn-reorder" style="border-radius: 12px; font-weight: 600; font-size: 0.95rem;" data-code="<?= htmlspecialchars($code) ?>" data-cart-count="<?= count($_SESSION['cart'] ?? []) ?>">
                                    <i class="bi bi-arrow-repeat"></i> Mua lại đơn hàng
                                </a>

                                <a href="index.php?page=order-invoice&code=<?= htmlspecialchars($code) ?>" target="_blank" class="btn btn-secondary py-2 w-100 d-flex align-items-center justify-content-center gap-1" style="border-radius: 12px; font-weight: 600; font-size: 0.95rem;">
                                    <i class="bi bi-file-earmark-pdf"></i> Tải hóa đơn PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- GIAO DIỆN CHUNG KHI CHƯA TÌM THẤY ĐƠN HOẶC XÁC THỰC -->
                <div class="card shadow-sm mb-4 order-card" style="border-radius: 16px; border: 1px solid var(--yumgo-hairline); background: var(--yumgo-surface-card);">
                    <div class="card-body py-4">
                        <h4 class="mb-4 text-center fw-bold">Tra cứu trạng thái đơn hàng</h4>
                        
                        <?php if ($requiresVerification): ?>
                            <!-- Giao diện yêu cầu nhập SĐT xác thực -->
                            <div class="alert alert-warning border-0 mb-4 text-center py-4 rounded-3" style="background: var(--yumgo-primary-soft); color: var(--yumgo-ink);">
                                <i class="bi bi-shield-lock-fill text-primary" style="font-size: 2.8rem;"></i>
                                <h5 class="mt-3 fw-bold text-dark">Xác thực số điện thoại</h5>
                                <p class="mb-0 text-secondary small px-3">Vui lòng nhập số điện thoại đặt hàng của mã đơn <strong><?= htmlspecialchars($code) ?></strong> để bảo mật và hiển thị thông tin chi tiết.</p>
                            </div>
                            
                            <?php if ($errorMsg !== ''): ?>
                                <div class="alert alert-danger border-0 text-center small py-2 rounded-3 mb-3"><?= htmlspecialchars($errorMsg) ?></div>
                            <?php endif; ?>

                            <form action="index.php?page=order-tracking&code=<?= urlencode($code) ?>" method="POST" class="row g-3 justify-content-center">
                                <div class="col-md-8 text-center">
                                    <label class="form-label small fw-bold text-secondary mb-2">Số điện thoại đặt hàng</label>
                                    <input type="tel" name="phone" class="form-control text-center fs-5 fw-bold py-2" placeholder="Ví dụ: 0909123456" pattern="[0-9]{10,11}" required autofocus style="border-radius: 8px;">
                                </div>
                                <div class="col-md-8 d-flex justify-content-between align-items-center mt-4 pt-2">
                                    <a href="index.php?page=order-history" class="btn btn-outline-primary" style="border-radius: 100px; padding: 8px 20px;">
                                        <i class="bi bi-clock-history"></i> Lịch sử đơn
                                    </a>
                                    <button type="submit" class="btn btn-primary-yumgo px-4 py-2" style="border-radius: 100px;">Xác nhận</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- Phần form tra cứu -->
                            <form action="index.php" method="GET" class="row g-3 mb-4">
                                <input type="hidden" name="page" value="order-tracking">

                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-bold text-secondary">Mã đơn hàng</label>
                                    <input type="text" name="code" value="<?= htmlspecialchars($code) ?>" class="form-control" placeholder="Ví dụ: YUMGO-20260609-1234" required style="border-radius: 8px;">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-bold text-secondary">Số điện thoại</label>
                                    <input type="tel" name="phone" value="<?= htmlspecialchars($phone) ?>" class="form-control" placeholder="Nhập số điện thoại" pattern="[0-9]{10,11}" required style="border-radius: 8px;">
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center order-form-actions mt-4">
                                    <a href="index.php?page=order-history" class="btn btn-outline-primary" style="border-radius: 100px; padding: 8px 20px;">
                                        <i class="bi bi-clock-history"></i> Lịch sử đơn hàng
                                    </a>
                                    <button type="submit" class="btn btn-primary-yumgo px-4 py-2" style="border-radius: 100px;">Tra cứu</button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <?php if (!$code && !$requiresVerification): ?>
                            <div class="alert alert-info border-0 text-center small rounded-3" style="background: rgba(13, 110, 253, 0.08); color: #0d6efd;">
                                Vui lòng nhập mã đơn hàng và số điện thoại để tra cứu trạng thái.
                            </div>
                        <?php elseif ($code && !$requiresVerification): ?>
                            <div class="alert alert-danger border-0 text-center small rounded-3" style="background: rgba(220, 53, 69, 0.08); color: #dc3545;">
                                Không tìm thấy đơn hàng với mã: <strong><?= htmlspecialchars($code) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($order): ?>
                <div class="text-center order-page-actions d-none d-md-block">
                    <a href="index.php?page=home" class="btn btn-outline-primary me-2">Tiếp tục mua hàng</a>
                    <a href="index.php?page=order-history" class="btn btn-primary-yumgo">Lịch sử đơn hàng</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Edit Order -->
<?php if (isset($canEdit) && $canEdit): ?>
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editOrderModalLabel">Chỉnh sửa thông tin nhận hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=order-edit" method="POST">
                <div class="modal-body text-start">
                    <div class="alert alert-warning p-2 text-sm text-center">
                        Bạn còn <strong class="text-danger"><?= 2 - $order['edit_count'] ?></strong> lần chỉnh sửa đơn.
                    </div>
                    <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">SĐT nhận hàng</label>
                        <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($order['phone']) ?>" pattern="[0-9]{10,11}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ</label>
                        <textarea class="form-control" name="address" required><?= htmlspecialchars($order['address']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú (Tùy chọn)</label>
                        <textarea class="form-control" name="note"><?= htmlspecialchars($order['note']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phương thức thanh toán</label>
                        <select name="payment_method" class="form-select">
                            <option value="COD" <?= $order['payment_method'] === 'COD' ? 'selected' : '' ?>>Tiền mặt (COD)</option>
                            <option value="Banking" <?= in_array($order['payment_method'], ['Banking', 'Transfer'], true) ? 'selected' : '' ?>>Chuyển khoản</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary-yumgo">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function setupCopyButton(btnId, valId) {
            var btn = document.getElementById(btnId);
            if (btn) {
                btn.addEventListener('click', function () {
                    var valEl = document.getElementById(valId);
                    if (valEl) {
                        var code = valEl.textContent.trim();
                        navigator.clipboard.writeText(code).then(function () {
                            Swal.fire({
                                icon: 'success',
                                title: 'Đã sao chép mã đơn',
                                timer: 1600,
                                showConfirmButton: false
                            });
                        }).catch(function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'Sao chép thất bại',
                                text: 'Vui lòng sao chép mã đơn theo cách thủ công.'
                            });
                        });
                    }
                });
            }
        }
        setupCopyButton('copyOrderCodeBtn', 'orderCodeValue');
        setupCopyButton('copyOrderCodeBtnMobile', 'orderCodeValueMobile');

        document.querySelectorAll('.cancel-order-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                Swal.fire({
                    title: 'Bạn có chắc chắn muốn hủy đơn hàng này không?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'OK',
                    cancelButtonText: 'Hủy',
                    reverseButtons: true
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Xử lý nút Mua lại đơn hàng với phân luồng Popup thông minh
        document.querySelectorAll('.btn-reorder').forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                var code = this.getAttribute('data-code');
                var cartCount = parseInt(this.getAttribute('data-cart-count') || '0');

                if (cartCount === 0) {
                    // Giỏ hàng rỗng -> Mua lại ngay lập tức
                    window.location.href = 'index.php?page=reorder&code=' + encodeURIComponent(code) + '&mode=replace';
                } else {
                    // Giỏ hàng có sản phẩm -> Hiện popup lựa chọn (SweetAlert2)
                    Swal.fire({
                        title: 'Giỏ hàng không trống',
                        html: '<div class="text-start fs-6" style="color: var(--yumgo-body); display: flex; flex-direction: column; gap: 12px; font-weight: 500;">' +
                              '<p class="mb-0"><strong>🛒 Mua lại đơn này</strong><br><span style="opacity: 0.85; font-size: 0.88rem;">→ Xóa giỏ hiện tại và tạo giỏ mới từ đơn cũ</span></p>' +
                              '<p class="mb-0"><strong>➕ Thêm vào giỏ hiện tại</strong><br><span style="opacity: 0.85; font-size: 0.88rem;">→ Giữ nguyên giỏ hiện tại và thêm các món từ đơn cũ</span></p>' +
                              '</div>',
                        icon: 'question',
                        iconHtml: '🛒',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Mua lại đơn này',
                        denyButtonText: 'Thêm vào giỏ hiện tại',
                        cancelButtonText: 'Hủy',
                        customClass: {
                            popup: 'reorder-swal-popup'
                        },
                        buttonsStyling: false,
                        reverseButtons: true
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            window.location.href = 'index.php?page=reorder&code=' + encodeURIComponent(code) + '&mode=replace';
                        } else if (result.isDenied) {
                            window.location.href = 'index.php?page=reorder&code=' + encodeURIComponent(code) + '&mode=merge';
                        }
                    });
                }
            });
        });
    });
</script>

<?php require_once 'views/layouts/footer.php'; ?>
