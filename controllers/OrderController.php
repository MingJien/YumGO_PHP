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

        $phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
        if ($phone === '' && isset($_SESSION['role']) && $_SESSION['role'] === 'user' && !empty($_SESSION['user']['phone'])) {
            $phone = trim($_SESSION['user']['phone']);
        }

        $page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
        $status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
        $time = isset($_GET['time']) ? trim($_GET['time']) : 'all';
        $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'date_desc';
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';

        // Validate whitelist
        if (!in_array($status, ['all', 'processing', 'delivering', 'completed', 'cancelled'])) {
            $status = 'all';
        }
        if (!in_array($time, ['all', '30days', '3months', '6months'])) {
            $time = 'all';
        }
        if (!in_array($sort, ['date_desc', 'date_asc', 'price_desc', 'price_asc'])) {
            $sort = 'date_desc';
        }

        $limit = 10;
        $offset = ($page - 1) * $limit;
        $totalOrders = 0;
        $totalPages = 1;
        $orders = [];

        $historyCodes = $_SESSION['order_history'] ?? [];
        
        // Fetch stats dashboard
        $stats = $orderModel->getStats($phone, $historyCodes);

        if ($phone !== '' || !empty($historyCodes)) {
            $totalOrders = $orderModel->getFilteredOrdersCount($phone, $historyCodes, $status, $time, $q);
            $totalPages = (int)ceil($totalOrders / $limit);
            $orders = $orderModel->getFilteredOrders($phone, $historyCodes, $offset, $limit, $status, $time, $sort, $q);
            
            // Attach food items to each order
            foreach ($orders as &$o) {
                $o['items'] = $orderModel->getItems($o['id']);
            }
            unset($o);
        }

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
        $phoneInput = trim($_REQUEST['phone'] ?? '');
        $order = null;
        $items = [];
        $errorMsg = '';
        $requiresVerification = false;

        if ($code) {
            $order = $orderModel->getByOrderCode($code);
            if ($order) {
                $isAuthorized = false;

                // 1. Session authorization (already verified or placed in this session)
                if (isset($_SESSION['order_history']) && in_array($code, $_SESSION['order_history'])) {
                    $isAuthorized = true;
                }
                // 2. Logged-in user matching order phone
                elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'user' && !empty($_SESSION['user']['phone']) && trim($_SESSION['user']['phone']) === trim($order['phone'])) {
                    $isAuthorized = true;
                }

                // If not authorized, try verifying with the provided phone input
                if (!$isAuthorized && $phoneInput !== '') {
                    if (trim($order['phone']) === $phoneInput) {
                        if (!isset($_SESSION['order_history'])) {
                            $_SESSION['order_history'] = [];
                        }
                        if (!in_array($code, $_SESSION['order_history'])) {
                            $_SESSION['order_history'][] = $code;
                        }
                        $isAuthorized = true;
                    } else {
                        $errorMsg = 'Số điện thoại không chính xác. Vui lòng nhập lại!';
                        $requiresVerification = true;
                    }
                }

                // If authorized and phone was sent in the request (GET or POST),
                // redirect to clean URL to strip the phone number immediately.
                if ($isAuthorized && $phoneInput !== '') {
                    header("Location: index.php?page=order-tracking&code=" . urlencode($code));
                    exit;
                }

                if (!$isAuthorized) {
                    $requiresVerification = true;
                } else {
                    $items = $orderModel->getItems($order['id']);
                    
                    // Keep session synced
                    if (!isset($_SESSION['order_history'])) {
                        $_SESSION['order_history'] = [];
                    }
                    if (!in_array($code, $_SESSION['order_history'])) {
                        $_SESSION['order_history'][] = $code;
                    }
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

    public function reorder() {
        $code = $_GET['code'] ?? '';
        if (!$code) {
            header("Location: index.php?page=home");
            exit;
        }

        require_once 'models/Order.php';
        $orderModel = new Order($this->pdo);
        $order = $orderModel->getByOrderCode($code);
        if (!$order) {
            header("Location: index.php?page=home");
            exit;
        }

        // Verify authorization (same as order-tracking verification)
        $isAuthorized = false;
        if (isset($_SESSION['order_history']) && in_array($code, $_SESSION['order_history'])) {
            $isAuthorized = true;
        } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'user' && !empty($_SESSION['user']['phone']) && trim($_SESSION['user']['phone']) === trim($order['phone'])) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            header("Location: index.php?page=order-tracking&code=" . urlencode($code));
            exit;
        }

        $mode = $_GET['mode'] ?? 'merge';
        if (!in_array($mode, ['replace', 'merge'], true)) {
            $mode = 'merge';
        }

        $items = $orderModel->getItems($order['id']);
        if (!empty($items)) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if ($mode === 'replace') {
                $_SESSION['cart'] = [];
            } elseif (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            require_once 'models/Food.php';
            $foodModel = new Food($this->pdo);

            foreach ($items as $item) {
                $foodId = (int)$item['food_id'];
                $quantity = (int)$item['quantity'];

                // Verify the food is still available and not deleted
                $food = $foodModel->getById($foodId);
                if ($food && $food['is_available'] && !$food['is_deleted']) {
                    // Ghi đè số lượng từ đơn hàng cũ lên giỏ hàng (thay vì cộng dồn +=) để tránh chồng chất
                    $_SESSION['cart'][$foodId] = [
                        'food_id' => $foodId,
                        'quantity' => $quantity
                    ];
                }
            }

            // Sync user cart to database if logged in
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'user' && !empty($_SESSION['user']['id'])) {
                require_once 'models/UserCart.php';
                try {
                    $userCartModel = new UserCart($this->pdo);
                    $userCartModel->replaceCart((int)$_SESSION['user']['id'], $_SESSION['cart']);
                } catch (Throwable $exception) {
                    error_log('Could not persist reordered user cart: ' . $exception->getMessage());
                }
            }
        }

        header("Location: index.php?page=cart&msg=added");
        exit;
    }
}