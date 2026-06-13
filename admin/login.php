<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if (isRole('admin')) {
    redirect(ADMIN_DASHBOARD_URL);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } elseif ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } elseif (loginAdmin($username, $password)) {
        redirect(ADMIN_DASHBOARD_URL);
    } else {
        $error = 'Thông tin đăng nhập không đúng.';
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập quản trị YumGo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(BASE_URL . '/assets/css/staff.css') ?>" rel="stylesheet">
</head>
<body class="auth-shell">
<main class="auth-layout">
    <section class="auth-panel">
        <div class="auth-card">
            <div class="auth-logo"><span class="brand-mark">Y</span><span>YumGo</span></div>
            <h1>Đăng nhập</h1>
            <p class="auth-subtitle mb-4">Trang đăng nhập quản trị YumGo</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post">
                            <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Tên đăng nhập</label>
                    <input class="form-control" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <div class="password-field">
                        <input class="form-control" id="password" name="password" type="password" placeholder="Nhập mật khẩu" required>
                        <button class="password-toggle" type="button" data-toggle-password="password" aria-label="Xem mật khẩu"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <button class="btn btn-primary w-100 py-3" type="submit">Đăng nhập</button>
            </form>

            <p class="text-muted small mt-4 mb-0">Tài khoản demo: admin / password</p>
        </div>
    </section>
    <section class="auth-mosaic" aria-hidden="true">
        <?php for ($i = 0; $i < 12; $i++): ?>
            <div class="auth-tile"></div>
        <?php endfor; ?>
    </section>
</main>
<script src="<?= e(BASE_URL . '/assets/js/staff.js') ?>"></script>
</body>
</html>
