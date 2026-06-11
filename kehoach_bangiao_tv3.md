# 🤝 Kế hoạch Bàn giao Mã nguồn & Tích hợp — Thành viên 3 (Checkout & Order)

Tài liệu này đóng vai trò là hợp đồng dữ liệu và kế hoạch làm việc nhằm chuyển giao sản phẩm của **Thành viên 2 (User Flow & Cart)** cho **Thành viên 3 (Checkout, Order & Voucher)** để tiếp tục ghép mã nguồn và triển khai hệ thống thanh toán.

---

## 📁 1. Trạng thái Git & Cách lấy mã nguồn

Mã nguồn hoàn chỉnh của Thành viên 2 đã được đẩy lên nhánh **`feature/member2-user`**.

**Cách Thành viên 3 lấy code về máy để làm việc:**
1. Chuyển sang nhánh `dev` hoặc tạo nhánh làm việc riêng của TV3:
   ```bash
   git checkout -b feature/member3-order
   ```
2. Thực hiện gộp mã nguồn của Thành viên 2 để thừa hưởng toàn bộ giao diện Trang chủ, Thực đơn, Giỏ hàng và kết nối CSDL:
   ```bash
   git pull origin feature/member2-user
   ```

---

## 🛒 2. Hợp đồng dữ liệu Giỏ hàng (Session Cart Contract)

Để tránh lỗ hổng bảo mật người dùng sửa giá từ phía Client (Inspect Element), **Giỏ hàng chỉ lưu trữ ID món ăn và Số lượng** trong `$_SESSION['cart']`.

### Cấu trúc dữ liệu trong Session:
```php
$_SESSION['cart'] = [
    2 => [ // Key của mảng chính là food_id
        'food_id' => 2,
        'quantity' => 1
    ],
    5 => [
        'food_id' => 5,
        'quantity' => 3
    ]
];
```

### Cách đọc dữ liệu tại trang Checkout:
Tại trang checkout (`?page=checkout`), Thành viên 3 bắt buộc phải truy vấn lại cơ sở dữ liệu để lấy giá tiền thực tế tại thời điểm thanh toán:

```php
// Nhúng Model Food để đọc thông tin món ăn từ CSDL
require_once 'models/Food.php';
$foodModel = new Food($pdo);

$subtotal = 0;
foreach ($_SESSION['cart'] as $foodId => $item) {
    $food = $foodModel->getById($foodId);
    if ($food && $food['is_available']) {
        // Tự động tính giá thực tế dựa trên chính sách giảm giá của món ăn
        $price = $food['is_sale'] ? ($food['price'] * (1 - $food['discount_percent']/100)) : $food['price'];
        
        $itemTotal = $price * $item['quantity'];
        $subtotal += $itemTotal;
        
        // Đoạn này dùng để insert vào bảng order_items của bạn...
    }
}
```

> [!WARNING]
> **Quy tắc an toàn tính toán giá (Recalculation Rule):**
> Tuyệt đối **không** tin tưởng tổng tiền gửi từ frontend, không dùng thẻ input ẩn (`<input type="hidden">`) để lưu giá trị đơn hàng từ trang Giỏ hàng sang trang Checkout. Mọi phép tính tiền, phí ship và giảm giá voucher **phải được tính toán lại hoàn toàn tại Backend**.

---

## 🔗 3. Hợp đồng định tuyến (Routing Contract)

* Khi người dùng nhấn nút **"Tiến hành đặt hàng"** tại trang Giỏ hàng, hệ thống của Thành viên 2 sẽ kiểm tra tính hợp lệ và tự động chuyển hướng:
  ```php
  header("Location: index.php?page=checkout");
  exit;
  ```
* **Yêu cầu đối với Thành viên 3:**
  * Khai báo thêm `case 'checkout':` trong tệp `index.php` để gọi Controller xử lý form thanh toán của bạn thay thế cho giao diện chờ tích hợp hiện tại.
  * Khai báo thêm các trang liên quan như `case 'order-history':` (Lịch sử đặt hàng) và `case 'order-tracking':` (Theo dõi đơn hàng).

---

## 📋 4. Danh sách nhiệm vụ chi tiết của Thành viên 3

Thành viên 3 cần thực hiện phát triển các tính năng sau dựa trên cấu trúc CSDL và giao diện chung:

