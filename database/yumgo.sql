-- YumGO database schema va sample data
-- Luu y: file nay da duoc sua cac loi cu phap va copy-paste cua TV1.

SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE DATABASE IF NOT EXISTS yumgo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE yumgo;

DROP TABLE IF EXISTS shipper_order_actions;
DROP TABLE IF EXISTS shipper_statuses;
DROP TABLE IF EXISTS user_favorites;
DROP TABLE IF EXISTS user_recent_foods;
DROP TABLE IF EXISTS user_carts;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS foods;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS vouchers;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS shippers;

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
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_shippers_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  address VARCHAR(255) NULL,
  avatar VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_users_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_carts (
  user_id INT UNSIGNED NOT NULL,
  food_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, food_id),
  CONSTRAINT fk_user_carts_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_user_carts_food
    FOREIGN KEY (food_id) REFERENCES foods(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_recent_foods (
  user_id INT UNSIGNED NOT NULL,
  food_id INT UNSIGNED NOT NULL,
  viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, food_id),
  INDEX idx_user_recent_foods_viewed_at (user_id, viewed_at),
  CONSTRAINT fk_user_recent_foods_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_user_recent_foods_food
    FOREIGN KEY (food_id) REFERENCES foods(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_favorites (
  user_id INT UNSIGNED NOT NULL,
  food_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, food_id),
  CONSTRAINT fk_user_favorites_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_user_favorites_food
    FOREIGN KEY (food_id) REFERENCES foods(id)
    ON UPDATE CASCADE ON DELETE CASCADE
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
  delivery_type ENUM('delivery','pickup') NOT NULL DEFAULT 'delivery',
  delivery_status VARCHAR(40) NOT NULL DEFAULT 'unchecked',
  distance_km DECIMAL(8,2) NULL,
  delivery_duration_text VARCHAR(50) NULL,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL,
  voucher_code VARCHAR(50),
  cod_collected TINYINT(1) NOT NULL DEFAULT 0,
  cancel_reason VARCHAR(255) NULL,
  issue_reason VARCHAR(255) NULL,
  issue_note TEXT NULL,
  issue_reported_at DATETIME NULL,
  issue_seen_at DATETIME NULL,
  order_seen_at DATETIME NULL,
  status ENUM(
    'Placed',
    'Preparing',
    'Ready',
    'Delivering',
    'Delivered',
    'Cancelled By User',
    'Cancelled By Admin',
    'Cancelled By Shipper'
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

CREATE TABLE IF NOT EXISTS shipper_statuses (
  shipper_id INT UNSIGNED PRIMARY KEY,
  is_online TINYINT(1) NOT NULL DEFAULT 1,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_shipper_statuses_shipper
    FOREIGN KEY (shipper_id) REFERENCES shippers(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS shipper_order_actions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shipper_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NULL,
  action VARCHAR(30) NOT NULL,
  reason VARCHAR(255) NULL,
  note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_shipper_actions_shipper (shipper_id),
  INDEX idx_shipper_actions_order (order_id),
  CONSTRAINT fk_shipper_actions_shipper
    FOREIGN KEY (shipper_id) REFERENCES shippers(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_shipper_actions_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON UPDATE CASCADE ON DELETE SET NULL
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
-- SAMPLE DATA
-- Image columns store file names only; actual images live in uploads/foods.
-- ==================================================

INSERT INTO categories (id, name, is_deleted) VALUES
(1, 'Món chính', 0),
(2, 'Ăn nhanh', 0),
(3, 'Đồ uống', 0),
(4, 'Món ăn kèm', 0),
(5, 'Combo', 0);

INSERT INTO foods (id, category_id, name, price, image, description, is_hot, is_sale, discount_percent, is_available, is_deleted) VALUES
(1, 2, 'Burger bò cổ điển', 59000.00, 'burger_bo_co_dien.jpg', 'Burger bò sốt đặc biệt.', 1, 0, 0, 1, 0),
(2, 2, 'Burger gà phô mai', 55000.00, 'burger_ga_pho_mai.jpg', 'Burger gà kèm phô mai béo nhẹ.', 0, 1, 10, 1, 0),
(3, 1, 'Cơm gà YumGO', 49000.00, 'com_ga_yumgo.jpg', 'Cơm gà sốt YumGO.', 1, 0, 0, 1, 0),
(4, 1, 'Cơm bò áp chảo', 69000.00, 'com_bo_ap_chao.jpg', 'Cơm bò áp chảo thơm mềm.', 0, 0, 0, 1, 0),
(5, 3, 'Trà chanh', 19000.00, 'tra_chanh.jpg', 'Trà chanh mát lạnh.', 0, 0, 0, 1, 0),
(6, 3, 'Trà sữa trân châu', 29000.00, 'tra_sua_tran_chau.jpg', 'Trà sữa trân châu đường đen.', 1, 1, 15, 1, 0),
(7, 2, 'Burger cá giòn', 52000.00, 'burger_ca_gion.jpg', 'Burger cá chiên giòn.', 0, 0, 0, 1, 0),
(8, 4, 'Cơm chiên trứng', 39000.00, 'com_chien_trung.jpg', 'Cơm chiên trứng nóng giòn.', 0, 0, 0, 1, 0),
(9, 3, 'Nước cam tươi', 25000.00, 'nuoc_cam_tuoi.jpg', 'Nước cam tươi ép trong ngày.', 0, 0, 0, 1, 0),
(10, 5, 'Combo cơm heo nướng', 56000.00, 'combo_com_heo_nuong.jpg', 'Combo cơm heo nướng đậm vị.', 0, 1, 10, 1, 0);

INSERT INTO vouchers (id, code, type, value, min_order, expired_at, is_active) VALUES
(1, 'YUM10', 'percent', 10.00, 50000.00, '2027-12-31 23:59:59', 1),
(2, 'FREESHIP', 'fixed', 15000.00, 80000.00, '2027-12-31 23:59:59', 1);

INSERT INTO admins (id, username, password, display_name, avatar) VALUES
(1, 'admin', '$2y$10$7gYBkSC7WO90bDrh9CCaWuzkkoaobCX.1.oHnW8Ik3if7eJSYtV36', 'YumGO Admin', NULL);

INSERT INTO shippers (id, name, phone, password, avatar, is_active) VALUES
(1, 'Nguyễn Văn Ship', '0900000001', '$2y$10$7gYBkSC7WO90bDrh9CCaWuzkkoaobCX.1.oHnW8Ik3if7eJSYtV36', NULL, 1),
(2, 'Trần Thị Fast', '0900000002', '$2y$10$7gYBkSC7WO90bDrh9CCaWuzkkoaobCX.1.oHnW8Ik3if7eJSYtV36', NULL, 1);

INSERT INTO orders (id, order_code, customer_name, phone, address, note, payment_method, subtotal, shipping_fee, discount_amount, total, voucher_code, status, edit_count, editable_until, shipper_id, created_at) VALUES
(1, 'YG202606070001', 'Minh Anh', '0911111111', '12 Nguyen Hue, Cao Lanh, Dong Thap', 'It cay', 'COD', 108000.00, 10000.00, 10000.00, 108000.00, 'YUM10', 'Placed', 0, DATE_ADD(NOW(), INTERVAL 5 MINUTE), NULL, NOW()),
(2, 'YG202606070002', 'Quoc Bao', '0922222222', '45 Le Loi, Cao Lanh, Dong Thap', '', 'COD', 69000.00, 10000.00, 0.00, 79000.00, NULL, 'Preparing', 0, DATE_ADD(NOW(), INTERVAL 5 MINUTE), NULL, NOW()),
(3, 'YG202606070003', 'Thanh Tam', '0933333333', '88 Hai Ba Trung, Cao Lanh, Dong Thap', '', 'COD', 103000.00, 0.00, 15000.00, 88000.00, 'FREESHIP', 'Ready', 0, DATE_ADD(NOW(), INTERVAL 5 MINUTE), NULL, NOW()),
(4, 'YG202606070004', 'Hoang Long', '0944444444', '9 Cach Mang Thang 8, Cao Lanh, Dong Thap', '', 'COD', 58000.00, 10000.00, 0.00, 68000.00, NULL, 'Delivering', 0, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 1, NOW()),
(5, 'YG202606070005', 'Gia Han', '0955555555', '22 Pham Huu Lau, Cao Lanh, Dong Thap', '', 'COD', 117000.00, 0.00, 0.00, 117000.00, NULL, 'Delivered', 0, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 2, NOW());

INSERT INTO order_items (id, order_id, food_id, quantity, price, subtotal) VALUES
(1, 1, 1, 1, 59000.00, 59000.00),
(2, 1, 3, 1, 49000.00, 49000.00),
(3, 2, 4, 1, 69000.00, 69000.00),
(4, 3, 2, 1, 55000.00, 55000.00),
(5, 3, 5, 1, 19000.00, 19000.00),
(6, 3, 6, 1, 29000.00, 29000.00),
(7, 4, 6, 2, 29000.00, 58000.00),
(8, 5, 1, 1, 59000.00, 59000.00),
(9, 5, 8, 1, 39000.00, 39000.00),
(10, 5, 9, 1, 25000.00, 25000.00);

INSERT INTO shipper_statuses (shipper_id, is_online) VALUES
(1, 1),
(2, 1);

INSERT INTO shipper_order_actions (shipper_id, order_id, action, reason, note, created_at) VALUES
(1, 4, 'accept', NULL, NULL, NOW()),
(2, 5, 'accept', NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 5, 'complete', NULL, 'Khach da nhan hang.', DATE_SUB(NOW(), INTERVAL 1 DAY));
