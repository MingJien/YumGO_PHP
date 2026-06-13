<?php
$title = "Tra cứu đơn hàng - YumGO";
require_once 'views/layouts/header.php';
?>

<div class="container py-5" style="min-height: 70vh;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="mb-3">Tra cứu trạng thái đơn hàng</h3>
                    <p class="text-secondary mb-4">Nhập mã đơn hàng và số điện thoại để xem chi tiết trạng thái đơn hàng hiện tại.</p>

                    <form action="index.php" method="GET" class="row g-3">
                        <input type="hidden" name="page" value="order-tracking">

                        <div class="col-md-6">
                            <label for="orderCode" class="form-label">Mã đơn hàng</label>
                            <input type="text" id="orderCode" name="code" class="form-control" placeholder="VD: YUMGO-20260609-1234" required>
                        </div>

                        <div class="col-md-6">
                            <label for="orderPhone" class="form-label">Số điện thoại</label>
                            <input type="tel" id="orderPhone" name="phone" class="form-control" placeholder="Nhập số điện thoại đặt hàng" pattern="[0-9]{10,11}" required>
                        </div>

                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <a href="index.php?page=order-history" class="btn btn-outline-primary">
                                <i class="bi bi-clock-history"></i> Lịch sử đơn hàng
                            </a>
                            <button type="submit" class="btn btn-primary-yumgo px-4 py-2">Tra cứu đơn hàng</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'views/layouts/footer.php'; ?>
