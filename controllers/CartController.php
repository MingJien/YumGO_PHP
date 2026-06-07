<?php
/**
 * YumGO - Controller điều phối Giỏ hàng (Session Cart)
 */

// Chặn truy cập trực tiếp
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

require_once dirname(__DIR__) . '/models/Food.php';

class CartController {
    private $foodModel;

    /**
     * Khởi tạo Controller với kết nối database
     * @param PDO $pdo Đối tượng kết nối CSDL
     */
    public function __construct(PDO $pdo) {
        $this->foodModel = new Food($pdo);
        
        // Đảm bảo Session đã được khởi chạy
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Khởi tạo giỏ hàng nếu chưa tồn tại
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    /**
     * Hiển thị trang Giỏ hàng
     */
    public function index() {
        $cartItems = [];
        $subtotal = 0;

        // Đọc dữ liệu giỏ hàng từ Session và truy vấn thông tin mới nhất từ CSDL
        foreach ($_SESSION['cart'] as $foodId => $item) {
            $food = $this->foodModel->getById($foodId);
            
            // Chỉ hiển thị món ăn tồn tại, chưa bị xóa và còn hàng bán
            if ($food && $food['is_available']) {
                // Tính giá bán thực tế sau giảm giá (nếu có)
                $finalPrice = $food['price'];
                if ($food['is_sale']) {
                    $finalPrice = $food['price'] * (1 - $food['discount_percent'] / 100);
                }
                
                $itemTotal = $finalPrice * $item['quantity'];
                $subtotal += $itemTotal;

                $cartItems[] = [
                    'food_id' => $food['id'],
                    'name' => $food['name'],
                    'image' => $food['image'],
                    'price' => $food['price'],
                    'is_sale' => $food['is_sale'],
                    'discount_percent' => $food['discount_percent'],
                    'final_price' => $finalPrice,
                    'quantity' => $item['quantity'],
                    'item_total' => $itemTotal
                ];
            } else {
                // Tự động loại bỏ món khỏi giỏ hàng nếu nó đã bị ẩn/xóa trên CSDL
                unset($_SESSION['cart'][$foodId]);
            }
        }

        $title = "Giỏ Hàng Của Bạn - YumGO";

        // Nhúng Layout và View tương ứng theo Integration Contract
        require_once dirname(__DIR__) . '/views/layouts/header.php';
        require_once dirname(__DIR__) . '/views/user/cart.php';
        require_once dirname(__DIR__) . '/views/layouts/footer.php';
    }

    /**
     * Thêm món ăn vào giỏ hàng
     */
    public function add() {
        // 1. Nhận dữ liệu đầu vào
        $foodId = isset($_POST['food_id']) ? filter_var($_POST['food_id'], FILTER_VALIDATE_INT) : 0;
        $quantity = isset($_POST['quantity']) ? filter_var($_POST['quantity'], FILTER_VALIDATE_INT) : 1;

        // 2. Xác thực dữ liệu (Bảo mật: chặn số lượng âm, số lượng rỗng)
        if ($foodId <= 0 || $quantity <= 0) {
            $this->redirectWithError("Số lượng món ăn không hợp lệ.");
        }

        // 3. Kiểm tra món ăn trong CSDL
        $food = $this->foodModel->getById($foodId);
        if (!$food) {
            $this->redirectWithError("Món ăn không tồn tại hoặc đã bị xóa.");
        }
        if (!$food['is_available']) {
            $this->redirectWithError("Món ăn này hiện đang tạm hết hàng.");
        }

        // 4. Thực hiện thêm/cập nhật vào Session
        if (isset($_SESSION['cart'][$foodId])) {
            $_SESSION['cart'][$foodId]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$foodId] = [
                'food_id' => $foodId,
                'quantity' => $quantity
            ];
        }

        // 5. Trả về phản hồi dựa theo hình thức yêu cầu (AJAX hoặc Direct Link)
        if ($this->isAjax()) {
            $cartCount = 0;
            if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $cartCount += $item['quantity'];
                }
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Đã thêm " . htmlspecialchars($food['name']) . " vào giỏ hàng!",
                'cart_count' => $cartCount
            ]);
            exit;
        }

        // Chuyển hướng về trang giỏ hàng nếu gửi form thường
        header("Location: index.php?page=cart&msg=added");
        exit;
    }

    /**
     * Cập nhật số lượng món ăn trong giỏ hàng (Gọi bằng POST từ form hoặc AJAX)
     */
    public function update() {
        $foodId = isset($_POST['food_id']) ? filter_var($_POST['food_id'], FILTER_VALIDATE_INT) : 0;
        $quantity = isset($_POST['quantity']) ? filter_var($_POST['quantity'], FILTER_VALIDATE_INT) : 0;

        if ($foodId <= 0) {
            $this->redirectWithError("Món ăn không hợp lệ.");
        }

        if ($quantity <= 0) {
            // Nếu số lượng <= 0, thực hiện xóa món ăn khỏi giỏ hàng
            unset($_SESSION['cart'][$foodId]);
        } else {
            // Kiểm tra tính khả dụng của món ăn
            $food = $this->foodModel->getById($foodId);
            if (!$food || !$food['is_available']) {
                unset($_SESSION['cart'][$foodId]);
                $this->redirectWithError("Món ăn hiện không khả dụng để đặt hàng.");
            }
            
            $_SESSION['cart'][$foodId]['quantity'] = $quantity;
        }

        // Trả về kết quả AJAX hoặc redirect
        if ($this->isAjax()) {
            $cartCount = 0;
            $subtotal = 0;
            $itemTotal = 0;
            foreach ($_SESSION['cart'] as $fid => $item) {
                $f = $this->foodModel->getById($fid);
                if ($f && $f['is_available']) {
                    $price = $f['is_sale'] ? ($f['price'] * (1 - $f['discount_percent']/100)) : $f['price'];
                    $subtotal += $price * $item['quantity'];
                    $cartCount += $item['quantity'];
                    if ($fid === $foodId) {
                        $itemTotal = $price * $item['quantity'];
                    }
                }
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Đã cập nhật số lượng thành công!",
                'cart_count' => $cartCount,
                'item_total' => number_format($itemTotal, 0, ',', '.') . 'đ',
                'subtotal' => number_format($subtotal, 0, ',', '.') . 'đ',
                'is_removed' => !isset($_SESSION['cart'][$foodId])
            ]);
            exit;
        }

        header("Location: index.php?page=cart&msg=updated");
        exit;
    }

    /**
     * Xóa một món ăn khỏi giỏ hàng
     */
    public function remove() {
        $foodId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

        if ($foodId > 0 && isset($_SESSION['cart'][$foodId])) {
            unset($_SESSION['cart'][$foodId]);
        }

        if ($this->isAjax()) {
            $cartCount = 0;
            $subtotal = 0;
            foreach ($_SESSION['cart'] as $fid => $item) {
                $f = $this->foodModel->getById($fid);
                if ($f && $f['is_available']) {
                    $price = $f['is_sale'] ? ($f['price'] * (1 - $f['discount_percent']/100)) : $f['price'];
                    $subtotal += $price * $item['quantity'];
                    $cartCount += $item['quantity'];
                }
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Đã xóa món ăn khỏi giỏ hàng!",
                'cart_count' => $cartCount,
                'subtotal' => number_format($subtotal, 0, ',', '.') . 'đ'
            ]);
            exit;
        }

        header("Location: index.php?page=cart&msg=removed");
        exit;
    }

    /**
     * Kiểm tra xem yêu cầu gửi lên là AJAX
     */
    private function isAjax(): bool {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
            || isset($_POST['ajax']) 
            || isset($_GET['ajax']);
    }

    /**
     * Hàm phụ trợ chuyển hướng kèm thông báo lỗi
     */
    private $errorRedirectPage = 'index.php?page=cart';
    private function redirectWithError(string $message) {
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
        $_SESSION['cart_error'] = $message;
        header("Location: " . $this->errorRedirectPage);
        exit;
    }
}
