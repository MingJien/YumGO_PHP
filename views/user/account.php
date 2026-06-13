<section class="account-page">
    <div class="container py-5">
        <div class="account-hero">
            <div>
                <span class="account-kicker">Tài khoản khách hàng</span>
                <h1>Tài khoản của tôi</h1>
                <p>Quản lý thông tin cơ bản để đặt món nhanh hơn trong các lần sau.</p>
            </div>
            <a href="index.php?page=foods" class="btn btn-primary-yumgo">Tiếp tục chọn món</a>
        </div>

        <div class="account-grid">
            <section class="account-card">
                <div class="account-card-heading">
                    <i class="bi bi-person-lines-fill"></i>
                    <div>
                        <h2>Thông tin hồ sơ</h2>
                        <p>Số điện thoại dùng để đăng nhập tài khoản.</p>
                    </div>
                </div>

                <?php if (!empty($profileSuccess)): ?>
                    <div class="alert alert-success rounded-md" role="alert">
                        <?php echo htmlspecialchars($profileSuccess); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($profileErrors)): ?>
                    <div class="alert alert-danger rounded-md" role="alert">
                        <?php foreach ($profileErrors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="index.php?page=account-update" method="POST" class="account-form" novalidate>
                    <?= csrfField() ?>
                    <div>
                        <label for="account_name">Họ tên</label>
                        <input
                            type="text"
                            id="account_name"
                            name="name"
                            value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"
                            maxlength="100"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div>
                        <label for="account_phone">Số điện thoại</label>
                        <input
                            type="tel"
                            id="account_phone"
                            name="phone"
                            value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                            maxlength="10"
                            autocomplete="tel"
                            required
                        >
                    </div>

                    <div>
                        <label for="account_address">Địa chỉ</label>
                        <textarea
                            id="account_address"
                            name="address"
                            maxlength="255"
                            rows="4"
                            autocomplete="street-address"
                        ><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary-yumgo">Lưu thông tin</button>
                </form>
            </section>

            <section class="account-card">
                <div class="account-card-heading">
                    <i class="bi bi-shield-lock"></i>
                    <div>
                        <h2>Đổi mật khẩu</h2>
                        <p>Dùng mật khẩu mạnh và không chia sẻ cho người khác.</p>
                    </div>
                </div>

                <?php if (!empty($passwordSuccess)): ?>
                    <div class="alert alert-success rounded-md" role="alert">
                        <?php echo htmlspecialchars($passwordSuccess); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($passwordErrors)): ?>
                    <div class="alert alert-danger rounded-md" role="alert">
                        <?php foreach ($passwordErrors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="index.php?page=account-password" method="POST" class="account-form" novalidate>
                    <?= csrfField() ?>
                    <div>
                        <label for="current_password">Mật khẩu hiện tại</label>
                        <div class="account-password-field">
                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="auth-password-toggle" data-password-toggle="current_password" aria-label="Hiện mật khẩu">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="new_password">Mật khẩu mới</label>
                        <div class="account-password-field">
                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                minlength="6"
                                autocomplete="new-password"
                                required
                            >
                            <button type="button" class="auth-password-toggle" data-password-toggle="new_password" aria-label="Hiện mật khẩu">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password">Nhập lại mật khẩu mới</label>
                        <div class="account-password-field">
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                minlength="6"
                                autocomplete="new-password"
                                required
                            >
                            <button type="button" class="auth-password-toggle" data-password-toggle="confirm_password" aria-label="Hiện mật khẩu">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-yumgo">Đổi mật khẩu</button>
                </form>
            </section>
        </div>
    </div>
</section>
<script src="assets/js/auth.js?v=20260608-auth1"></script>
