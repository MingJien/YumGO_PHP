YUMGO
ĐẶC TẢ HỆ THỐNG
WEBSITE ĐẶT ĐỒ ĂN ONLINE PHP + MYSQL
 1. TỔNG QUAN DỰ ÁN
Tên ứng dụng: YumGo
Ý nghĩa:
Yum = ngon
GO = nhanh, giao nhanh
Mục tiêu: Xây dựng hệ thống đặt đồ ăn online theo mô hình thực tế, hỗ trợ:
xem món ăn
tìm kiếm/lọc món
giỏ hàng
đặt hàng
voucher
theo dõi đơn hàng
shipper nhận giao
admin quản lý
responsive mobile-first
installable web app (PWA)
Mục tiêu cuối: Tạo mini food delivery web app có business flow giống ứng dụng thực tế nhưng vẫn nằm trong khả năng môn PHP.
2. CÔNG NGHỆ SỬ DỤNG
Backend:
PHP 8+
PDO Prepared Statement
Database:
MySQL / MariaDB
Frontend:
HTML5
CSS3
Bootstrap 5
JavaScript
Môi trường:
XAMPP
VS Code
Thư viện:
Bootstrap Icons
DomPDF
3. KIẾN TRÚC & CẤU TRÚC DỰ ÁN
3.1 CẤU TRÚC THƯ MỤC /app /controllers /models /views
/admin
/config
/includes
/assets /css /js /images
/uploads /foods /avatars /banners
/database
3.2 Ý NGHĨA
/controllers
xử lý logic
/models
truy vấn database
/views
giao diện
/admin
giao diện admin
/uploads
chứa file upload thật
/assets
css/js/images hệ thống
 3.3 QUY TẮC ĐẶT TÊN
Database: snake_case
Ví dụ:
order_items
delivery_fee
PHP variables: camelCase
Ví dụ:
$cartItems
$orderTotal
CSS: kebab-case
Ví dụ:
food-card
mobile-navbar
 4. GIT WORKFLOW & QUY TẮC TEAMWORK
4.1 BRANCHES
main
production stable
dev
merge tổng
feature/member1-admin feature/member2-user feature/member3-order feature/member4-ui
4.2 QUY TẮC GIT
KHÔNG:
push trực tiếp main
đổi database schema tùy ý
rename folder bừa bãi
sửa shared layout không báo team
BẮT BUỘC:
pull trước push
test trước merge
commit message rõ nghĩa
Ví dụ:
feat: add voucher validation
fix: resolve cart subtotal bug
style: improve mobile navbar
5. THIẾT KẾ DATABASE
 5.1 BẢNG CATEGORIES
Id
name
is_deleted
created_at
updated_at
Mục đích: quản lý danh mục món ăn
5.2 BẢNG FOODS
Id
category_id
name
price
image
description
is_hot
is_sale
discount_percent
is_available
is_deleted
created_at
updated_at
 QUY TẮC
image:
chỉ lưu path hoặc filename
Ví dụ: burger.jpg
KHÔNG lưu:
image blob
binary image
 5.3 BẢNG VOUCHERS
Id
code
type
value
min_order
expired_at
is_active
created_at
 TYPE
percent
giảm %
fixed
giảm tiền cố định
 5.4 BẢNG ORDERS
Id
order_code
customer_name
phone
address
note
payment_method
subtotal
shipping_fee
discount_amount
total
voucher_code
status
edit_count
editable_until
shipper_name
shipper_phone
created_at
updated_at
 TRẠNG THÁI ĐƠN HÀNG
Đã đặt
 Đang chuẩn bị
 Sẵn sàng giao
Đang giao
Đã giao
Cancelled By User
Cancelled By Admin

LƯU Ý QUAN TRỌNG
Database luôn lưu:
trạng thái cuối cùng hiện tại của đơn hàng
Ví dụ: Đã giao hoặc Khách đã hủy
KHÔNG xóa order thật khỏi DB.

 5.5 BẢNG ORDER_ITEMS

id order_id food_id quantity price subtotal

5.6 BẢNG ADMINS ==================================================
id username password display_name avatar created_at
================================================== 5.7 BẢNG SHIPPERS ==================================================
id name phone avatar is_active created_at

