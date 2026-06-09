<?php
$title = "Lịch sử đơn hàng - YumGO";
require_once 'views/layouts/header.php';
?>

<div class="container py-5" style="min-height: 70vh;">
    <h3 class="mb-4">Lịch sử đặt hàng của bạn</h3>
    
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="index.php" method="GET" class="d-flex align-items-center">
                <input type="hidden" name="page" value="order-tracking">
                <input type="text" name="code" class="form-control me-2" placeholder="Nhập mã đơn hàng để tra cứu..." required>
                <button type="submit" class="btn btn-primary-yumgo" style="white-space: nowrap;">Tra cứu ngay</button>
            </form>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-receipt" style="font-size: 3rem;"></i>
            <p class="mt-3">Bạn chưa có đơn hàng nào trong lịch sử (trên trình duyệt này).</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Ngày đặt</th>
                        <th>Khách hàng</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                            <td><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td class="text-danger fw-bold"><?= number_format($o['total'], 0, ',', '.') ?> đ</td>
                            <td>
                                <?php
                                $badgeClass = 'bg-secondary';
                                if ($o['status'] === 'Placed') $badgeClass = 'bg-info text-dark';
                                if ($o['status'] === 'Preparing') $badgeClass = 'bg-primary';
                                if ($o['status'] === 'Ready') $badgeClass = 'bg-warning text-dark';
                                if ($o['status'] === 'Delivering') $badgeClass = 'bg-info text-dark';
                                if ($o['status'] === 'Delivered') $badgeClass = 'bg-success';
                                if (strpos($o['status'], 'Cancelled') !== false) $badgeClass = 'bg-danger';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $o['status'] ?></span>
                            </td>
                            <td>
                                <a href="index.php?page=order-tracking&code=<?= htmlspecialchars($o['order_code']) ?>" class="btn btn-sm btn-outline-primary">Xem chi tiết</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'views/layouts/footer.php'; ?>