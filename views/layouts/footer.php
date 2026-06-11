<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$footerCartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $footerCartCount += (int)$item['quantity'];
    }
}
?>
    </main>

    <footer class="site-footer">
        <div class="site-footer-inner">
            <div class="footer-brand-block">
                <a class="site-brand site-brand-footer" href="index.php?page=home" aria-label="YumGO trang chủ">
                    <strong><span>Yum</span>GO</strong>
                </a>
                <p>Đặt món nhanh, thanh toán gọn, giao bữa ăn nóng tới đúng nơi bạn cần.</p>
                <div class="footer-socials" aria-label="Mạng xã hội YumGO">
                    <a href="https://www.facebook.com/" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://www.tiktok.com/" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                </div>
            </div>

            <div>
                <h6>LIÊN HỆ</h6>
                <ul>
                    <li><i class="bi bi-telephone"></i> 1900 1234</li>
                    <li><i class="bi bi-envelope"></i> support@yumgo.vn</li>
                    <li><i class="bi bi-geo-alt"></i> Trường Đại học CNTT</li>
                </ul>
            </div>

            <div>
                <h6>DỊCH VỤ</h6>
                <ul>
                    <li><a href="index.php?page=foods">Thực đơn</a></li>
                    <li><a href="index.php?page=favorites">Món yêu thích</a></li>
                    <li><a href="index.php?page=cart">Giỏ hàng (<span class="footer-cart-count"><?php echo $footerCartCount; ?></span>)</a></li>
                    <li><a href="index.php?page=checkout">Thanh toán</a></li>
                    <li><a href="index.php?page=order-search">Tra cứu đơn hàng</a></li>
                    <li><a href="index.php?page=order-history">Lịch sử đơn hàng</a></li>
                    <li><a href="index.php?page=support">Hỗ trợ</a></li>
                </ul>
            </div>

            <div class="footer-image-card">
                <img src="uploads/banners/hero_combo.png" alt="Món ăn YumGO">
            </div>
        </div>
        <div class="site-footer-quote">
            “Món ngon đến nhanh, bữa ăn vừa ý.”
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/cart.js?v=20260608-cartpersist2"></script>
</body>
</html>
