<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn <?= htmlspecialchars($order['order_code']) ?></title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 14px; color: #333; line-height: 1.5; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); }
        .invoice-header { display: flex; justify-content: space-between; border-bottom: 2px solid #ff4757; padding-bottom: 20px; margin-bottom: 20px; }
        .invoice-header h1 { color: #ff4757; margin: 0; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { vertical-align: top; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        .items-table th { background: #f8f9fa; }
        .items-table td.text-right, .items-table th.text-right { text-align: right; }
        .items-table td.text-center, .items-table th.text-center { text-align: center; }
        .summary-box { float: right; width: 50%; }
        .summary-table { width: 100%; margin-top: 20px; }
        .summary-table th, .summary-table td { padding: 5px 10px; text-align: right; }
        .total-row th, .total-row td { border-top: 2px solid #ff4757; color: #ff4757; font-size: 18px; font-weight: bold; }
        .footer { clear: both; margin-top: 50px; text-align: center; color: #777; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="invoice-header">
            <table style="width:100%">
                <tr>
                    <td>
                        <h1>YumGO</h1>
                        <p>Hệ thống Đặt đồ ăn Tốc độ cao</p>
                    </td>
                    <td style="text-align: right;">
                        <h2>HÓA ĐƠN BÁN HÀNG</h2>
                        <p>Mã hóa đơn: <strong><?= htmlspecialchars($order['order_code']) ?></strong><br>
                        Ngày mua: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <table class="info-table">
            <tr>
                <td width="50%">
                    <h3 style="margin-top:0">Khách hàng:</h3>
                    <p>
                        Họ & tên: <?= htmlspecialchars($order['customer_name']) ?><br>
                        SĐT: <?= htmlspecialchars($order['phone']) ?><br>
                        Địa chỉ: <?= htmlspecialchars($order['address']) ?><br>
                        PT. Thanh toán: <?= htmlspecialchars($order['payment_method']) ?>
                    </p>
                </td>
                <td width="50%" style="text-align: right;">
                    <h3 style="margin-top:0">Trạng thái:</h3>
                    <p>
                        <?= $order['status'] ?>
                    </p>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Tên món ăn</th>
                    <th class="text-center">Số lượng</th>
                    <th class="text-right">Đơn giá</th>
                    <th class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php $stt = 1; foreach ($items as $item): ?>
                <tr>
                    <td><?= $stt++ ?></td>
                    <td><?= htmlspecialchars($item['food_name']) ?></td>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td class="text-right"><?= number_format($item['price'], 0, ',', '.') ?> đ</td>
                    <td class="text-right"><?= number_format($item['subtotal'], 0, ',', '.') ?> đ</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="summary-box">
            <table class="summary-table">
                <tr>
                    <th>Tạm tính:</th>
                    <td><?= number_format($order['subtotal'], 0, ',', '.') ?> đ</td>
                </tr>
                <tr>
                    <th>Phí vận chuyển:</th>
                    <td><?= number_format($order['shipping_fee'], 0, ',', '.') ?> đ</td>
                </tr>
                <?php if ($order['discount_amount'] > 0): ?>
                <tr>
                    <th>Giảm giá (<?= htmlspecialchars($order['voucher_code']) ?>):</th>
                    <td style="color: #198754;">-<?= number_format($order['discount_amount'], 0, ',', '.') ?> đ</td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <th>TỔNG THANH TOÁN:</th>
                    <td><?= number_format($order['total'], 0, ',', '.') ?> đ</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Cảm ơn quý khách đã sử dụng dịch vụ của YumGO!</p>
            <p>Mọi thắc mắc xin vui lòng liên hệ hotline: 1900 1234</p>
        </div>
    </div>
</body>
</html>