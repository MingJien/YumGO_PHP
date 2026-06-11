<?php
declare(strict_types=1);

final class Voucher
{
    public static function calculateDiscount(array $voucher, float $subtotal): array
    {
        if ((int)$voucher['is_active'] !== 1) {
            return ['valid' => false, 'discount' => 0.0, 'message' => 'Voucher đang bị tắt.'];
        }

        if (strtotime((string)$voucher['expired_at']) < time()) {
            return ['valid' => false, 'discount' => 0.0, 'message' => 'Voucher đã hết hạn.'];
        }

        if ($subtotal < (float)$voucher['min_order']) {
            return [
                'valid' => false,
                'discount' => 0.0,
                'message' => 'Đơn hàng chưa đạt tối thiểu ' . number_format((float)$voucher['min_order'], 0, ',', '.') . 'đ.',
            ];
        }

        if ($voucher['type'] === 'percent') {
            $percent = max(10, min(100, (float)$voucher['value']));
            $discount = $subtotal * $percent / 100;
        } else {
            $discount = (float)$voucher['value'];
        }

        $discount = min($discount, $subtotal);

        return [
            'valid' => true,
            'discount' => $discount,
            'message' => 'Voucher hợp lệ.',
        ];
    }

    public static function findActiveByCode(PDO $pdo, string $code): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM vouchers WHERE code = :code LIMIT 1');
        $stmt->execute(['code' => strtoupper(trim($code))]);
        $voucher = $stmt->fetch();

        return $voucher ?: null;
    }

    public static function preview(PDO $pdo, string $code, float $subtotal): array
    {
        $voucher = self::findActiveByCode($pdo, $code);

        if (!$voucher) {
            return ['valid' => false, 'discount' => 0.0, 'total' => $subtotal, 'message' => 'Không tìm thấy voucher.'];
        }

        $result = self::calculateDiscount($voucher, $subtotal);
        $result['total'] = max(0, $subtotal - (float)$result['discount']);
        $result['voucher'] = $voucher;

        return $result;
    }
}
