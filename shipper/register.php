<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../app/controllers/ShipperController.php';

redirectIfLoggedIn();

$pdo = Database::getConnection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireValidCsrf();
        ShipperController::register($pdo, $_POST, $_FILES);
        setFlash('success', 'Đăng ký tài khoản shipper thành công. Bạn có thể đăng nhập ngay.');
        redirect(SHIPPER_LOGIN_URL);
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng ký shipper YumGO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(BASE_URL . '/assets/css/staff.css') ?>" rel="stylesheet">
</head>
<body class="auth-shell">
<main class="auth-layout">
    <section class="auth-panel">
        <div class="auth-card">
            <div class="auth-logo"><span class="brand-mark">Y</span><span>YumGO Shipper</span></div>
            <h1>Đăng ký giao hàng</h1>
            <p class="auth-subtitle mb-4">Tạo tài khoản shipper để nhận đơn đã chuẩn bị.</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                            <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label" for="name">Họ tên</label>
                    <input class="form-control" id="name" name="name" placeholder="Nhập họ tên" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="phone">Số điện thoại</label>
                    <input class="form-control" id="phone" name="phone" inputmode="numeric" maxlength="10" placeholder="Nhập số điện thoại" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <div class="password-field">
                        <input class="form-control" id="password" name="password" type="password" minlength="6" maxlength="18" placeholder="6-18 ký tự, có chữ hoa và số" required>
                        <button class="password-toggle" type="button" data-toggle-password="password" aria-label="Xem mật khẩu"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="avatar">Avatar</label>
                    <input class="form-control" id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp">
                </div>
                <button class="btn btn-primary w-100 py-3" type="submit">Đăng ký</button>
            </form>

            <p class="text-muted small mt-4 mb-0">Đã có tài khoản? <a href="<?= e(SHIPPER_LOGIN_URL) ?>">Đăng nhập</a></p>
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