### Nhiệm vụ 1: Lập trình Trang Thanh toán (Checkout Page)
* **Giao diện:** Thiết kế form nhập liệu gồm: *Họ tên, Số điện thoại, Địa chỉ giao hàng, Ghi chú, Phương thức thanh toán (COD hoặc chuyển khoản)*.
* **Xác thực dữ liệu (Validation):**
  * Số điện thoại chỉ chứa chữ số, độ dài hợp lệ, không chứa khoảng trắng.
  * Kiểm tra giỏ hàng không trống mới cho đặt hàng.
  * Các món ăn trong giỏ phải ở trạng thái khả dụng (`is_available = 1` và `is_deleted = 0`).

### Nhiệm vụ 2: Lưu đơn hàng bằng Database Transaction (Bắt buộc)
Khi khách nhấn nút "Xác nhận đặt hàng", hệ thống cần lưu thông tin vào CSDL. Do ghi nhận dữ liệu vào hai bảng khác nhau, bạn **bắt buộc** phải sử dụng Transaction của PDO để đảm bảo tính toàn vẹn dữ liệu:

```php
try {
    $pdo->beginTransaction();

    // 1. Chèn dữ liệu vào bảng `orders`
    $stmtOrder = $pdo->prepare("INSERT INTO `orders` ... ");
    $stmtOrder->execute([...]);
    $orderId = $pdo->lastInsertId();

    // 2. Duyệt giỏ hàng và chèn vào bảng `order_items`
    $stmtItem = $pdo->prepare("INSERT INTO `order_items` ... ");
    foreach ($_SESSION['cart'] as $id => $item) {
        // Lưu ý: Giá ở đây phải là giá snapshot tại thời điểm đặt (đã trừ % sale nếu có)
        $stmtItem->execute([
            'order_id' => $orderId,
            'food_id' => $item['food_id'],
            'quantity' => $item['quantity'],
            'price' => $actualPrice,
            'subtotal' => $actualPrice * $item['quantity']
        ]);
    }

    // 3. Xóa giỏ hàng trong session
    unset($_SESSION['cart']);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    // Ghi log lỗi và thông báo cho khách hàng
}
```

### Nhiệm vụ 3: Áp dụng mã giảm giá (Voucher System)
* Tạo ô nhập Voucher tại trang Checkout.
* Khi người dùng áp dụng mã, Backend truy vấn bảng `vouchers`:
  * Mã phải tồn tại và đang hoạt động (`is_active = 1`).
  * Thời gian hiện tại chưa vượt quá ngày hết hạn (`expired_at`).
  * Tổng tiền đơn hàng (`subtotal`) phải lớn hơn hoặc bằng giá trị đơn hàng tối thiểu (`min_order`).
* Tính toán lại tổng tiền thanh toán: `total = subtotal + shipping_fee - discount_amount`.

### Nhiệm vụ 4: Theo dõi đơn hàng & Lịch sử mua hàng (Guest Flow)
* Do dự án không yêu cầu khách hàng đăng ký tài khoản (Guest Checkout):
  * Khách hàng theo dõi trạng thái đơn hàng thông qua **Mã đơn hàng (`order_code`) + Số điện thoại**.
  * Lưu danh sách các mã đơn hàng (`order_code`) mà trình duyệt đó đã đặt vào `$_SESSION['order_history']` hoặc `localStorage` của Client để hiển thị lịch sử đặt hàng.
* Giao diện theo dõi đơn hàng dạng Timeline trực quan: 
  `[✓] Đã đặt (Placed) -> [✓] Đang chuẩn bị (Preparing) -> [ ] Chờ Shipper (Ready) -> [ ] Đang giao (Delivering) -> [ ] Đã giao (Delivered)`.

### Nhiệm vụ 5: Hủy đơn và Chỉnh sửa thông tin đơn
* **Quy định hủy đơn:** Khách hàng chỉ được phép bấm hủy đơn khi đơn hàng đang ở trạng thái `Placed` (Chờ xử lý) hoặc `Preparing` (Đang chuẩn bị).
* **Quy định sửa thông tin:** Khách hàng chỉ được phép sửa thông tin giao hàng (SĐT, địa chỉ, ghi chú, phương thức thanh toán) khi:
  * Trạng thái đơn là `Placed`.
  * Thời gian đặt hàng chưa quá 5 phút.
  * Số lần sửa đơn tối đa là 2 lần (kiểm tra cột `edit_count` trong bảng `orders`).

### Nhiệm vụ 6: Xuất hóa đơn PDF (Invoice)
* Sử dụng thư viện **DomPDF** (cần nạp qua Composer hoặc require trực tiếp).
* Xuất file hóa đơn PDF đẹp mắt gửi cho khách hàng lưu trữ sau khi đặt hàng thành công, hiển thị đầy đủ thông tin đơn hàng, danh sách món ăn, giá snapshot, giảm giá voucher và trạng thái đơn.