6.QUẢN LÝ HÌNH ẢNH
6.1ẢNH MÓN ĂN
File thật:
/uploads/foods/
DB lưu:
filename/path
6.2 AVATAR
Admin/Shipper avatar:
/uploads/avatars/
6.3 BANNER & LOGO
Logo:
/assets/images/logo/
Banner:
/uploads/banners/
 6.4 VALIDATION IMAGE
BẮT BUỘC:
rename bằng uniqid()
validate extension
validate size
Chỉ cho phép:
jpg
png
webp
KHÔNG:
dùng tên file gốc
trust frontend extension
7. PHÂN QUYỀN HỆ THỐNG
7.1 ADMIN
Admin có quyền:
login/logout
quản lý món ăn
quản lý category
quản lý voucher
quản lý đơn hàng
cập nhật trạng thái
hủy đơn
quản lý shipper
xem dashboard
7.2 USER
User:
không cần register/login full system
sử dụng guest checkout flow
User có thể:
xem món
cart
checkout
theo dõi đơn
sửa đơn giới hạn
hủy đơn giới hạn
xem lịch sử đơn
 7.3 SHIPPER
Shipper:
lite delivery role
KHÔNG triển khai:
GPS
realtime tracking
socket
bản đồ
8. LUỒNG NGHIỆP VỤ HỆ THỐNG
 8.1 LUỒNG ĐẶT HÀNG
Xem món → Thêm giỏ hàng → Checkout → Nhập voucher → Tạo đơn → Admin xử lý → Sẵn sàng giao → Shipper nhận giao → Đang giao → Đã giao
 8.2 LUỒNG SHIPPER
Khi: status = Sẵn sàng giao
Đơn xuất hiện ở: Delivery Board
Shipper nhấn: Accept Delivery
Hệ thống update:
shipper_name
shipper_phone
status = Đang giao
8.3 LUỒNG HỦY ĐƠN
User: chỉ được hủy khi:
Đã đặt
Đang chuẩn bị
Admin: có thể hủy mọi lúc
 8.4 LUỒNG SỬA ĐƠN
User chỉ được sửa:
phone
address
note
payment_method
voucher_code
KHÔNG được sửa:
food items
quantity
order status
 ĐIỀU KIỆN SỬA
status = Đã đặt
AND
edit_count < 2
AND
current_time < editable_until
 GIỚI HẠN
tối đa 2 lần
trong vòng 5 phút sau khi đặt
9. CHỨC NĂNG USER
9.1 HOMEPAGE
Hiển thị:
banner
categories
featured foods
responsive UI
9.2 FOOD LISTING
Hiển thị:
image
name
price
HOT/SALE badge
available status
 9.3 SEARCH & FILTER
search theo tên
filter category
pagination
9.4 FOOD DETAIL
Hiển thị:
ảnh lớn
mô tả
giá
add to cart
 9.5 CART
Cho phép:
update quantity
remove item
subtotal
9.6 CHECKOUT
Input:
customer_name
phone
address
note
payment_method
voucher
9.7 VOUCHER
Admin tạo voucher.
User nhập/chọn voucher có sẵn.
Backend validate:
tồn tại
active
chưa hết hạn
đủ min_order
 9.8 ORDER TRACKING
Progress UI:
[✓] Placed [✓] Preparing [ ] Ready [ ] Delivering [ ] Delivered
 9.9 ORDER HISTORY
Hiển thị:
order code
total
created_at
final status
 9.10 EXPORT PDF
Invoice gồm:
order code
items
subtotal
shipping
discount
total
final status
10. CHỨC NĂNG ADMIN
 10.1 AUTH
login
logout
session protection
10.2 DASHBOARD
Hiển thị:
total orders
total revenue
best seller
 10.3 CATEGORY CRUD
add
edit
soft delete
10.4 FOOD CRUD
add
edit
upload image
set HOT
set SALE
set available
10.5 VOUCHER CRUD
create
update
disable
 10.6 ORDER MANAGEMENT
Admin có thể:
xem order
xem detail
update status
cancel order
10.7 SHIPPER MANAGEMENT
create shipper
update shipper
active/inactive
10.8 PROFILE
Admin có thể:
đổi tên
đổi avatar
đổi password
11. CHỨC NĂNG SHIPPER
11.1 DELIVERY BOARD
Shipper xem: các đơn Ready( trạng thái đã chuẩn bị )
 11.2 THÔNG TIN HIỂN THỊ
