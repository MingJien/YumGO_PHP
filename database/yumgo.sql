-- YumGO database schema va sample data
-- Luu y: file nay da duoc sua cac loi cu phap va copy-paste cua TV1.

SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE DATABASE IF NOT EXISTS yumgo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE yumgo;

-- BUSINESS RULES (bat buoc):
-- 1) Soft Delete Rule: categories.is_deleted va foods.is_deleted. Khong delete that.
-- 2) Food Availability Rule: is_available = 0 thi khong add cart va khong checkout.
-- 3) Price Snapshot Rule: order_items.price luu gia tai thoi diem dat hang.
-- 4) Status Transition Rule: Placed -> Preparing -> Ready -> Delivering -> Delivered.
-- 5) Final Status Rule: Delivered hoac Cancelled (By User/Admin) la ket thuc, khong duoc update tiep.

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS foods (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255) NOT NULL,
  description TEXT,
  is_hot TINYINT(1) NOT NULL DEFAULT 0,
  is_sale TINYINT(1) NOT NULL DEFAULT 0,
  discount_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_foods_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_foods_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vouchers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL,
  type ENUM('percent', 'fixed') NOT NULL,
  value DECIMAL(10,2) NOT NULL,
  min_order DECIMAL(10,2) NOT NULL DEFAULT 0,
  expired_at DATETIME NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_vouchers_code (code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS shippers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_shippers_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_code VARCHAR(30) NOT NULL,
  customer_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address VARCHAR(255) NOT NULL,
  note TEXT,
  payment_method VARCHAR(50) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  shipping_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL,
  voucher_code VARCHAR(50),
  status ENUM(
    'Placed',
    'Preparing',
    'Ready',
    'Delivering',
    'Delivered',
    'Cancelled By User',
    'Cancelled By Admin'
  ) NOT NULL DEFAULT 'Placed',
  edit_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  editable_until DATETIME NULL,
  shipper_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_orders_order_code (order_code),
  INDEX idx_orders_status (status),
  INDEX idx_orders_created_at (created_at),
  CONSTRAINT fk_orders_shipper
    FOREIGN KEY (shipper_id) REFERENCES shippers(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  food_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_order_items_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_order_items_food
    FOREIGN KEY (food_id) REFERENCES foods(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  display_name VARCHAR(100) NOT NULL,
  avatar VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_admins_username (username)
) ENGINE=InnoDB;

-- ==================================================
-- SAMPLE DATA (Yêu cầu: 3 categories, 10 foods, 2 vouchers, 2 shippers, 5 orders, 1 admin)
-- ==================================================

-- 1. Thêm danh mục mẫu (categories)
INSERT INTO categories (id, name, is_deleted) VALUES
(1, 'Gà Rán', 0),
(2, 'Pizza', 0),
(3, 'Trà Sữa', 0),
(4, 'Mì Ý', 0);

-- 2. Thêm món ăn mẫu (foods)
INSERT INTO foods (id, category_id, name, price, image, description, is_hot, is_sale, discount_percent, is_available, is_deleted) VALUES
(1, 1, 'Gà Rán Giòn Cay', 35000.00, 'ga_ran_gion.jpg', 'Gà chiên giòn rụm vị cay nồng đặc trưng, kèm nước sốt.', 1, 0, 0, 1, 0),
(2, 1, 'Gà Rán Sốt Hàn Quốc', 39000.00, 'ga_sot_han.jpg', 'Gà rán phủ sốt cay ngọt kiểu Hàn, rắc mè rang thơm phức.', 0, 1, 10, 1, 0),
(3, 2, 'Pizza Thập Cẩm Lớn', 120000.00, 'pizza_thap_cam.jpg', 'Pizza đầy đủ topping thịt nguội, xúc xích, ớt chuông và phô mai mozzarella.', 1, 1, 15, 1, 0),
(4, 2, 'Pizza Hải Sản Pesto', 135000.00, 'pizza_hai_san.jpg', 'Tôm, mực tươi ngon trên nền sốt pesto xanh đặc trưng.', 0, 0, 0, 0, 0), -- Trạng thái: Hết hàng
(5, 3, 'Trà Sữa Trân Châu Hoàng Gia', 30000.00, 'tra_sua_tc.jpg', 'Trà sữa truyền thống đậm vị trà kèm trân châu đen dai giòn.', 0, 0, 0, 1, 0),
(6, 3, 'Sữa Tươi Trân Châu Đường Đen', 35000.00, 'sua_tuoi_dd.jpg', 'Sữa tươi thanh trùng kết hợp đường đen Hàn Quốc ngọt thanh.', 1, 0, 0, 1, 0),
(7, 4, 'Mì Ý Sốt Bò Bằm', 55000.00, 'mi_y_bo_bam.jpg', 'Mì Ý truyền thống kết hợp sốt bò bằm đậm đà.', 1, 0, 0, 1, 0),
(8, 4, 'Mì Ý Sốt Kem Nấm', 60000.00, 'mi_y_sot_kem.jpg', 'Sợi mì mềm dai quyện trong sốt kem nấm béo ngậy.', 0, 1, 5, 1, 0),
(9, 1, 'Khoai Tây Chiên Lắc Phô Mai', 25000.00, 'khoai_tay_phomai.jpg', 'Khoai tây chiên giòn tan lắc kèm bột phô mai mặn ngọt.', 0, 0, 0, 1, 0),
(10, 3, 'Trà Đào Sả Tắc', 28000.00, 'tra_dao_sa_tac.jpg', 'Trà đào thanh mát hương sả tắc giải nhiệt mùa hè cực tốt.', 0, 0, 0, 1, 0);

-- 3. Thêm mã giảm giá mẫu (vouchers)
INSERT INTO vouchers (id, code, type, value, min_order, expired_at, is_active) VALUES
(1, 'YUMGO50', 'percent', 10.00, 100000.00, '2030-12-31 23:59:59', 1),
(2, 'FREESHIP', 'fixed', 15000.00, 50000.00, '2030-12-31 23:59:59', 1);

-- 4. Thêm tài khoản Shipper (mật khẩu mặc định là: admin123)
INSERT INTO shippers (id, name, phone, password, avatar, is_active) VALUES
(1, 'Nguyễn Văn A', '0987654321', '$2y$10$GGY9DIF6BS6sPu3xu2V.dOYmHO2f9xPJ8JlwqpFJVUvgAi9Iwp/JS', 'shipper_a.jpg', 1),
(2, 'Trần Văn B', '0912345678', '$2y$10$GGY9DIF6BS6sPu3xu2V.dOYmHO2f9xPJ8JlwqpFJVUvgAi9Iwp/JS', 'shipper_b.jpg', 1);

-- 5. Thêm tài khoản Admin (mật khẩu mặc định là: admin123)
INSERT INTO admins (id, username, password, display_name, avatar) VALUES
(1, 'admin', '$2y$10$GGY9DIF6BS6sPu3xu2V.dOYmHO2f9xPJ8JlwqpFJVUvgAi9Iwp/JS', 'Quản trị viên YumGO', 'admin_avatar.png');

-- 6. Thêm đơn hàng mẫu (orders) để demo phân hệ
INSERT INTO orders (id, order_code, customer_name, phone, address, note, payment_method, subtotal, shipping_fee, discount_amount, total, voucher_code, status, edit_count, editable_until, shipper_id, created_at) VALUES
(1, 'YUMGO-20260607-001', 'Khách hàng 1', '0909123456', '123 Đường ABC, Quận 1', 'Giao giờ hành chính', 'COD', 70000.00, 15000.00, 0.00, 85000.00, NULL, 'Placed', 0, '2026-06-07 12:00:00', NULL, '2026-06-07 07:00:00'),
(2, 'YUMGO-20260607-002', 'Khách hàng 2', '0909789012', '456 Đường XYZ, Quận 3', 'Không hành', 'Banking', 120000.00, 15000.00, 12000.00, 123000.00, 'YUMGO50', 'Preparing', 0, '2026-06-07 12:05:00', NULL, '2026-06-07 07:05:00'),
(3, 'YUMGO-20260607-003', 'Khách hàng 3', '0911222333', '789 Đường LMN, Quận 5', 'Đồ ăn cay nhiều', 'COD', 55000.00, 15000.00, 15000.00, 55000.00, 'FREESHIP', 'Ready', 0, '2026-06-07 12:10:00', NULL, '2026-06-07 07:10:00'),
(4, 'YUMGO-20260607-004', 'Khách hàng 4', '0922333444', '321 Đường GHI, Quận 10', NULL, 'COD', 109000.00, 15000.00, 10900.00, 113100.00, 'YUMGO50', 'Delivering', 0, '2026-06-07 12:15:00', 1, '2026-06-07 07:15:00'),
(5, 'YUMGO-20260607-005', 'Khách hàng 5', '0933444555', '654 Đường DEF, Bình Thạnh', 'Gọi trước khi giao', 'Banking', 30000.00, 15000.00, 0.00, 45000.00, NULL, 'Delivered', 0, '2026-06-07 12:20:00', 2, '2026-06-07 07:20:00');

-- 7. Chi tiết các đơn hàng mẫu (order_items)
INSERT INTO order_items (id, order_id, food_id, quantity, price, subtotal) VALUES
-- Đơn hàng 1: 2 Gà Rán Giòn Cay (35k/món)
(1, 1, 1, 2, 35000.00, 70000.00),
-- Đơn hàng 2: 1 Pizza Thập Cẩm Lớn (120k/món)
(2, 2, 3, 1, 120000.00, 120000.00),
-- Đơn hàng 3: 1 Mì Ý Sốt Bò Bằm (55k/món)
(3, 3, 7, 1, 55000.00, 55000.00),
-- Đơn hàng 4: 2 Gà Rán Sốt Hàn Quốc (35.1k/món đã giảm 10%), 1 Trà Sữa TC (30k/món)
(4, 4, 2, 2, 35100.00, 70200.00),
(5, 4, 5, 1, 30000.00, 30000.00),
-- Đơn hàng 5: 1 Trà Sữa Trân Châu Hoàng Gia (30k/món)
(6, 5, 5, 1, 30000.00, 30000.00);
