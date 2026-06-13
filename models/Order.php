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

    public function markPickupReceived($orderId) {
        $stmt = $this->pdo->prepare("UPDATE orders SET status = 'Delivered' WHERE id = ? AND delivery_type = 'pickup' AND status = 'Ready'");
        return $stmt->execute([$orderId]);
    }

    public function updateOrderInfo($orderId, $phone, $address, $note, $paymentMethod) {
        $stmt = $this->pdo->prepare("UPDATE orders SET phone = ?, address = ?, note = ?, payment_method = ?, edit_count = edit_count + 1 WHERE id = ?");
        return $stmt->execute([$phone, $address, $note, $paymentMethod, $orderId]);
    }

    public function getByPhone($phone) {
        if (empty($phone)) {
            return [];
        }
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE phone = ? ORDER BY created_at DESC");
        $stmt->execute([$phone]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByPhone($phone, $status = 'all') {
        if (empty($phone)) {
            return 0;
        }
        $sql = "SELECT COUNT(*) FROM orders WHERE phone = :phone";
        $params = [':phone' => $phone];

        if ($status === 'processing') {
            $sql .= " AND status IN ('Placed', 'Preparing', 'Ready')";
        } elseif ($status === 'delivering') {
            $sql .= " AND status = 'Delivering'";
        } elseif ($status === 'completed') {
            $sql .= " AND status = 'Delivered'";
        } elseif ($status === 'cancelled') {
            $sql .= " AND status LIKE 'Cancelled%'";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getByPhonePaginated($phone, $offset, $limit, $status = 'all') {
        if (empty($phone)) {
            return [];
        }
        $sql = "SELECT * FROM orders WHERE phone = :phone";
        
        if ($status === 'processing') {
            $sql .= " AND status IN ('Placed', 'Preparing', 'Ready')";
        } elseif ($status === 'delivering') {
            $sql .= " AND status = 'Delivering'";
        } elseif ($status === 'completed') {
            $sql .= " AND status = 'Delivered'";
        } elseif ($status === 'cancelled') {
            $sql .= " AND status LIKE 'Cancelled%'";
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        
        $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFilteredOrdersCount($phone, array $codes = [], $status = 'all', $time = 'all', $q = '') {
        $sql = "SELECT COUNT(DISTINCT o.id) FROM orders o";
        $where = [];
        $params = [];

        // 1. Identity filtering: phone or codes
        if (!empty($phone)) {
            $where[] = "o.phone = :phone";
            $params[':phone'] = $phone;
        } elseif (!empty($codes)) {
            $inQuery = [];
            foreach ($codes as $idx => $code) {
                $paramName = ":code_" . $idx;
                $inQuery[] = $paramName;
                $params[$paramName] = $code;
            }
            $where[] = "o.order_code IN (" . implode(',', $inQuery) . ")";
        } else {
            return 0; // No identity filter provided
        }

        // 2. Status filtering
        if ($status === 'processing') {
            $where[] = "o.status IN ('Placed', 'Preparing', 'Ready')";
        } elseif ($status === 'delivering') {
            $where[] = "o.status = 'Delivering'";
        } elseif ($status === 'completed') {
            $where[] = "o.status = 'Delivered'";
        } elseif ($status === 'cancelled') {
            $where[] = "o.status LIKE 'Cancelled%'";
        }

        // 3. Time filtering
        if ($time === '30days') {
            $where[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        } elseif ($time === '3months') {
            $where[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        } elseif ($time === '6months') {
            $where[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
        }

        // 4. Keyword search
        if (!empty($q)) {
            $where[] = "(o.order_code LIKE :q1 OR EXISTS (
                SELECT 1 FROM order_items oi 
                JOIN foods f ON oi.food_id = f.id 
                WHERE oi.order_id = o.id AND f.name LIKE :q2
            ))";
            $params[':q1'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getFilteredOrders($phone, array $codes = [], $offset = 0, $limit = 10, $status = 'all', $time = 'all', $sort = 'date_desc', $q = '') {
        $sql = "SELECT DISTINCT o.* FROM orders o";
        $where = [];
        $params = [];

        // 1. Identity filtering: phone or codes
        if (!empty($phone)) {
            $where[] = "o.phone = :phone";
            $params[':phone'] = $phone;
        } elseif (!empty($codes)) {
            $inQuery = [];
            foreach ($codes as $idx => $code) {
                $paramName = ":code_" . $idx;
                $inQuery[] = $paramName;
                $params[$paramName] = $code;
            }
            $where[] = "o.order_code IN (" . implode(',', $inQuery) . ")";
        } else {
            return []; // No identity filter provided
        }

        // 2. Status filtering
        if ($status === 'processing') {
            $where[] = "o.status IN ('Placed', 'Preparing', 'Ready')";
        } elseif ($status === 'delivering') {
            $where[] = "o.status = 'Delivering'";
        } elseif ($status === 'completed') {
            $where[] = "o.status = 'Delivered'";
        } elseif ($status === 'cancelled') {
            $where[] = "o.status LIKE 'Cancelled%'";
        }

        // 3. Time filtering
        if ($time === '30days') {
            $where[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        } elseif ($time === '3months') {
            $where[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        } elseif ($time === '6months') {
            $where[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
        }

        // 4. Keyword search
        if (!empty($q)) {
            $where[] = "(o.order_code LIKE :q1 OR EXISTS (
                SELECT 1 FROM order_items oi 
                JOIN foods f ON oi.food_id = f.id 
                WHERE oi.order_id = o.id AND f.name LIKE :q2
            ))";
            $params[':q1'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // 5. Sorting
        switch ($sort) {
            case 'date_asc':
                $sql .= " ORDER BY o.created_at ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY o.total DESC";
                break;
            case 'price_asc':
                $sql .= " ORDER BY o.total ASC";
                break;
            case 'date_desc':
            default:
                $sql .= " ORDER BY o.created_at DESC";
                break;
        }

        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);

        // Bind parameters
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats($phone, array $codes = []) {
        $stats = [
            'total_orders' => 0,
            'inflight_orders' => 0,
            'total_spent' => 0.0
        ];

        $where = [];
        $params = [];

        // 1. Identity filtering: phone or codes
        if (!empty($phone)) {
            $where[] = "phone = :phone";
            $params[':phone'] = $phone;
        } elseif (!empty($codes)) {
            $inQuery = [];
            foreach ($codes as $idx => $code) {
                $paramName = ":code_" . $idx;
                $inQuery[] = $paramName;
                $params[$paramName] = $code;
            }
            $where[] = "order_code IN (" . implode(',', $inQuery) . ")";
        } else {
            return $stats; // No identity, return zeros
        }

        $whereSql = " WHERE " . implode(" AND ", $where);

        // Query total orders count
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders" . $whereSql);
        $stmt->execute($params);
        $stats['total_orders'] = (int)$stmt->fetchColumn();

        // Query inflight orders count (Placed, Preparing, Ready, Delivering)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders" . $whereSql . " AND status IN ('Placed', 'Preparing', 'Ready', 'Delivering')");
        $stmt->execute($params);
        $stats['inflight_orders'] = (int)$stmt->fetchColumn();

        // Query total spent (Delivered)
        $stmt = $this->pdo->prepare("SELECT SUM(total) FROM orders" . $whereSql . " AND status = 'Delivered'");
        $stmt->execute($params);
        $stats['total_spent'] = (float)($stmt->fetchColumn() ?: 0.0);

        return $stats;
    }
}
