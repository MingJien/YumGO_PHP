<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();
$pdo = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    requireValidCsrf();
} catch (Throwable $e) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// Chỉ tự chuyển các bước xử lý nội bộ của quán, không tự nhận/giao thay shipper.
$next = [
    'Placed' => 'Preparing',
    'Preparing' => 'Ready',
];

try {
    // Select orders that are in the pipeline (not final)
    $inStatuses = array_keys($next);
    $placeholders = implode(',', array_fill(0, count($inStatuses), '?'));
    $stmt = $pdo->prepare("SELECT id, order_code, status FROM orders WHERE status IN ($placeholders)");
    $stmt->execute($inStatuses);
    $orders = $stmt->fetchAll();

    $updated = [];
    foreach ($orders as $order) {
        $cur = $order['status'];
        if (isset($next[$cur])) {
            $nid = (int)$order['id'];
            $nstatus = $next[$cur];
            $u = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id AND status = :current_status');
            $u->execute(['status' => $nstatus, 'id' => $nid, 'current_status' => $cur]);
            $changed = $u->rowCount() === 1;
            if ($changed && function_exists('logActivity')) {
                logActivity($pdo, 'order', sprintf('Auto transition %s: %s -> %s', $order['order_code'], $cur, $nstatus), ['id' => $nid]);
            }
            if ($changed) {
                $updated[] = ['id' => $nid, 'from' => $cur, 'to' => $nstatus];
            }
        }
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'updated' => $updated], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
