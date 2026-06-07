# 🤝 Tài liệu Bàn giao Tích hợp — Thành viên 2 (User Website & User Flow)

Tài liệu này dùng để bàn giao cho **Thành viên 3 (Checkout & Order)** và **Thành viên 1 (Core System)** để phục vụ quá trình ghép code và tích hợp hệ thống YumGO.

---

## 📁 1. Danh sách các File đã triển khai
Toàn bộ phần code của Thành viên 2 tuân thủ đúng cấu trúc thư mục quy chuẩn:
*   **Cấu hình & Kết nối:**
    *   [`/config/config.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/config/config.php): Chứa hằng số kết nối (đã cấu hình chạy trên cổng `3307` của XAMPP) và nhận diện tự động `BASE_URL`.
    *   [`/includes/database.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/includes/database.php): Khởi tạo đối tượng PDO `$pdo`.
*   **Model (Tái sử dụng cho cả team):**
    *   [`/models/Category.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/models/Category.php): Đọc danh mục hoạt động.
    *   [`/models/Food.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/models/Food.php): Truy vấn chi tiết, danh sách món, tìm kiếm, lọc và phân trang.
*   **Controller điều phối:**
    *   [`/controllers/UserHomeController.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/controllers/UserHomeController.php): Trang chủ.
    *   [`/controllers/FoodController.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/controllers/FoodController.php): Tìm kiếm, lọc và chi tiết món.
    *   [`/controllers/CartController.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/controllers/CartController.php): Nghiệp vụ giỏ hàng (thêm, cập nhật số lượng, xóa).
*   **Giao diện (Views & Assets):**
    *   [`/assets/css/user.css`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/assets/css/user.css): CSS chứa design tokens (màu sắc, bo góc, shadows, mobile bottom nav).
    *   [`/views/layouts/header.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/views/layouts/header.php) & [`footer.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/views/layouts/footer.php): Khung giao diện dùng chung.
    *   [`/views/user/home.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/views/user/home.php), [`foods.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/views/user/foods.php), [`food-detail.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/views/user/food-detail.php), [`cart.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/views/user/cart.php): Các trang hiển thị.
    *   [`/views/user/partials/food-card.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/views/user/partials/food-card.php) & [`empty-state.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/views/user/partials/empty-state.php): Các thành phần giao diện nhỏ.
*   **Router & Test:**
    *   [`/index.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/index.php): Định tuyến chính.
    *   [`/test_integration.php`](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/test_integration.php): Tệp kiểm thử kết nối CSDL và Model.

---

## 🛒 2. Session Cart Contract (Bàn giao cho Thành viên 3)

Để tránh lỗ hổng bảo mật người dùng sửa giá từ phía Client, **Giỏ hàng chỉ lưu trữ ID món ăn và Số lượng** trong `$_SESSION['cart']`.

### Cấu trúc chuẩn lưu trong Session:
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

### Cách Thành viên 3 đọc dữ liệu tại trang Checkout:
Tại trang checkout (`?page=checkout`), Thành viên 3 chỉ cần duyệt qua `$_SESSION['cart']`, kết nối tới CSDL thông qua Model `Food` để lấy thông tin giá tiền, khuyến mãi mới nhất và tính tổng số tiền thanh toán thực tế:

```php
require_once 'models/Food.php';
$foodModel = new Food($pdo);

$subtotal = 0;
foreach ($_SESSION['cart'] as $foodId => $item) {
    $food = $foodModel->getById($foodId);
    if ($food && $food['is_available']) {
        // Tự động tính giá thực tế dựa trên chính sách giảm giá
        $price = $food['is_sale'] ? ($food['price'] * (1 - $food['discount_percent']/100)) : $food['price'];
        
        $itemTotal = $price * $item['quantity'];
        $subtotal += $itemTotal;
        
        // Thêm vào cơ sở dữ liệu hóa đơn của bạn (bảng order_items) ở đây...
    }
}
```

---

