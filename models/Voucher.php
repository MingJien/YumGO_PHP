<?php
class Voucher {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function validateCode($code, $subtotal) {
        $stmt = $this->pdo->prepare("SELECT * FROM vouchers WHERE code = ? AND is_active = 1 AND expired_at >= NOW() LIMIT 1");
        $stmt->execute([$code]);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$voucher) {
            return ['status' => false, 'message' => 'Mã giảm giá không tồn tại hoặc đã hết hạn!'];
        }

        if ($subtotal < $voucher['min_order']) {
            return ['status' => false, 'message' => 'Đơn hàng chưa đạt giá trị tối thiểu (' . number_format($voucher['min_order'], 0, ',', '.') . 'đ) để áp dụng mã này!'];
        }

        $discountValue = 0;
        if ($voucher['type'] === 'percent') {
            $discountValue = $subtotal * ($voucher['value'] / 100);
            // Có thể giới hạn max discount nếu có trường dữ liệu, ở đây ta ko có nên discount theo phần trăm thẳng
        } else {
            // type fixed
            $discountValue = $voucher['value'];
        }

        // Đảm bảo không giảm quá tổng tiền hiện tại
        if ($discountValue > $subtotal) {
            $discountValue = $subtotal;
        }

        return ['status' => true, 'discount_amount' => $discountValue, 'voucher' => $voucher];
    }
}