order code
customer name
phone
address
delivery fee
11.3 ACCEPT DELIVERY
Update:
shipper_name
shipper_phone
status = Delivering
 11.4 COMPLETE DELIVERY
Update: status = Delivered
12. PWA & MOBILE-FIRST
12.1 RESPONSIVE
Bắt buộc hỗ trợ:
mobile
tablet
desktop
2.2 MOBILE FEATURES
bottom navigation
touch-friendly UI
mobile spacing
 12.3 PWA
manifest.json
service worker
installable app
splash screen
add to home screen
13. RÀNG BUỘC QUAN TRỌNG
13.1 KHÔNG ĐƯỢC
delete order thật
trust frontend total
trust frontend voucher
trust frontend price
 13.2 BẮT BUỘC
PDO Prepared Statement
backend recalculation
validate upload image
responsive mobile
13.3 CHECKOUT TRANSACTION
BEGIN TRANSACTION
insert orders
insert order_items
COMMIT
Nếu lỗi: ROLLBACK

14.DATABASE RELATIONSHIP & DATA RULES
14.1 FOREIGN KEY RELATIONSHIP
foods.category_id → categories.id
order_items.order_id → orders.id
order_items.food_id → foods.id
14.2 DATA TYPE RECOMMENDATION
Price fields:
DECIMAL(10,2)
Ví dụ:
price
subtotal
shipping_fee
discount_amount
total
Text fields:
VARCHAR phù hợp
description dùng TEXT
 14.3 INDEXING
Nên tạo INDEX cho:
foods.name
orders.status
orders.created_at
vouchers.code
Mục đích:
tăng tốc search/filter/query
14.4 PRICE SNAPSHOT RULE
order_items.price phải lưu:
giá món tại thời điểm đặt hàng
Nếu admin thay đổi giá món sau đó:
đơn hàng cũ KHÔNG được thay đổi giá
14.5 SOFT DELETE RULE
Foods và categories: KHÔNG delete thật khỏi DB.
Chỉ update: is_deleted = 1
RULE HIỂN THỊ
User: KHÔNG được thấy:
food deleted
category deleted
Admin:
vẫn xem được
có thể restore
14.6 FOOD AVAILABILITY RULE
Nếu: is_available = 0
THÌ:
không được add cart
không được checkout
UI hiển thị: “Currently Unavailable”
 15. STATUS FLOW RULES
 15.1 STATUS TRANSITION
Flow hợp lệ:
Placed → Preparing → Ready → Delivering → Delivered
 15.2 CANCEL FLOW
Placed → Cancelled By User
Preparing → Cancelled By User
Placed → Cancelled By Admin
Preparing → Cancelled By Admin
Ready → Cancelled By Admin
 15.3 KHÔNG HỢP LỆ
KHÔNG được:
Placed → Delivered
Ready → Preparing
Delivered → trạng thái khác
Cancelled → trạng thái khác
 15.4 FINAL STATUS RULE
Status cuối cùng:
Delivered hoặc
Cancelled
Sau khi final: KHÔNG được update tiếp.
15.VALIDATION RULES
16.1 PHONE VALIDATION
Phone:
chỉ cho số
độ dài hợp lệ
trim khoảng trắng
16.2 QUANTITY VALIDATION
Quantity:
phải > 0
không cho số âm
không cho string
16.3 SEARCH RULE
Search:
case-insensitive
partial match
Ví dụ: “bur” → tìm được: Burger
 16.4 VOUCHER VALIDATION
Voucher hợp lệ khi:
tồn tại
active
chưa hết hạn
đủ min_order
16.5 VOUCHER CONSTRAINT
Mỗi đơn:
chỉ dùng 1 voucher
KHÔNG:
stack nhiều voucher
discount_amount: KHÔNG được lớn hơn subtotal
 16.6 INPUT SANITIZATION
BẮT BUỘC:
trim()
htmlspecialchars()
prepared statement
 17. SESSION & AUTH RULES
17.1 ADMIN AUTH
Admin authentication:
dùng PHP Session
17.2 SESSION PROTECTION
Nếu chưa login: KHÔNG được truy cập:
admin dashboard
admin CRUD pages
17.3 CART SESSION
Cart:
lưu bằng PHP Session
 17.4 LOGOUT RULE
