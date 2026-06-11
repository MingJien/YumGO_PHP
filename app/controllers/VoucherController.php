<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../models/Voucher.php';

final class VoucherController
{
    public static function applyJson(PDO $pdo, string $code, float $subtotal): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($code === '' || $subtotal <= 0) {
            echo json_encode([
                'valid' => false,
                'discount' => 0,
                'total' => $subtotal,
                'message' => 'Thiếu mã voucher hoặc tạm tính đơn hàng không hợp lệ.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(Voucher::preview($pdo, $code, $subtotal), JSON_UNESCAPED_UNICODE);
    }
}
