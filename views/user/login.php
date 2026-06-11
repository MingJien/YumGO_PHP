<section class="auth-page auth-page-login">
    <div class="auth-shell">
        <div class="auth-form-side">
            <div class="auth-card">
                <div class="auth-form-heading">
                    <h1>Chào mừng trở lại</h1>
                    <p>
                        Chưa có tài khoản?
                        <a href="index.php?page=register<?php echo ($redirectPage ?? 'home') !== 'home' ? '&redirect=' . urlencode($redirectPage) : ''; ?>">Đăng ký</a>
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

                <form action="index.php?page=login<?php echo ($redirectPage ?? 'home') !== 'home' ? '&redirect=' . urlencode($redirectPage) : ''; ?>" method="POST" class="auth-form" novalidate>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectPage ?? 'home'); ?>">
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
                        <label for="password">Mật khẩu</label>
                        <div class="auth-input">
                            <i class="bi bi-lock"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                minlength="6"
                                placeholder="Nhập mật khẩu"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="auth-password-toggle" data-password-toggle="password" aria-label="Hiện mật khẩu">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="auth-row">
                        <label class="auth-check">
                            <input type="checkbox" name="remember" value="1">
                            <span>Ghi nhớ tôi</span>
                        </label>
                        <span class="auth-muted-link">Quên mật khẩu?</span>
                    </div>

                    <button type="submit" class="auth-submit">Đăng nhập</button>
                </form>

                <div class="auth-divider"><span>Hoặc</span></div>

                <div class="auth-socials" aria-label="Tùy chọn đăng nhập demo">
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
                    Khi đăng nhập, bạn đồng ý với điều khoản sử dụng demo của hệ thống.
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
