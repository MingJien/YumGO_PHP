<?php
/**
 * YumGO - Controller điều phối giỏ hàng bằng session và lưu theo tài khoản user.
 */

if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

require_once dirname(__DIR__) . '/models/Food.php';
require_once dirname(__DIR__) . '/models/UserCart.php';

class CartController {
    private Food $foodModel;
    private UserCart $userCartModel;
    private string $errorRedirectPage = 'index.php?page=cart';

    public function __construct(PDO $pdo) {
        $this->foodModel = new Food($pdo);
        $this->userCartModel = new UserCart($pdo);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    public function index(): void {
        $cartItems = [];
        $subtotal = 0;

        foreach ($_SESSION['cart'] as $foodId => $item) {
            $food = $this->foodModel->getById((int)$foodId);
            $quantity = (int)($item['quantity'] ?? 0);

            if ($food && $food['is_available'] && $quantity > 0) {
                $finalPrice = $this->getFinalPrice($food);
                $itemTotal = $finalPrice * $quantity;
                $subtotal += $itemTotal;

                $cartItems[] = [
                    'food_id' => $food['id'],
                    'name' => $food['name'],
                    'image' => $food['image'],
                    'price' => $food['price'],
                    'is_sale' => $food['is_sale'],
                    'discount_percent' => $food['discount_percent'],
                    'final_price' => $finalPrice,
                    'quantity' => $quantity,
                    'item_total' => $itemTotal
                ];
            } else {
                unset($_SESSION['cart'][$foodId]);
            }
        }

        $this->persistCartForCurrentUser();

        $cartSuggestionFoods = $this->foodModel->getCartSuggestions(array_keys($_SESSION['cart']), 4);
        $title = 'Giỏ hàng của bạn - YumGO';

        require_once dirname(__DIR__) . '/views/layouts/header.php';
        require_once dirname(__DIR__) . '/views/user/cart.php';
        require_once dirname(__DIR__) . '/views/layouts/footer.php';
    }

    public function add(): void {
        $this->requireValidCartCsrf();

        $foodId = isset($_POST['food_id']) ? filter_var($_POST['food_id'], FILTER_VALIDATE_INT) : 0;
        $quantity = isset($_POST['quantity']) ? filter_var($_POST['quantity'], FILTER_VALIDATE_INT) : 1;

        if ($foodId <= 0 || $quantity <= 0) {
            $this->redirectWithError('Số lượng món ăn không hợp lệ.');
        }

        $food = $this->foodModel->getById($foodId);
        if (!$food) {
            $this->redirectWithError('Món ăn không tồn tại hoặc đã bị xóa.');
        }
        if (!$food['is_available']) {
            $this->redirectWithError('Món ăn này hiện đang tạm hết hàng.');
        }

        if (isset($_SESSION['cart'][$foodId])) {
            $_SESSION['cart'][$foodId]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$foodId] = [
                'food_id' => $foodId,
                'quantity' => $quantity
            ];
        }

        $this->persistCartForCurrentUser();

        if ($this->isAjax()) {
            $this->jsonSuccess('Đã thêm ' . $food['name'] . ' vào giỏ hàng.');
        }

        header('Location: index.php?page=cart&msg=added');
        exit;
    }

