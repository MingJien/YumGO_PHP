-- YumGO database schema va sample data
-- Default demo accounts:
-- Admin: admin / password
-- Shipper: 0900000001 / password, 0900000002 / password

SET NAMES utf8mb4;
SET time_zone = '+07:00';

CREATE DATABASE IF NOT EXISTS yumgo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE yumgo;

DROP TABLE IF EXISTS shipper_order_actions;
DROP TABLE IF EXISTS shipper_statuses;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS foods;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS vouchers;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS shippers;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE foods (
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
  CONSTRAINT fk_foods_category FOREIGN KEY (category_id) REFERENCES categories(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_foods_name (name)
) ENGINE=InnoDB;

CREATE TABLE vouchers (
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

CREATE TABLE shippers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_shippers_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE orders (
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
  issue_reason VARCHAR(255) NULL,
  issue_note TEXT NULL,
  issue_reported_at DATETIME NULL,
  status ENUM('Placed','Preparing','Ready','Delivering','Delivered','Cancelled By User','Cancelled By Admin','Cancelled By Shipper') NOT NULL DEFAULT 'Placed',
  edit_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  editable_until DATETIME NULL,
  shipper_id INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_orders_order_code (order_code),
  INDEX idx_orders_status (status),
  INDEX idx_orders_created_at (created_at),
  CONSTRAINT fk_orders_shipper FOREIGN KEY (shipper_id) REFERENCES shippers(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  food_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_order_items_food FOREIGN KEY (food_id) REFERENCES foods(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE shipper_statuses (
  shipper_id INT UNSIGNED PRIMARY KEY,
  is_online TINYINT(1) NOT NULL DEFAULT 1,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_shipper_statuses_shipper FOREIGN KEY (shipper_id) REFERENCES shippers(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE shipper_order_actions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  shipper_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NULL,
  action VARCHAR(30) NOT NULL,
  reason VARCHAR(255) NULL,
  note TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_shipper_actions_shipper (shipper_id),
  INDEX idx_shipper_actions_order (order_id),
  CONSTRAINT fk_shipper_actions_shipper FOREIGN KEY (shipper_id) REFERENCES shippers(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_shipper_actions_order FOREIGN KEY (order_id) REFERENCES orders(id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  display_name VARCHAR(100) NOT NULL,
  avatar VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_admins_username (username)
) ENGINE=InnoDB;

INSERT INTO categories (name) VALUES
('Bánh mì kẹp'),
('Cơm phần'),
('Đồ uống');

INSERT INTO foods (category_id, name, price, image, description, is_hot, is_sale, discount_percent, is_available) VALUES
(1, 'Burger bò cổ điển', 59000, 'burger.jpg', 'Burger bò sốt đặc biệt.', 1, 0, 0, 1),
(1, 'Burger gà phô mai', 55000, 'chicken-burger.jpg', 'Burger gà kèm phô mai béo nhẹ.', 0, 1, 10, 1),
(2, 'Cơm gà YumGO', 49000, 'chicken-rice.jpg', 'Cơm gà sốt YumGO.', 1, 0, 0, 1),
(2, 'Cơm bò áp chảo', 69000, 'beef-rice.jpg', 'Cơm bò áp chảo thơm mềm.', 0, 0, 0, 1),
(3, 'Trà chanh', 19000, 'lemon-tea.jpg', 'Trà chanh mát lạnh.', 0, 0, 0, 1),
(3, 'Trà sữa trân châu', 29000, 'milk-tea.jpg', 'Trà sữa trân châu đường đen.', 1, 1, 15, 1),
(1, 'Burger cá giòn', 52000, 'fish-burger.jpg', 'Burger cá chiên giòn.', 0, 0, 0, 1),
(2, 'Cơm chiên trứng', 39000, 'fried-rice.jpg', 'Cơm chiên trứng nóng giòn.', 0, 0, 0, 1),
(3, 'Nước cam tươi', 25000, 'orange-juice.jpg', 'Nước cam tươi ép trong ngày.', 0, 0, 0, 1),
(2, 'Cơm heo nướng', 56000, 'pork-rice.jpg', 'Cơm heo nướng đậm vị.', 0, 1, 10, 1);

INSERT INTO vouchers (code, type, value, min_order, expired_at, is_active) VALUES
('YUM10', 'percent', 10, 50000, '2027-12-31 23:59:59', 1),
('FREESHIP', 'fixed', 15000, 80000, '2027-12-31 23:59:59', 1);

INSERT INTO admins (username, password, display_name, avatar) VALUES
('admin', '$2y$10$7gYBkSC7WO90bDrh9CCaWuzkkoaobCX.1.oHnW8Ik3if7eJSYtV36', 'YumGO Admin', NULL);

INSERT INTO shippers (name, phone, password, avatar, is_active) VALUES
('Nguyễn Văn Ship', '0900000001', '$2y$10$7gYBkSC7WO90bDrh9CCaWuzkkoaobCX.1.oHnW8Ik3if7eJSYtV36', NULL, 1),
('Trần Thị Fast', '0900000002', '$2y$10$7gYBkSC7WO90bDrh9CCaWuzkkoaobCX.1.oHnW8Ik3if7eJSYtV36', NULL, 1);

INSERT INTO orders (order_code, customer_name, phone, address, note, payment_method, subtotal, shipping_fee, discount_amount, total, voucher_code, status, editable_until, shipper_id) VALUES
('YG202606070001', 'Minh Anh', '0911111111', '12 Nguyễn Huệ, Cao Lãnh, Đồng Tháp', 'Ít cay', 'COD', 108000, 10000, 10000, 108000, 'YUM10', 'Placed', DATE_ADD(NOW(), INTERVAL 5 MINUTE), NULL),
('YG202606070002', 'Quốc Bảo', '0922222222', '45 Lê Lợi, Cao Lãnh, Đồng Tháp', '', 'COD', 69000, 10000, 0, 79000, NULL, 'Preparing', DATE_ADD(NOW(), INTERVAL 5 MINUTE), NULL),
('YG202606070003', 'Thanh Tâm', '0933333333', '88 Hai Bà Trưng, Cao Lãnh, Đồng Tháp', '', 'COD', 103000, 0, 15000, 88000, 'FREESHIP', 'Ready', DATE_ADD(NOW(), INTERVAL 5 MINUTE), NULL),
('YG202606070004', 'Hoàng Long', '0944444444', '9 Cách Mạng Tháng 8, Cao Lãnh, Đồng Tháp', '', 'COD', 58000, 10000, 0, 68000, NULL, 'Delivering', DATE_ADD(NOW(), INTERVAL 5 MINUTE), 1),
('YG202606070005', 'Gia Hân', '0955555555', '22 Phạm Hữu Lầu, Cao Lãnh, Đồng Tháp', '', 'COD', 117000, 0, 0, 117000, NULL, 'Delivered', DATE_ADD(NOW(), INTERVAL 5 MINUTE), 2);

INSERT INTO order_items (order_id, food_id, quantity, price, subtotal) VALUES
(1, 1, 1, 59000, 59000),
(1, 3, 1, 49000, 49000),
(2, 4, 1, 69000, 69000),
(3, 2, 1, 55000, 55000),
(3, 5, 1, 19000, 19000),
(3, 6, 1, 29000, 29000),
(4, 6, 2, 29000, 58000),
(5, 1, 1, 59000, 59000),
(5, 8, 1, 39000, 39000),
(5, 9, 1, 25000, 25000);

INSERT INTO shipper_statuses (shipper_id, is_online) VALUES
(1, 1),
(2, 1);

INSERT INTO shipper_order_actions (shipper_id, order_id, action, reason, note, created_at) VALUES
(1, 4, 'accept', NULL, NULL, NOW()),
(2, 5, 'accept', NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 5, 'complete', NULL, 'Khách đã nhận hàng.', DATE_SUB(NOW(), INTERVAL 1 DAY));