## 🔗 3. Hợp đồng định tuyến (Routing Contract)
Khi người dùng bấm **"Tiến hành đặt hàng"** tại trang Giỏ hàng, hệ thống của Thành viên 2 sẽ tự động thực hiện chuyển hướng sau:
```php
header("Location: index.php?page=checkout");
exit;
```
**Yêu cầu đối với Thành viên 3:**
*   Cần đăng ký và xử lý giao diện checkout tại route `index.php?page=checkout`.
*   Cần đọc dữ liệu từ `$_SESSION['cart']` để xử lý form thanh toán, lưu đơn hàng vào các bảng `orders`, `order_items` và tiến hành trừ mã giảm giá (voucher).

---

## 📱 4. Thiết kế Tối ưu giao diện cho Mobile (Mobile-first UX)
Nhằm mang lại trải nghiệm tiện lợi và kích thích khách hàng đặt món nhanh nhất, giao diện mobile của Thành viên 2 đã được tối ưu hóa như sau:
*   **Menu Hamburger bên trái:** Thay thế thanh điều hướng desktop cồng kềnh bằng Sidebar Drawer điều hướng bên trái di động sử dụng Alpine.js (bật/tắt mượt mà).
*   **Gỡ bỏ Bottom Navigation:** Thanh điều hướng dưới cùng (Bottom Nav) đã được loại bỏ hoàn toàn để tối ưu không gian màn hình hiển thị sản phẩm và tránh xung đột với các thành phần khác.
*   **Sticky Bottom CTA:** Nút "Thêm vào giỏ" ở trang chi tiết món ăn [food-detail.php](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/views/user/food-detail.php) được chuyển thành dạng thanh bám sát đáy (`bottom: 0`) trên thiết bị di động để người dùng dễ dàng thao tác bằng một tay.
*   **Card-based Cart:** Giỏ hàng trên di động hiển thị dạng danh sách Card đứng thay vì dạng Table ngang để không bị tràn màn hình [cart.php](file:///d:/CNTT23A-CS/HK3%202025-2026/Lap%20trinh%20PHP/YumGOv1/views/user/cart.php).

---

## 🚀 5. Các Tính Năng Nâng Cao Hỗ Trợ Đặt Món (Appetizer & Conversion Boosters)
Nhằm lấp đầy khoảng trống trải nghiệm và gia tăng tỷ lệ chuyển đổi, Thành viên 2 đã tái thiết kế hoàn toàn trang chủ theo mô hình **Premium Food Delivery Startup** với các mảnh ghép giao diện đột phá:
*   **Hero Premium 100vh:** Cấu trúc Split-screen chiếm trọn khung nhìn ban đầu. Bên trái chứa tag thương hiệu, tiêu đề cực lớn dạng Apple-style, ô tìm kiếm cao 56px và nhãn gợi ý. Bên phải chứa ảnh combo đồ ăn AI cinematic sắc nét và 4 thẻ trôi nổi lơ lửng (Rating, Speed, Free Ship, Orders today) có hiệu ứng nền kính mờ glassmorphism và shadow đa tầng.
*   **Trust Section tối giản:** Khối chỉ số uy tín dạng hàng ngang kiểu Stripe (50k+ đơn hàng, 300+ đối tác, 4.9★, 15 phút giao hàng) ngay dưới Hero tạo ấn tượng đáng tin cậy.
*   **Bento Grid Lợi ích (Bento Background Diversity):** Phân chia 3 thẻ cam kết với các màu nền nhạt tương phản dịu mắt (`bento-delivery` cam nhạt, `bento-hygiene` xanh lá nhạt, `bento-gift` vàng nhạt) tăng tính nhận diện dịch vụ.
*   **Khối đánh giá khách hàng (Testimonials Grid):** Lưới 3 cột hiển thị các review của khách hàng thật với sao vàng lấp lánh và avatar monogram tinh tế.
*   **Khối đề xuất "Có thể bạn cũng thích" (Related Foods):** Hiển thị 4 sản phẩm cùng danh mục được gợi ý ở cuối trang chi tiết món ăn nhằm khuyến khích khách hàng mua thêm (upsell).
*   **Hiệu ứng cuộn GSAP ScrollTrigger:** Các category pills, food cards và testimonial cards tự động trượt stagger xuất hiện tuần tự khi cuộn trang, tự động vô hiệu hóa nếu thiết bị bật `prefers-reduced-motion`.