    public function update(): void {
        $this->requireValidCartCsrf();

        $foodId = isset($_POST['food_id']) ? filter_var($_POST['food_id'], FILTER_VALIDATE_INT) : 0;
        $quantity = isset($_POST['quantity']) ? filter_var($_POST['quantity'], FILTER_VALIDATE_INT) : 0;

        if ($foodId <= 0) {
            $this->redirectWithError('Món ăn không hợp lệ.');
        }

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$foodId]);
        } else {
            $food = $this->foodModel->getById($foodId);
            if (!$food || !$food['is_available']) {
                unset($_SESSION['cart'][$foodId]);
                $this->persistCartForCurrentUser();
                $this->redirectWithError('Món ăn hiện không khả dụng để đặt hàng.');
            }

            $_SESSION['cart'][$foodId] = [
                'food_id' => $foodId,
                'quantity' => $quantity
            ];
        }

        $this->persistCartForCurrentUser();

        if ($this->isAjax()) {
            $summary = $this->buildCartSummary();
            $itemTotal = 0;
            if (isset($_SESSION['cart'][$foodId])) {
                $food = $this->foodModel->getById($foodId);
                if ($food) {
                    $itemTotal = $this->getFinalPrice($food) * (int)$_SESSION['cart'][$foodId]['quantity'];
                }
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật số lượng thành công.',
                'cart_count' => $summary['cart_count'],
                'item_total' => $this->formatMoney($itemTotal),
                'subtotal' => $summary['subtotal_formatted'],
                'is_removed' => !isset($_SESSION['cart'][$foodId]),
                'drawer_items' => $summary['drawer_items']
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: index.php?page=cart&msg=updated');
        exit;
    }

    public function remove(): void {
        $this->requireValidCartCsrf();

        $foodId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

        if ($foodId > 0 && isset($_SESSION['cart'][$foodId])) {
            unset($_SESSION['cart'][$foodId]);
        }

        $this->persistCartForCurrentUser();

        if ($this->isAjax()) {
            $this->jsonSuccess('Đã xóa món ăn khỏi giỏ hàng.');
        }

        header('Location: index.php?page=cart&msg=removed');
        exit;
    }

    public function drawer(): void {
        $summary = $this->buildCartSummary();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'items' => $summary['drawer_items'],
            'subtotal' => $summary['subtotal_formatted'],
            'cart_count' => $summary['cart_count'],
            'is_empty' => empty($summary['drawer_items']),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function jsonSuccess(string $message): void {
        $summary = $this->buildCartSummary();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'cart_count' => $summary['cart_count'],
            'subtotal' => $summary['subtotal_formatted'],
            'drawer_items' => $summary['drawer_items']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function buildCartSummary(): array {
        $cartCount = 0;
        $subtotal = 0;
        $drawerItems = [];

        foreach ($_SESSION['cart'] as $foodId => $item) {
            $food = $this->foodModel->getById((int)$foodId);
            $quantity = (int)($item['quantity'] ?? 0);

            if (!$food || !$food['is_available'] || $quantity <= 0) {
                unset($_SESSION['cart'][$foodId]);
                continue;
            }

            $finalPrice = $this->getFinalPrice($food);
            $itemTotal = $finalPrice * $quantity;
            $subtotal += $itemTotal;
            $cartCount += $quantity;

            $drawerItems[] = [
                'food_id' => (int)$food['id'],
                'name' => $food['name'],
                'image_url' => $this->foodImageUrl($food),
                'final_price' => $this->formatMoney($finalPrice),
                'quantity' => $quantity,
                'item_total' => $this->formatMoney($itemTotal),
                'remove_url' => 'index.php?page=cart-remove&id=' . (int)$food['id'] . '&csrf_token=' . urlencode(csrfToken()),
            ];
        }

        $this->persistCartForCurrentUser();

        return [
            'cart_count' => $cartCount,
            'subtotal' => $subtotal,
            'subtotal_formatted' => $this->formatMoney($subtotal),
            'drawer_items' => $drawerItems,
        ];
    }

    private function persistCartForCurrentUser(): void {
        if (($_SESSION['role'] ?? null) !== 'user' || empty($_SESSION['user']['id'])) {
            return;
        }

        $cart = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
        try {
            $this->userCartModel->replaceCart((int)$_SESSION['user']['id'], $cart);
        } catch (Throwable $exception) {
            error_log('Could not persist user cart: ' . $exception->getMessage());
        }
    }

    private function getFinalPrice(array $food): float {
        $price = (float)$food['price'];

        if (!empty($food['is_sale'])) {
            $price *= (1 - ((float)$food['discount_percent'] / 100));
        }

        return $price;
    }

    private function foodImageUrl(array $food): ?string {
        $image = !empty($food['image']) ? (string)$food['image'] : '';
        if ($image === '') {
            return null;
        }

        $path = dirname(__DIR__) . '/uploads/foods/' . $image;
        if (!file_exists($path)) {
            return null;
        }

        return 'uploads/foods/' . rawurlencode($image);
    }

    private function formatMoney(float $value): string {
        return number_format($value, 0, ',', '.') . 'đ';
    }

    private function isAjax(): bool {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || isset($_POST['ajax'])
            || isset($_GET['ajax']);
    }

    private function requireValidCartCsrf(): void {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        if (!verifyCsrfToken(is_string($token) ? $token : null)) {
            $this->redirectWithError('Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        }
    }

    private function redirectWithError(string $message): void {
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $_SESSION['cart_error'] = $message;
        header('Location: ' . $this->errorRedirectPage);
        exit;
    }
}
