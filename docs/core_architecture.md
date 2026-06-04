# Core Architecture (TV1)

## Database schema
- categories: id, name, is_deleted, created_at, updated_at
- foods: id, category_id, name, price, image, description, is_hot, is_sale, discount_percent, is_available, is_deleted, created_at, updated_at
- vouchers: id, code, type, value, min_order, expired_at, is_active, created_at
- orders: id, order_code, customer_name, phone, address, note, payment_method, subtotal, shipping_fee, discount_amount, total, voucher_code, status, edit_count, editable_until, shipper_id, created_at, updated_at
- order_items: id, order_id, food_id, quantity, price, subtotal
- admins: id, username, password, display_name, avatar, created_at
- shippers: id, name, phone, password, avatar, is_active, created_at

## Foreign keys
- foods.category_id -> categories.id
- order_items.order_id -> orders.id
- order_items.food_id -> foods.id
- orders.shipper_id -> shippers.id

## Session structure
- $_SESSION['cart'] = [food_id => quantity]
- $_SESSION['role'] = 'admin' | 'shipper'
- $_SESSION['user'] = thong tin nguoi dang nhap

## Auth flow
- loginAdmin() va loginShipper() dung password_verify()
- Khi dang nhap thanh cong, set $_SESSION['role'] va $_SESSION['user']
- requireAdminLogin() va requireShipperLogin() bat buoc role dung
- redirectIfLoggedIn() chuyen huong khi da login
- logout() huy session va redirect

## Config structure
- BASE_URL
- UPLOAD_PATH, UPLOAD_FOOD_PATH, UPLOAD_AVATAR_PATH, UPLOAD_BANNER_PATH
- DB_HOST, DB_NAME, DB_USER, DB_PASS
- APP_TIMEZONE
- ADMIN_LOGIN_URL, ADMIN_DASHBOARD_URL
- SHIPPER_LOGIN_URL, SHIPPER_DASHBOARD_URL

## Naming convention
- Database: snake_case
- PHP variables: camelCase
- CSS: kebab-case
