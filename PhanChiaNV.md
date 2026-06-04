PHÂN CHIA NHIỆM VỤ PHP YUMGO
NGUYÊN TẮC CHUNG
Dự án YumGO được chia theo hướng:
mỗi thành viên phụ trách một cụm chức năng hoàn chỉnh
tự triển khai cả frontend + backend cho phần mình
hạn chế phụ thuộc lẫn nhau
hạn chế conflict khi merge Git
dễ test và dễ debug
Toàn bộ thành viên PHẢI tuân thủ:
database schema đã chốt, nếu có cỉa thiện hay phát triển nhớ báo nhau 
folder structure đã chốt
naming convention đã chốt
business rules trong bản đặc tả
status flow đã quy định
KHÔNG tự ý:
đổi tên bảng
đổi tên cột
đổi status
đổi folder structure
đổi route naming
redesign layout chung mà không thống nhất
THÀNH VIÊN 1 — CORE SYSTEM + DATABASE + AUTH+ UI, TEST,FIX + MERGE FINAL  
==================================================
Vai trò
TV1 là người xây nền tảng hệ thống cho toàn team.
TV1 KHÔNG phụ trách business module lớn.
Nhiệm vụ chính là chuẩn hóa hệ thống để các thành viên còn lại có thể phát triển độc lập.
hoàn thiện UI cuối cùng của toàn hệ thống ,là người finalize sản phẩm trước demo.


NHIỆM VỤ CHÍNH

1. Thiết kế và triển khai database
Bao gồm đầy đủ:
categories
foods
vouchers
orders
order_items
admins
shippers
BẮT BUỘC:
foreign key
index
data type chuẩn
DECIMAL cho tiền
timestamps
soft delete
status constraint

2. Tạo file SQL chuẩn
Tạo file:
/database/yumgo.sql
Bao gồm:
CREATE TABLE
foreign key
indexes
sample data

3. Config hệ thống
Tạo:
/config/config.php
Bao gồm:
DB config
BASE_URL
upload path
constants

4. Database connection
Tạo:
/includes/database.php
Yêu cầu:
PDO
utf8mb4
exception mode

5. Authentication core
Triển khai:
admin login
logout
session check
auth middleware
Ví dụ:
requireAdminLogin();

6. Shared core system
Các helper dùng chung:
redirect()
sanitizeInput()
session helper
upload helper
7. Final UI consistency
Sau khi merge toàn bộ project sẽ finalize:
spacing
typography
button style
card design
responsive consistency
loading state
toast notification
màu sắc toàn hệ thống

THÀNH VIÊN 2 — USER WEBSITE (PHẦN USER FLOW)
==================================================
Vai trò
TV2 phụ trách phần lớn giao diện và trải nghiệm người dùng phía khách hàng.
TV2 tự làm FULLSTACK cho module mình.
Bao gồm:
frontend
backend
query
validation
responsive UI

NHIỆM VỤ CHÍNH

1. Homepage
Bao gồm:
banner
categories
featured foods
responsive UI

2. Food listing
Bao gồm:
hiển thị danh sách món ăn
search món ăn
filter category
pagination
HOT badge
SALE badge

3. Food detail
Bao gồm:
image
description
price
add to cart

4. Cart system
Bao gồm:
add cart
remove item
update quantity
subtotal
Dùng:
PHP Session

5. Responsive mobile UI
Bao gồm:
mobile navbar
bottom navigation
responsive spacing

6. Empty states
Bao gồm:
no foods
empty cart
no search result

RÀNG BUỘC BẮT BUỘC

KHÔNG được:
trust frontend price
trust frontend subtotal
Backend PHẢI:
recalculation
validate quantity
validate availability
THÀNH VIÊN 3 — CHECKOUT + ORDER + VOUCHER
==================================================
Vai trò
TV3 phụ trách toàn bộ business flow chính của hệ thống phía user.
TV3 tự triển khai FULLSTACK cho module mình.
Bao gồm:
frontend
backend
validation
business logic
query

NHIỆM VỤ CHÍNH

1. Checkout system
Bao gồm:
form checkout
validate input
create order
create order_items

2. Voucher system
Bao gồm:
validate voucher
apply voucher
calculate discount

3. Order tracking
Hiển thị:
Placed
Preparing
Ready
Delivering
Delivered

4. Order history
Hiển thị:
order code
total
created_at
final status

5. Edit order
Rule:
tối đa 2 lần
trong 5 phút
chỉ status = Placed

6. Cancel order
User chỉ được cancel khi:
Placed
Preparing

7. Export PDF invoice
Dùng:
DomPDF

8. Checkout transaction
BẮT BUỘC dùng:
BEGIN TRANSACTION
COMMIT
ROLLBACK

BUSINESS RULES BẮT BUỘC

PRICE SNAPSHOT RULE
order_items.price PHẢI lưu:
giá tại thời điểm đặt hàng
Nếu admin đổi giá món sau đó:
đơn hàng cũ KHÔNG được đổi giá

FINAL STATUS RULE
Sau khi:
Delivered
Cancelled
KHÔNG được update tiếp.
THÀNH VIÊN 4 — ADMIN + SHIPPER 
==================================================
Vai trò
TV4 phụ trách:
admin system
shipper system

NHIỆM VỤ CHÍNH

1. Dashboard
Bao gồm:
total orders
total revenue
best seller

2. Category CRUD
Bao gồm:
add
edit
soft delete
restore

3. Food CRUD
Bao gồm:
upload image
HOT
SALE
available

4. Voucher CRUD
Bao gồm:
create
update
disable

5. Order management
Bao gồm:
view detail
update status
cancel order
PHẢI validate:
status transition

6. Shipper management
Bao gồm:
create
update
active/inactive

7. Delivery Board
Chỉ hiển thị:
status = Ready

8. Shipper flow
Bao gồm:
accept delivery
complete delivery

==================================================
GIT WORKFLOW
==================================================
Branches
main
dev
feature/member1-core
feature/member2-user
feature/member3-order
feature/member4-admin

QUY TẮC GIT

KHÔNG:
push trực tiếp main
merge chưa test
tự ý đổi structure
BẮT BUỘC:
pull trước push
test local trước merge
commit message rõ nghĩa
Ví dụ:
feat: add voucher validation
fix: resolve cart subtotal bug
style: improve responsive navbar
==================================================
QUY TRÌNH LÀM VIỆC
==================================================
GIAI ĐOẠN 1 — FINALIZE NỀN TẢNG
TV1 hoàn thành:
database
config
auth core
folder structure
BẮT BUỘC xong trước khi build chức năng.

GIAI ĐOẠN 2 — BUILD MODULE RIÊNG
TV2, TV3, TV4 bắt đầu build module riêng.
Mỗi người:
tự build frontend
tự build backend
tự validate
tự responsive
KHÔNG cần chờ nhau.

GIAI ĐOẠN 3 — INTEGRATION
Merge:
feature → dev
Kiểm tra:
routing
DB compatibility
responsive
UI consistency

GIAI ĐOẠN 4 — FINALIZE
TV4 finalize:
UI consistency
responsive
loading states
spacing
màu sắc
animation nhẹ

GIAI ĐOẠN 5 — DEMO PREPARATION
Chuẩn bị:
sample data
sample orders
test cases
responsive demo
PWA install demo
==================================================
LƯU Ý QUAN TRỌNG
==================================================
Khi dùng AI generate code:
PHẢI luôn cung cấp:
database schema
folder structure
naming convention
business rules
status flow
validation rules
KHÔNG cho AI tự quyết định:
database structure
order status
voucher logic
auth flow
folder structure
AI chỉ được build theo đặc tả đã chốt.