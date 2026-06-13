<section class="auth-page auth-page-register">
    <div class="auth-shell">
        <div class="auth-form-side">
            <div class="auth-card">
                <div class="auth-form-heading">
                    <h1>Chào buổi ngon</h1>
                    <p>
                        Đã có tài khoản?
                        <a href="index.php?page=login<?php echo ($redirectPage ?? 'home') !== 'home' ? '&redirect=' . urlencode($redirectPage) : ''; ?>">Đăng nhập</a>
                    </p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="auth-alert" role="alert">
                        <i class="bi bi-exclamation-circle"></i>
                        <div>
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="index.php?page=register<?php echo ($redirectPage ?? 'home') !== 'home' ? '&redirect=' . urlencode($redirectPage) : ''; ?>" method="POST" class="auth-form" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectPage ?? 'home'); ?>">
                    <div class="auth-field">
                        <label for="name">Họ tên</label>
                        <div class="auth-input">
                            <i class="bi bi-person"></i>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>"
                                maxlength="100"
                                placeholder="Tên của bạn"
                                autocomplete="name"
                                required
                            >
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="phone">Số điện thoại</label>
                        <div class="auth-input">
                            <i class="bi bi-phone"></i>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?php echo htmlspecialchars($old['phone'] ?? ''); ?>"
                                maxlength="10"
                                placeholder="0901234567"
                                autocomplete="tel"
                                required
                            >
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="address">Địa chỉ</label>
                        <div class="auth-input">
                            <i class="bi bi-geo-alt"></i>
                            <input
                                type="text"
                                id="address"
                                name="address"
                                value="<?php echo htmlspecialchars($old['address'] ?? ''); ?>"
                                maxlength="255"
                                placeholder="Có thể để trống"
                                autocomplete="street-address"
                            >
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="password">Mật khẩu</label>
                        <div class="auth-input">
                            <i class="bi bi-lock"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                minlength="6"
                                placeholder="Tối thiểu 6 ký tự"
                                autocomplete="new-password"
                                required
                            >
                            <button type="button" class="auth-password-toggle" data-password-toggle="password" aria-label="Hiện mật khẩu">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="confirm_password">Nhập lại mật khẩu</label>
                        <div class="auth-input">
                            <i class="bi bi-check2-circle"></i>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                minlength="6"
                                placeholder="Nhập lại mật khẩu"
                                autocomplete="new-password"
                                required
                            >
                            <button type="button" class="auth-password-toggle" data-password-toggle="confirm_password" aria-label="Hiện mật khẩu">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="auth-submit">Tạo tài khoản</button>
                </form>

                <div class="auth-divider"><span>Hoặc</span></div>

                <div class="auth-socials" aria-label="Tùy chọn đăng ký demo">
                    <button type="button" disabled>
                        <i class="bi bi-google"></i>
                        Tiếp tục với Google
                    </button>
                    <button type="button" disabled>
                        <i class="bi bi-apple"></i>
                        Tiếp tục với Apple
                    </button>
                </div>

                <p class="auth-legal">
                    Tài khoản khách hàng dùng số điện thoại để đăng nhập.
                </p>
            </div>
        </div>

        <aside class="auth-visual" aria-label="Bộ sưu tập món ăn YumGO">
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <div class="auth-food-tile auth-food-tile-<?php echo $i; ?>">
                    <img src="uploads/banners/hero_combo.png" alt="Món ăn YumGO">
                </div>
            <?php endfor; ?>
        </aside>
    </div>
</section>
<script src="assets/js/auth.js?v=20260608-auth1"></script>
