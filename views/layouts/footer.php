<?php
/**
 * YumGO - Footer Layout dùng chung
 */

// Đảm bảo Session đã được khởi chạy để đọc Giỏ hàng
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$footerCartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $footerCartCount += $item['quantity'];
    }
}

$currentPage = isset($_GET['page']) ? trim($_GET['page']) : 'home';
?>
    </main>

    <!-- Desktop Footer (≥ 992px) -->
    <footer class="bg-canvas border-top py-5 d-none d-lg-block mt-auto">
        <div class="container">
            <div class="row gy-4">
                <!-- Brand Info -->
                <div class="col-lg-4">
                    <span class="fs-4 fw-bold" style="color: var(--yumgo-primary);">
                        Yum<span style="color: var(--yumgo-ink);">GO</span>
                    </span>
                    <p class="text-secondary mt-3 body-md" style="max-width: 300px;">
                        Hệ thống đặt món ăn trực tuyến nhanh chóng, mang hương vị nóng hổi đến tận cửa nhà bạn.
                    </p>
                </div>
                <!-- Navigation links -->
                <div class="col-lg-4">
                    <h6 class="fw-bold mb-3 text-uppercase">Khám Phá</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 body-md">
                        <li><a href="index.php?page=home" class="text-secondary text-decoration-none hover-primary">Trang Chủ</a></li>
                        <li><a href="index.php?page=foods" class="text-secondary text-decoration-none hover-primary">Thực Đơn</a></li>
                        <li><a href="index.php?page=cart" class="text-secondary text-decoration-none hover-primary">Giỏ Hàng (<?php echo $footerCartCount; ?>)</a></li>
                    </ul>
                </div>
                <!-- Support Contact -->
                <div class="col-lg-4">
                    <h6 class="fw-bold mb-3 text-uppercase">Liên Hệ</h6>
                    <ul class="list-unstyled d-flex flex-column gap-2 text-secondary body-md">
                        <li><i class="bi bi-geo-alt me-2"></i> Trường Đại học CNTT</li>
                        <li><i class="bi bi-telephone me-2"></i> 1900 1234 (Tổng đài hỗ trợ)</li>
                        <li><i class="bi bi-envelope me-2"></i> support@yumgo.vn</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4" style="border-color: var(--yumgo-hairline);">
            <div class="d-flex justify-content-between align-items-center text-secondary body-md">
                <span>&copy; <?php echo date('Y'); ?> YumGO. Tất cả các quyền được bảo lưu.</span>
                <span class="fs-7 text-muted">Phát triển bởi Thành viên 2 (User Flow & Web)</span>
            </div>
        </div>
    </footer>



    <!-- Bootstrap 5 JS Bundle with Popper (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- YumGO Cart AJAX & Interactions -->
    <script src="assets/js/cart.js"></script>
</body>
</html>
