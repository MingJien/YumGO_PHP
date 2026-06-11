<?php
declare(strict_types=1);

final class Order
{
    public static function find(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch();

        return $order ?: null;
    }

    public static function items(PDO $pdo, int $orderId): array
    {
        $stmt = $pdo->prepare('SELECT oi.*, f.name FROM order_items oi JOIN foods f ON f.id = oi.food_id WHERE oi.order_id = :order_id');
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll();
    }

    public static function readyOrders(PDO $pdo): array
    {
        return $pdo->query("SELECT * FROM orders WHERE status = 'Ready' ORDER BY created_at ASC")->fetchAll();
    }

    public static function byShipper(PDO $pdo, int $shipperId): array
    {
        $stmt = $pdo->prepare(
            "SELECT * FROM orders
             WHERE shipper_id = :shipper_id AND status IN ('Delivering', 'Delivered', 'Cancelled By Shipper')
             ORDER BY updated_at DESC LIMIT 20"
        );
        $stmt->execute(['shipper_id' => $shipperId]);

        return $stmt->fetchAll();
    }

    public static function search(PDO $pdo, string $keyword): array
    {
        $like = '%' . str_replace('%', '\\%', $keyword) . '%';
        $stmt = $pdo->prepare(
            "SELECT o.*, s.name AS shipper_name, s.phone AS shipper_phone
             FROM orders o
             LEFT JOIN shippers s ON s.id = o.shipper_id
             WHERE o.order_code LIKE :order_code
                OR o.customer_name LIKE :customer_name
                OR o.phone LIKE :phone
             ORDER BY o.created_at DESC
             LIMIT 30"
        );
        $stmt->execute([
            'order_code' => $like,
            'customer_name' => $like,
            'phone' => $like,
        ]);

        return $stmt->fetchAll();
    }

    public static function allWithShipper(PDO $pdo): array
    {
        return $pdo->query(
            'SELECT o.*, s.name AS shipper_name, s.phone AS shipper_phone
             FROM orders o LEFT JOIN shippers s ON s.id = o.shipper_id
             ORDER BY o.created_at DESC'
        )->fetchAll();
    }
}
