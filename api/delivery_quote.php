<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../app/services/DeliveryService.php';

function quoteMoney(float|string|null $value): string
{
    return number_format((float)$value, 0, ',', '.') . 'đ';
}

try {
    $address = trim((string)($_POST['address'] ?? $_GET['address'] ?? ''));
    $deliveryType = (string)($_POST['delivery_type'] ?? $_GET['delivery_type'] ?? DeliveryService::TYPE_DELIVERY);
    $deliveryType = $deliveryType === DeliveryService::TYPE_PICKUP ? DeliveryService::TYPE_PICKUP : DeliveryService::TYPE_DELIVERY;

    $wantsJson = ($_SERVER['REQUEST_METHOD'] === 'POST')
        || (($_GET['format'] ?? '') === 'json')
        || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

    if (!$wantsJson) {
        $quote = $address !== '' ? DeliveryService::quote($address, $deliveryType) : null;
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!doctype html>
        <html lang="vi">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Kiểm tra phí giao hàng YumGO</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
            <link href="<?= e(BASE_URL . '/assets/css/staff.css') ?>" rel="stylesheet">
        </head>
        <body class="staff-shell">
            <main class="container py-5">
                <div class="card mx-auto" style="max-width:760px">
                    <div class="card-body">
                        <div class="brand-link mb-3"><span class="brand-mark">Y</span><span>YumGO</span></div>
                        <h1 class="h4 page-title">Kiểm tra phí giao hàng</h1>
                        <p class="text-muted">Điểm lấy hàng cố định: <strong><?= e(RESTAURANT_ADDRESS) ?></strong></p>
                        <form method="get" class="d-grid gap-3">
                            <div>
                                <label class="form-label">Địa chỉ khách hàng</label>
                                <input class="form-control" name="address" value="<?= e($address) ?>" placeholder="Ví dụ: Chợ Cao Lãnh, Đồng Tháp" required>
                            </div>
                            <div>
                                <label class="form-label">Hình thức nhận hàng</label>
                                <select class="form-select" name="delivery_type">
                                    <option value="delivery" <?= $deliveryType === DeliveryService::TYPE_DELIVERY ? 'selected' : '' ?>>Giao hàng</option>
                                    <option value="pickup" <?= $deliveryType === DeliveryService::TYPE_PICKUP ? 'selected' : '' ?>>Khách tự lấy</option>
                                </select>
                            </div>
                            <div class="checkship-actions">
                                <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-geo-alt"></i> Kiểm tra</button>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e(BASE_URL . '/api/delivery_quote.php') ?>">Nhập lại</a>
                                <a class="btn btn-sm btn-outline-primary" href="<?= e(BASE_URL . '/') ?>">Trang chủ</a>
                            </div>
                        </form>

                        <?php if ($quote !== null): ?>
                            <?php $isTooFar = $quote['delivery_status'] === DeliveryService::STATUS_TOO_FAR; ?>
                            <hr class="my-4">
                            <div class="delivery-result <?= $isTooFar ? 'delivery-result-danger' : 'delivery-result-ok' ?>">
                                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                                    <div>
                                        <div class="text-muted small">Kết quả</div>
                                        <h2 class="h5 mb-1"><?= e(DeliveryService::statusLabel($quote['delivery_status'])) ?></h2>
                                        <p class="mb-0"><?= e($quote['message']) ?></p>
                                    </div>
                                    <span class="badge <?= $isTooFar ? 'badge-danger' : 'badge-success' ?>"><?= e($quote['provider'] ?? 'YumGO') ?></span>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-4"><div class="delivery-result-box"><span>Khoảng cách</span><strong><?= $quote['distance_km'] !== null ? number_format((float)$quote['distance_km'], 2, ',', '.') . 'km' : '-' ?></strong></div></div>
                                    <div class="col-md-4"><div class="delivery-result-box"><span>Thời gian</span><strong><?= e($quote['duration_text'] ?? '-') ?></strong></div></div>
                                    <div class="col-md-4"><div class="delivery-result-box"><span>Phí ship</span><strong><?= $quote['shipping_fee'] !== null ? quoteMoney($quote['shipping_fee']) : '-' ?></strong></div></div>
                                </div>
                                <?php if ($isTooFar): ?>
                                    <div class="checkship-actions mt-3">
                                        <a class="btn btn-sm btn-warning" href="<?= e(BASE_URL . '/api/delivery_quote.php?' . http_build_query(['address' => $address, 'delivery_type' => DeliveryService::TYPE_PICKUP])) ?>"><i class="bi bi-bag-check"></i> Khách tự lấy</a>
                                        <button class="btn btn-sm btn-outline-danger" type="button" disabled>Không thể giao</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </body>
        </html>
        <?php
        exit;
    }

    $quote = DeliveryService::quote($address, $deliveryType);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => in_array($quote['delivery_status'], [DeliveryService::STATUS_OK, DeliveryService::STATUS_PICKUP], true),
        'restaurant_address' => RESTAURANT_ADDRESS,
        'delivery_type' => $quote['delivery_type'],
        'delivery_status' => $quote['delivery_status'],
        'delivery_status_label' => DeliveryService::statusLabel($quote['delivery_status']),
        'distance_km' => $quote['distance_km'],
        'duration_text' => $quote['duration_text'],
        'shipping_fee' => $quote['shipping_fee'],
        'provider' => $quote['provider'] ?? null,
        'message' => $quote['message'],
        'fee_rules' => [
            ['max_km' => 1, 'fee' => 0],
            ['max_km' => 3, 'fee' => 10000],
            ['max_km' => 5, 'fee' => 20000],
            ['max_km' => 7, 'fee' => 30000],
            ['max_km' => 10, 'fee' => 40000],
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
