<?php
declare(strict_types=1);

final class DeliveryService
{
    public const TYPE_DELIVERY = 'delivery';
    public const TYPE_PICKUP = 'pickup';

    public const STATUS_OK = 'ok';
    public const STATUS_TOO_FAR = 'too_far';
    public const STATUS_PICKUP = 'pickup';
    public const STATUS_FAILED = 'route_failed';
    public const STATUS_OSM_FAILED = 'osm_failed';

    public static function ensureSchema(PDO $pdo): void
    {
        self::ensureOrderColumn($pdo, 'delivery_type', "ENUM('delivery','pickup') NOT NULL DEFAULT 'delivery'");
        self::ensureOrderColumn($pdo, 'delivery_status', "VARCHAR(40) NOT NULL DEFAULT 'unchecked'");
        self::ensureOrderColumn($pdo, 'distance_km', 'DECIMAL(8,2) NULL');
        self::ensureOrderColumn($pdo, 'delivery_duration_text', 'VARCHAR(50) NULL');
    }

    public static function calculateFee(float $distanceKm): ?float
    {
        if ($distanceKm <= 1) {
            return 0.0;
        }
        if ($distanceKm <= 3) {
            return 10000.0;
        }
        if ($distanceKm <= 5) {
            return 20000.0;
        }
        if ($distanceKm <= 7) {
            return 30000.0;
        }
        if ($distanceKm <= DELIVERY_MAX_DISTANCE_KM) {
            return 40000.0;
        }

        return null;
    }

    public static function quote(string $destinationAddress, string $deliveryType = self::TYPE_DELIVERY): array
    {
        $deliveryType = $deliveryType === self::TYPE_PICKUP ? self::TYPE_PICKUP : self::TYPE_DELIVERY;
        if ($deliveryType === self::TYPE_PICKUP) {
            return [
                'delivery_type' => self::TYPE_PICKUP,
                'delivery_status' => self::STATUS_PICKUP,
                'distance_km' => 0.0,
                'duration_text' => null,
                'shipping_fee' => 0.0,
                'provider' => 'pickup',
                'message' => 'Khách đến quán tự lấy, không tính phí giao hàng.',
            ];
        }

        $destinationAddress = trim($destinationAddress);
        if ($destinationAddress === '') {
            return [
                'delivery_type' => self::TYPE_DELIVERY,
                'delivery_status' => self::STATUS_FAILED,
                'distance_km' => null,
                'duration_text' => null,
                'shipping_fee' => null,
                'provider' => null,
                'message' => 'Địa chỉ khách hàng không được để trống.',
            ];
        }

        $route = self::computeRoute(RESTAURANT_ADDRESS, $destinationAddress);
        if (!$route['ok']) {
            return [
                'delivery_type' => self::TYPE_DELIVERY,
                'delivery_status' => $route['provider'] === 'OpenStreetMap/OSRM' ? self::STATUS_OSM_FAILED : self::STATUS_FAILED,
                'distance_km' => null,
                'duration_text' => null,
                'shipping_fee' => null,
                'provider' => $route['provider'] ?? null,
                'message' => $route['message'],
            ];
        }

        $distanceKm = round(((float)$route['distance_meters']) / 1000, 2);
        $shippingFee = self::calculateFee($distanceKm);
        if ($shippingFee === null) {
            return [
                'delivery_type' => self::TYPE_DELIVERY,
                'delivery_status' => self::STATUS_TOO_FAR,
                'distance_km' => $distanceKm,
                'duration_text' => $route['duration_text'],
                'shipping_fee' => null,
                'provider' => $route['provider'],
                'message' => 'Khoảng cách ' . number_format($distanceKm, 2, ',', '.') . 'km vượt quá phạm vi giao hàng 10km. Khách chỉ có thể đến quán tự lấy hoặc không đặt giao hàng.',
            ];
        }

        return [
            'delivery_type' => self::TYPE_DELIVERY,
            'delivery_status' => self::STATUS_OK,
            'distance_km' => $distanceKm,
            'duration_text' => $route['duration_text'],
            'shipping_fee' => $shippingFee,
            'provider' => $route['provider'],
            'message' => 'Đã tính phí giao hàng theo khoảng cách đường đi thật từ ' . $route['provider'] . '.',
        ];
    }

    public static function refreshOrderDelivery(PDO $pdo, int $orderId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            throw new RuntimeException('Không tìm thấy đơn hàng.');
        }

        $quote = self::quote((string)$order['address'], (string)($order['delivery_type'] ?? self::TYPE_DELIVERY));
        $shippingFee = $quote['shipping_fee'] !== null ? (float)$quote['shipping_fee'] : (float)$order['shipping_fee'];
        $total = max(0, (float)$order['subtotal'] + $shippingFee - (float)$order['discount_amount']);

        $stmt = $pdo->prepare(
            'UPDATE orders
             SET delivery_type = :delivery_type,
                 delivery_status = :delivery_status,
                 distance_km = :distance_km,
                 delivery_duration_text = :delivery_duration_text,
                 shipping_fee = :shipping_fee,
                 total = :total
             WHERE id = :id'
        );
        $stmt->execute([
            'delivery_type' => $quote['delivery_type'],
            'delivery_status' => $quote['delivery_status'],
            'distance_km' => $quote['distance_km'],
            'delivery_duration_text' => $quote['duration_text'],
            'shipping_fee' => $shippingFee,
            'total' => $total,
            'id' => $orderId,
        ]);