Logout phải:
destroy session
redirect login
18. CONFIGURATION RULES
 18.1 CONFIG FILE
DB config, BASE_URL và upload path: PHẢI đặt trong:
/config/config.php
18.2 KHÔNG HARDCODE
KHÔNG hardcode:
localhost path
DB credentials
upload path
19. SECURITY RULES
19.1 DATABASE SECURITY
BẮT BUỘC: PDO Prepared Statement
KHÔNG: nối chuỗi SQL trực tiếp
 19.2 PASSWORD SECURITY
Password:
hash bằng password_hash()
Verify:
password_verify()
 19.3 XSS PREVENTION
Output text:
dùng htmlspecialchars()
 19.4 FILE UPLOAD SECURITY
BẮT BUỘC:
validate extension
validate mime type
validate size
20. UI/UX RULES
 20.1 RESPONSIVE BREAKPOINTS Mobile: <768px
Tablet: 768px - 991px
Desktop: >=992px
 20.2 UI CONSISTENCY
Toàn hệ thống phải thống nhất:
button style
border radius
spacing
typography
card design
20.3 EMPTY STATES
Bắt buộc có:
empty cart
no foods
no orders
no search result
 20.4 LOADING STATES
Bắt buộc:
loading button
disable submit khi submit
 20.5 TOAST NOTIFICATION
Thông báo cho:
add cart success
voucher invalid
order success
upload fail
21. PWA RULES
21.1 MANIFEST
manifest.json phải có:
app name
app icon
theme color
start_url
 21.2 SERVICE WORKER
Cache cơ bản:
css
js
images
KHÔNG cần:
advanced offline sync
realtime sync
 21.3 INSTALLABLE APP
YumGO phải:
Add To Home Screen được
chạy như app mobile cơ bản
22. SAMPLE DATA & DEMO
Bắt buộc chuẩn bị:
10 foods
3 categories
2 vouchers
2 shippers
5 sample orders
23. TESTING CHECKLIST
 23.1 USER FLOW Kiểm tra:
add cart
update cart
checkout
apply voucher
tracking order
cancel order
edit order
23.2 ADMIN FLOW
Kiểm tra:
login
CRUD category
CRUD food
update status
cancel order
23.3 SHIPPER FLOW
Kiểm tra:
xem Ready orders
accept delivery
complete delivery
23.4 RESPONSIVE
Kiểm tra:
mobile
tablet
desktop
23.5 PWA ==================================================
Kiểm tra:
Add To Home Screen
install app
splash screen
manifest load

P/S

CART RECALCULATION RULE
Khi checkout:

Backend phải query lại:
food price
food availability
voucher condition

KHÔNG dùng:
frontend total
hidden input total
session subtotal cũ

GUEST ORDER TRACKING RULE

User guest theo dõi đơn bằng:
order_code
phone

KHÔNG triển khai:
user account system
user login/register
SHIPPER AUTH RULE

Shipper có:
login riêng đơn giản bằng phone/password

KHÔNG cần:
JWT
OAuth
multi-role permission system
ROUTING RULE

Sử dụng:
PHP native routing đơn giản
query parameter based

Ví dụ:
index.php?page=food-detail&id=1

KHÔNG dùng:
Laravel
Symfony
advanced router package
Order history của guest:
lưu danh sách order_code trong session/localStorage
24. FINAL TEAM RULES
24.1 KHÔNG ĐƯỢC ==================================================
tự đổi DB schema
redesign shared layout
merge main trực tiếp
push code chưa test
 24.2 BẮT BUỘC ==================================================
pull trước push
commit rõ nghĩa
test responsive
test flow liên quan trước merge
24.3 FINAL RESPONSIBILITY ==================================================
T nhóm:
finalize database
finalize UI consistency
merge cuối
fix integration conflict
chuẩn bị demo cuối
25. MỤC TIÊU CUỐI CÙNG
Xây dựng hệ thống:
giống mini food delivery startup
business flow thực tế
responsive mobile-first
UI hiện đại
dễ demo
ít bug
teamwork tốt
dễ bảo trì
đúng tư duy software engineering

