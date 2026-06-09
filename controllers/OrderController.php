<?php
class OrderController {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function checkout() {
        // Kiểm tra giỏ hàng rỗng
        if (empty($_SESSION['cart'])) {
            header("Location: index.php?page=cart");
            exit;
        }

        require_once 'models/Food.php';
        $foodModel = new Food($this->pdo);

        $cartItems = [];
        $subtotal = 0;

        foreach ($_SESSION['cart'] as $foodId => $item) {
            $food = $foodModel->getById($foodId);
            if ($food && $food['is_available']) {
                $price = $food['is_sale'] ? ($food['price'] * (1 - $food['discount_percent'] / 100)) : $food['price'];
                $itemTotal = $price * $item['quantity'];
                $subtotal += $itemTotal;

                $cartItems[] = [
                    'food' => $food,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'itemTotal' => $itemTotal
                ];
            }
        }

        include 'views/user/checkout.php';
    }

    public function processCheckout() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
            header("Location: index.php?page=cart");
            exit;
        }

        require_once 'models/Food.php';
        require_once 'models/Voucher.php';
        $foodModel = new Food($this->pdo);
        $voucherModel = new Voucher($this->pdo);

        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $paymentMethod = trim($_POST['payment_method'] ?? 'COD');
        $voucherCodeInput = trim($_POST['voucher_code'] ?? '');

        // Validate cơ bản
        if (!$fullName || !$phone || !$address) {
            die("Vui lòng điền đầy đủ thông tin bắt buộc!");
        }

        try {
            $this->pdo->beginTransaction();

            $subtotal = 0;
            $orderItems = [];

            // 1. Tính toán lại giá
            foreach ($_SESSION['cart'] as $foodId => $item) {
                $food = $foodModel->getById($foodId);
                if ($food && $food['is_available'] && !$food['is_deleted']) {
                    $price = $food['is_sale'] ? ($food['price'] * (1 - $food['discount_percent'] / 100)) : $food['price'];
                    $itemSubtotal = $price * $item['quantity'];
                    $subtotal += $itemSubtotal;

                    $orderItems[] = [
                        'food_id' => $foodId,
                        'quantity' => $item['quantity'],
                        'price' => $price,
                        'subtotal' => $itemSubtotal
                    ];
                } else {
                    throw new Exception("Món ăn ID $foodId hiện không khả dụng!");
                }
            }

            $shippingFee = 15000;
            $discountAmount = 0;
            $appliedVoucherCode = null;

            // Xử lý áp dụng voucher
            if ($voucherCodeInput !== '') {
                $voucherResult = $voucherModel->validateCode($voucherCodeInput, $subtotal);
                if ($voucherResult['status']) {
                    $discountAmount = $voucherResult['discount_amount'];
                    $appliedVoucherCode = $voucherCodeInput;
                } else {
                    // Nếu nhập mã mà mã sai, văng lỗi ngay không cho đặt
                    throw new Exception($voucherResult['message']);
                }
            }

            $total = $subtotal + $shippingFee - $discountAmount;

            $orderCode = 'YGO-' . date('Ymd') . '-' . rand(1000, 9999);
            $editableUntil = date('Y-m-d H:i:s', strtotime('+5 minutes'));

            // 2. Insert bảng orders
            $stmtOrder = $this->pdo->prepare("
                INSERT INTO orders (order_code, customer_name, phone, address, note, payment_method, 
                                    subtotal, shipping_fee, discount_amount, total, voucher_code, status, editable_until) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Placed', ?)
            ");
            $stmtOrder->execute([
                $orderCode, $fullName, $phone, $address, $note, $paymentMethod,
                $subtotal, $shippingFee, $discountAmount, $total, $appliedVoucherCode, $editableUntil
            ]);
            $orderId = $this->pdo->lastInsertId();

            // 3. Insert bảng order_items
            $stmtItem = $this->pdo->prepare("
                INSERT INTO order_items (order_id, food_id, quantity, price, subtotal) 
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($orderItems as $item) {
                $stmtItem->execute([
                    $orderId, 
                    $item['food_id'], 
                    $item['quantity'], 
                    $item['price'], 
                    $item['subtotal']
                ]);
            }

            // 4. Cẩn thận lưu thông tin để tracking trước khi xoá session
            if (!isset($_SESSION['order_history'])) {
                $_SESSION['order_history'] = [];
            }
            $_SESSION['order_history'][] = $orderCode;

            // Xóa session giỏ hàng
            unset($_SESSION['cart']);

            $this->pdo->commit();
            
            // Chuyển hướng tới trang theo dõi đơn hàng với trạng thái đặt thành công
            header("Location: index.php?page=order-tracking&code=" . urlencode($orderCode) . "&success=1");
            exit;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            die("Lỗi xử lý đặt hàng: " . htmlspecialchars($e->getMessage()));
        }
    }

    public function history() {
        require_once 'models/Order.php';
        $orderModel = new Order($this->pdo);

        // Đọc danh sách mã đơn hàng từ session
        $historyCodes = $_SESSION['order_history'] ?? [];
        $orders = $orderModel->getByOrderCodes($historyCodes);

        include 'views/user/order-history.php';
    }

    public function search() {
        $title = "Tra cứu đơn hàng - YumGO";
        require_once 'views/layouts/header.php';
        require_once 'views/user/order-search.php';
        require_once 'views/layouts/footer.php';
    }

    public function tracking() {
        require_once 'models/Order.php';
        $orderModel = new Order($this->pdo);

        $code = $_GET['code'] ?? '';
        $phone = trim($_GET['phone'] ?? '');
        $order = null;
        $items = [];

        if ($code) {
            $order = $orderModel->getByOrderCode($code);
            if ($order) {
                if ($phone !== '' && $order['phone'] !== $phone) {
                    $order = null;
                }
            }

            if ($order) {
                // Lấy chi tiết món ăn trong đơn hàng
                $items = $orderModel->getItems($order['id']);
                
                // Nếu tìm thấy đơn, lưu bổ sung vào lịch sử (nếu người dùng tra cứu từ tab ẩn danh hoặc máy khác)
                if (!isset($_SESSION['order_history'])) {
                    $_SESSION['order_history'] = [];
                }
                if (!in_array($code, $_SESSION['order_history'])) {
                    $_SESSION['order_history'][] = $code;
                }
            }
        }

        include 'views/user/order-tracking.php';
    }

    public function cancel() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=home");
            exit;
        }
        $code = $_POST['code'] ?? '';
        require_once 'models/Order.php';
        $orderModel = new Order($this->pdo);
        
        $order = $orderModel->getByOrderCode($code);
        if ($order && in_array($order['status'], ['Placed', 'Preparing'])) {
            $orderModel->cancelUserOrder($order['id']);
        }
        header("Location: index.php?page=order-tracking&code=" . filter_var($code, FILTER_SANITIZE_URL));
        exit;
    }

    public function edit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?page=home");
            exit;
        }
        $code = $_POST['code'] ?? '';
        
        require_once 'models/Order.php';
        $orderModel = new Order($this->pdo);
        $order = $orderModel->getByOrderCode($code);
        
        // Kiểm tra điều kiện khắt khe
        if ($order 
            && $order['status'] === 'Placed' 
            && $order['edit_count'] < 2 
            && strtotime($order['editable_until']) > time()
        ) {
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $note = trim($_POST['note'] ?? '');
            $paymentMethod = trim($_POST['payment_method'] ?? 'COD');
            
            if ($phone && $address) {
                $orderModel->updateOrderInfo($order['id'], $phone, $address, $note, $paymentMethod);
            }
        }
        header("Location: index.php?page=order-tracking&code=" . filter_var($code, FILTER_SANITIZE_URL));
        exit;
    }

    public function exportInvoice() {
        $code = $_GET['code'] ?? '';
        if (!$code) {
            die('Mã đơn hàng không hợp lệ.');
        }

        require_once 'models/Order.php';
        $orderModel = new Order($this->pdo);
        $order = $orderModel->getByOrderCode($code);
        
        if (!$order) {
            die('Không tìm thấy đơn hàng.');
        }
        
        $items = $orderModel->getItems($order['id']);

        // Load DomPDF
        require_once 'vendor/autoload.php';
        
        // Dompdf namespace
        $dompdf = new \Dompdf\Dompdf();
        
        // Start output buffering cho template HTML
        ob_start();
        include 'views/user/invoice-template.php';
        $html = ob_get_clean();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Xuất file PDF (attachment = false để xem trực tiếp trên trình duyệt, = true để tải về)
        $dompdf->stream("hoadon_yumgo_" . $order['order_code'] . ".pdf", array("Attachment" => false));
    }
}