        return $quote;
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_OK => 'Trong phạm vi giao hàng',
            self::STATUS_TOO_FAR => 'Vượt quá 10km',
            self::STATUS_PICKUP => 'Khách tự lấy',
            self::STATUS_OSM_FAILED => 'Không tính được bằng OpenStreetMap',
            self::STATUS_FAILED => 'Chưa tính được khoảng cách',
            default => 'Chưa kiểm tra',
        };
    }

    private static function computeRoute(string $originAddress, string $destinationAddress): array
    {
        if (GOOGLE_MAPS_API_KEY === '') {
            return self::computeRouteWithOpenStreetMap($originAddress, $destinationAddress);
        }

        return self::computeRouteWithGoogle($originAddress, $destinationAddress);
    }

    private static function computeRouteWithGoogle(string $originAddress, string $destinationAddress): array
    {
        $payload = json_encode([
            'origin' => ['address' => $originAddress],
            'destination' => ['address' => $destinationAddress],
            'travelMode' => 'DRIVE',
            'routingPreference' => 'TRAFFIC_AWARE',
            'languageCode' => 'vi-VN',
            'units' => 'METRIC',
        ], JSON_UNESCAPED_UNICODE);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/json',
                    'X-Goog-Api-Key: ' . GOOGLE_MAPS_API_KEY,
                    'X-Goog-FieldMask: routes.distanceMeters,routes.duration',
                ]),
                'content' => $payload,
                'timeout' => 8,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents('https://routes.googleapis.com/directions/v2:computeRoutes', false, $context);
        if ($response === false) {
            return ['ok' => false, 'provider' => 'Google Maps', 'message' => 'Không kết nối được Google Maps Routes API.'];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['routes'][0]['distanceMeters'])) {
            $message = $data['error']['message'] ?? 'Google Maps không trả về tuyến đường hợp lệ.';
            return ['ok' => false, 'provider' => 'Google Maps', 'message' => $message];
        }

        $durationSeconds = self::parseDurationSeconds((string)($data['routes'][0]['duration'] ?? '0s'));

        return [
            'ok' => true,
            'provider' => 'Google Maps',
            'distance_meters' => (float)$data['routes'][0]['distanceMeters'],
            'duration_text' => $durationSeconds > 0 ? self::formatDuration($durationSeconds) : null,
        ];
    }

    private static function computeRouteWithOpenStreetMap(string $originAddress, string $destinationAddress): array
    {
        $origin = [
            'lat' => RESTAURANT_LAT,
            'lng' => RESTAURANT_LNG,
        ];

        $destination = self::geocodeWithNominatim($destinationAddress);
        if (!$destination['ok']) {
            return ['ok' => false, 'provider' => 'OpenStreetMap/OSRM', 'message' => 'Không tìm được tọa độ địa chỉ khách trên OpenStreetMap. Hãy nhập rõ phường/xã, thành phố, tỉnh.'];
        }

        $url = sprintf(
            'https://router.project-osrm.org/route/v1/driving/%F,%F;%F,%F?overview=false&alternatives=false&steps=false',
            $origin['lng'],
            $origin['lat'],
            $destination['lng'],
            $destination['lat']
        );
        $response = self::httpGetJson($url);
        if (!$response['ok']) {
            return ['ok' => false, 'provider' => 'OpenStreetMap/OSRM', 'message' => 'Không kết nối được OSRM để tính đường đi.'];
        }

        $data = $response['data'];
        if (($data['code'] ?? '') !== 'Ok' || empty($data['routes'][0]['distance'])) {
            return ['ok' => false, 'provider' => 'OpenStreetMap/OSRM', 'message' => 'OSRM không trả về tuyến đường hợp lệ.'];
        }

        return [
            'ok' => true,
            'provider' => 'OpenStreetMap/OSRM',
            'distance_meters' => (float)$data['routes'][0]['distance'],
            'duration_text' => !empty($data['routes'][0]['duration']) ? self::formatDuration((int)round((float)$data['routes'][0]['duration'])) : null,
        ];
    }

    private static function geocodeWithNominatim(string $address): array
    {
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => $address,
            'format' => 'jsonv2',
            'limit' => 1,
            'countrycodes' => 'vn',
        ]);
        $response = self::httpGetJson($url);
        if (!$response['ok']) {
            return ['ok' => false, 'message' => 'Không kết nối được Nominatim.'];
        }

        $row = $response['data'][0] ?? null;
        if (!is_array($row) || !isset($row['lat'], $row['lon'])) {
            return ['ok' => false, 'message' => 'Không tìm thấy địa chỉ.'];
        }

        return [
            'ok' => true,
            'lat' => (float)$row['lat'],
            'lng' => (float)$row['lon'],
        ];
    }

    private static function httpGetJson(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", [
                    'Accept: application/json',
                    'User-Agent: YumGO-PHP-Student-Project/1.0 (local-dev)',
                ]),
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return ['ok' => false, 'data' => null];
        }

        $data = json_decode($raw, true);

        return ['ok' => is_array($data), 'data' => $data];
    }

    private static function ensureOrderColumn(PDO $pdo, string $column, string $definition): void
    {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'orders'
               AND COLUMN_NAME = :column"
        );
        $stmt->execute(['column' => $column]);

        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN {$column} {$definition}");
        }
    }

    private static function parseDurationSeconds(string $duration): int
    {
        return preg_match('/^(\d+)s$/', $duration, $matches) === 1 ? (int)$matches[1] : 0;
    }

    private static function formatDuration(int $seconds): string
    {
        $minutes = max(1, (int)ceil($seconds / 60));
        if ($minutes < 60) {
            return $minutes . ' phút';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return $remaining > 0 ? $hours . ' giờ ' . $remaining . ' phút' : $hours . ' giờ';
    }
}
