<?php
/**
 * YumGO - Empty state dùng chung.
 */

if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$emptyIcon = $emptyIcon ?? 'bi-exclamation-circle';
$emptyTitle = $emptyTitle ?? 'Không tìm thấy dữ liệu';
$emptyDesc = $emptyDesc ?? 'Thông tin bạn yêu cầu hiện không có trên hệ thống hoặc đã bị ẩn.';
$emptyBtnText = $emptyBtnText ?? 'Quay lại trang chủ';
$emptyBtnUrl = $emptyBtnUrl ?? 'index.php?page=home';
?>

<div class="container py-5 text-center d-flex flex-column align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="mb-4 text-muted" style="opacity: 0.8; font-size: 80px;">
        <i class="bi <?php echo htmlspecialchars($emptyIcon); ?>" style="color: var(--yumgo-muted);"></i>
    </div>

    <h3 class="fw-bold mb-2 display-md text-dark">
        <?php echo htmlspecialchars($emptyTitle); ?>
    </h3>

    <p class="text-secondary mb-4 body-md mx-auto" style="max-width: 480px;">
        <?php echo htmlspecialchars($emptyDesc); ?>
    </p>

    <div>
        <a href="<?php echo htmlspecialchars($emptyBtnUrl); ?>" class="btn btn-primary-yumgo px-4 py-2 fs-6">
            <?php echo htmlspecialchars($emptyBtnText); ?>
        </a>
    </div>
</div>
