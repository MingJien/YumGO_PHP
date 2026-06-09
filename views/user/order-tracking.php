<?php
$title = "Tra cứu đơn hàng - YumGO";
require_once 'views/layouts/header.php';

$code = $_GET['code'] ?? '';
$phone = trim($_GET['phone'] ?? '');
?>

<div class="container py-5" style="min-height: 70vh;">
    <div class="row w-100 justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body py-4">
                    <h4 class="mb-4 text-center">Tra cứu Trạng thái Đơn hàng</h4>
                    
                    <?php if ($order): ?>
                        <!-- Phần hiển thị đơn hàng + Copy Button -->
                        <div class="alert alert-info border-0 mb-4 d-flex align-items-center justify-content-between gap-3">
                            <div>
                                <h5 class="mb-1">Thông tin đơn hàng</h5>
                                <p class="mb-0 text-secondary">Mã đơn hàng của bạn</p>
                                <div class="mt-2 p-2 bg-white rounded" style="display: inline-block;">
                                    <strong>Mã đơn hàng:</strong>
                                    <span id="orderCodeValue" class="text-primary fw-bold"><?= htmlspecialchars($order['order_code']) ?></span>
                                </div>
                            </div>
                            <button id="copyOrderCodeBtn" type="button" class="btn btn-outline-primary flex-shrink-0" style="white-space: nowrap;">
                                <i class="bi bi-clipboard"></i> Copy mã đơn
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- Phần form tra cứu -->
                        <form action="index.php" method="GET" class="row g-3 mb-4">
                            <input type="hidden" name="page" value="order-tracking">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Mã đơn hàng</label>
                                <input type="text" name="code" value="<?= htmlspecialchars($code) ?>" class="form-control" placeholder="Ví dụ: YGO-20260609-1234" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Số điện thoại</label>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($phone) ?>" class="form-control" placeholder="Nhập số điện thoại" pattern="[0-9]{10,11}" required>
                            </div>
                            <div class="col-12 d-flex justify-content-between align-items-center">
                                <a href="index.php?page=order-history" class="btn btn-outline-primary">
                                    <i class="bi bi-clock-history"></i> Lịch sử đơn hàng
                                </a>
                                <button type="submit" class="btn btn-primary-yumgo px-4 py-2">Tra cứu</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if (!$code && !$order): ?>
                        <div class="alert alert-info border-0 text-center">
                            Vui lòng nhập mã đơn hàng và số điện thoại để tra cứu trạng thái.
                        </div>
                    <?php elseif ($code && !$order): ?>
                        <div class="alert alert-danger border-0 text-center">
                            Không tìm thấy đơn hàng với mã: <strong><?= htmlspecialchars($code) ?></strong>
                        </div>
                    <?php elseif ($order): ?>
                        <!-- Giao diện Tracking Timeline -->
                        <?php
                            $statusList = ['Placed', 'Preparing', 'Ready', 'Delivering', 'Delivered'];
                            $currentStatus = $order['status'];
                            
                            $isCancelled = strpos($currentStatus, 'Cancelled') !== false;
                            $currentIndex = array_search($currentStatus, $statusList);
                        ?>

                        <div class="tracking-timeline py-4 position-relative text-center">
                            <?php if ($isCancelled): ?>
                                <h4 class="text-danger mb-3"><i class="bi bi-x-circle-fill"></i> ĐƠN HÀNG ĐÃ BỊ HỦY</h4>
                                <p class="text-muted">Cập nhật: <?= $currentStatus ?></p>
                            <?php else: ?>
                                <h4 class="text-success mb-4"><i class="bi bi-check-circle-fill"></i> TRẠNG THÁI: <?= strtoupper($currentStatus) ?></h4>
                                <div class="d-flex justify-content-between position-relative fs-5 text-muted">
                                    <div class="progress" style="height: 4px; position: absolute; top: 15px; left: 10%; right: 10%; z-index: 1;">
                                        <?php 
                                            $percent = ($currentIndex > 0) ? ($currentIndex / (count($statusList) - 1)) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percent ?>%"></div>
                                    </div>
                                    
                                    <?php foreach ($statusList as $index => $statusName): ?>
                                        <div class="position-relative" style="z-index: 2; flex: 1;">
                                            <?php if ($currentIndex >= $index): ?>
                                                <i class="bi bi-check-circle-fill text-success fs-3"></i>
                                            <?php else: ?>
                                                <i class="bi bi-circle-fill text-light border rounded-circle fs-3 text-white"></i>
                                            <?php endif; ?>
                                            <div class="mt-2 <?= ($currentIndex >= $index) ? 'fw-bold text-dark' : 'text-muted' ?>" style="font-size: 0.85rem;">
                                                <?= $statusName ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Chi tiết đơn hàng -->
                        <div class="mt-4 pt-3 border-top">
                            <h5 class="mb-3">Chi tiết đơn hàng</h5>
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <p class="mb-1"><span class="text-muted">Khách hàng:</span> <?= htmlspecialchars($order['customer_name']) ?></p>
                                    <p class="mb-1"><span class="text-muted">SĐT:</span> <?= htmlspecialchars($order['phone']) ?></p>
                                    <p class="mb-1"><span class="text-muted">Địa chỉ:</span> <?= nl2br(htmlspecialchars($order['address'])) ?></p>
                                    <p class="mb-1"><span class="text-muted">PTTT:</span> <?= htmlspecialchars($order['payment_method']) ?></p>
                                </div>
                                <div class="col-sm-6 text-sm-end">
                                    <p class="mb-1"><span class="text-muted">Ngày đặt:</span> <br> <?= date('d/m/Y H:i:s', strtotime($order['created_at'])) ?></p>
                                </div>
                            </div>
                            
                            <table class="table table-sm border-bottom">
                                <thead class="table-light">
                                    <tr>
                                        <th>Món ăn</th>
                                        <th class="text-center">Số lượng</th>
                                        <th class="text-end">Tạm tính</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['food_name']) ?></td>
                                        <td class="text-center"><?= $item['quantity'] ?></td>
                                        <td class="text-end"><?= number_format($item['subtotal'], 0, ',', '.') ?> đ</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="text-end">
                                <p class="mb-1">Tạm tính: <?= number_format($order['subtotal'], 0, ',', '.') ?> đ</p>
                                <p class="mb-1">Phí vận chuyển: <?= number_format($order['shipping_fee'], 0, ',', '.') ?> đ</p>
                                <?php if ($order['discount_amount'] > 0): ?>
                                    <p class="mb-1 text-success">Mã giảm giá (<?= htmlspecialchars($order['voucher_code']) ?>): -<?= number_format($order['discount_amount'], 0, ',', '.') ?> đ</p>
                                <?php endif; ?>
                                <h5 class="mt-2 text-danger">Tổng thanh toán: <?= number_format($order['total'], 0, ',', '.') ?> đ</h5>
                            </div>
                        </div>

                        <!-- Khu vực Thao tác Hủy / Sửa Đơn ($canCancel, $canEdit) -->
                        <?php
                            $canCancel = in_array($currentStatus, ['Placed', 'Preparing']);
                            $canEdit = ($currentStatus === 'Placed' && $order['edit_count'] < 2 && strtotime($order['editable_until']) > time());
                        ?>
                        <?php if ($canCancel || $canEdit): ?>
                            <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                                <?php if ($canEdit): ?>
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editOrderModal">
                                        <i class="bi bi-pencil-square"></i> Cập nhật thông tin
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($canCancel): ?>
                                    <form action="index.php?page=cancel-order" method="POST" class="cancel-order-form">
                                        <input type="hidden" name="code" value="<?= htmlspecialchars($code) ?>">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-trash"></i> Hủy đơn
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Nút xuất hóa đơn luôn có -->
                                <a href="index.php?page=order-invoice&code=<?= htmlspecialchars($code) ?>" target="_blank" class="btn btn-secondary">
                                    <i class="bi bi-printer"></i> Hóa đơn PDF
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mt-4 pt-3 border-top text-end">
                                <a href="index.php?page=order-invoice&code=<?= htmlspecialchars($code) ?>" target="_blank" class="btn btn-secondary">
                                    <i class="bi bi-printer"></i> In Hóa đơn PDF
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-center">
                <a href="index.php?page=home" class="btn btn-outline-primary me-2">Tiếp tục mua hàng</a>
                <a href="index.php?page=order-history" class="btn btn-primary-yumgo">Lịch sử đơn hàng</a>
            </div>
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
                            <option value="Transfer" <?= $order['payment_method'] === 'Transfer' ? 'selected' : '' ?>>Chuyển khoản</option>
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
        var copyBtn = document.getElementById('copyOrderCodeBtn');
        
        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                var codeElement = document.getElementById('orderCodeValue');
                if (!codeElement) {
                    return;
                }
                var orderCode = codeElement.textContent.trim();
                navigator.clipboard.writeText(orderCode).then(function () {
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã sao chép mã đơn',
                        text: 'Bạn đã lưu mã đơn hàng vào clipboard.',
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
            });
        }

        var cancelOrderForm = document.querySelector('.cancel-order-form');
        if (cancelOrderForm) {
            cancelOrderForm.addEventListener('submit', function (event) {
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
                        cancelOrderForm.submit();
                    }
                });
            });
        }
    });
</script>

<?php require_once 'views/layouts/footer.php'; ?>