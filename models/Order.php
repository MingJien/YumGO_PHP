<?php
class Order {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getByOrderCode($orderCode) {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE order_code = ? LIMIT 1");
        $stmt->execute([$orderCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getByOrderCodes($codes) {
        if (empty($codes)) {
            return [];
        }
        $inQuery = implode(',', array_fill(0, count($codes), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE order_code IN ($inQuery) ORDER BY created_at DESC");
        $stmt->execute($codes);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getItems($orderId) {
        $stmt = $this->pdo->prepare("
            SELECT oi.*, f.name as food_name, f.image 
            FROM order_items oi 
            JOIN foods f ON oi.food_id = f.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancelUserOrder($orderId) {
        $stmt = $this->pdo->prepare("UPDATE orders SET status = 'Cancelled By User' WHERE id = ? AND status IN ('Placed', 'Preparing')");
        return $stmt->execute([$orderId]);
    }

    public function updateOrderInfo($orderId, $phone, $address, $note, $paymentMethod) {
        $stmt = $this->pdo->prepare("UPDATE orders SET phone = ?, address = ?, note = ?, payment_method = ?, edit_count = edit_count + 1 WHERE id = ?");
        return $stmt->execute([$phone, $address, $note, $paymentMethod, $orderId]);
    }
}
