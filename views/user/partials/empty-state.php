<?php
/**
 * YumGO - Empty State View Partial dùng chung
 * Dùng hiển thị khi Giỏ hàng rỗng, Tìm kiếm không ra kết quả, hoặc không tìm thấy món ăn.
 */

// Chặn truy cập trực tiếp
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// Thiết lập các giá trị mặc định nếu không được truyền từ Controller/View gọi
$emptyIcon = $emptyIcon ?? 'bi-exclamation-circle';
$emptyTitle = $emptyTitle ?? 'Không tìm thấy dữ liệu!';
$emptyDesc = $emptyDesc ?? 'Thông tin bạn yêu cầu hiện không có trên hệ thống hoặc đã bị ẩn.';
$emptyBtnText = $emptyBtnText ?? 'Quay lại Trang chủ';
$emptyBtnUrl = $emptyBtnUrl ?? 'index.php?page=home';
?>
<div class="container py-5 text-center d-flex flex-column align-items-center justify-content-center" style="min-height: 60vh;">
    <!-- Large opacity-80 illustration icon -->
    <div class="mb-4 text-muted" style="opacity: 0.8; font-size: 80px;">
        <i class="bi <?php echo htmlspecialchars($emptyIcon); ?>" style="color: var(--yumgo-muted);"></i>
    </div>
    
    <!-- Title -->
    <h3 class="fw-bold mb-2 display-md text-dark">
        <?php echo htmlspecialchars($emptyTitle); ?>
    </h3>
    
    <!-- Description -->
    <p class="text-secondary mb-4 body-md mx-auto" style="max-width: 480px;">
        <?php echo htmlspecialchars($emptyDesc); ?>
    </p>
    
    <!-- CTA Action Button -->
    <div>
        <a href="<?php echo htmlspecialchars($emptyBtnUrl); ?>" class="btn btn-primary-yumgo px-4 py-2 fs-6">
            <?php echo htmlspecialchars($emptyBtnText); ?>
        </a>
    </div>
</div>
