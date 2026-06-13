<?php
/**
 * YumGO - Trang ho tro khach hang tinh.
 */

if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

$faqs = [
    [
        'icon' => 'bi-bag-check',
        'title' => 'Đặt món như thế nào?',
        'content' => 'Chọn món trong thực đơn, thêm vào giỏ, kiểm tra số lượng rồi chuyển sang trang checkout do Thành viên 3 tích hợp.'
    ],
    [
        'icon' => 'bi-credit-card',
        'title' => 'YumGO hỗ trợ thanh toán gì?',
        'content' => 'Flow demo hỗ trợ COD và Banking. Tổng tiền, voucher và phí giao hàng sẽ được backend tính lại ở bước checkout.'
    ],
    [
        'icon' => 'bi-pencil-square',
        'title' => 'Có thể sửa đơn không?',
        'content' => 'Theo rule hệ thống, khách chỉ sửa thông tin nhận hàng khi đơn còn Placed, dưới 2 lần sửa và trong 5 phút đầu.'
    ],
    [
        'icon' => 'bi-x-octagon',
        'title' => 'Khi nào được hủy đơn?',
        'content' => 'Khách có thể hủy khi đơn ở trạng thái Placed hoặc Preparing. Đơn đã Delivered hoặc Cancelled là trạng thái cuối.'
    ]
];
?>

<div class="support-page">
    <section class="support-hero">
        <div class="support-hero-copy">
            <span class="home-kicker">Trợ giúp YumGO</span>
            <h1>Hỗ trợ khách hàng</h1>
            <p>Các câu hỏi thường gặp trước khi đặt món, thanh toán và theo dõi đơn hàng demo.</p>
            <div class="support-actions">
                <a href="index.php?page=foods" class="btn btn-primary-yumgo">Đặt món ngay</a>
                <a href="index.php?page=cart" class="btn support-secondary-btn">Xem giỏ hàng</a>
            </div>
        </div>
        <div class="support-contact-card">
            <i class="bi bi-headset"></i>
            <strong>1900 1234</strong>
            <span>support@yumgo.vn</span>
            <small>Demo support, không gửi email hoặc tạo ticket trong database.</small>
        </div>
    </section>

    <section class="support-faq-grid">
        <?php foreach ($faqs as $faq): ?>
            <article class="support-faq-card">
                <div class="support-faq-icon"><i class="bi <?php echo htmlspecialchars($faq['icon']); ?>"></i></div>
                <h2><?php echo htmlspecialchars($faq['title']); ?></h2>
                <p><?php echo htmlspecialchars($faq['content']); ?></p>
            </article>
        <?php endforeach; ?>
    </section>
</div>
