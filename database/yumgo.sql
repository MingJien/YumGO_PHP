-- YumGO database schema va sample data
-- Luu y: file nay tuan thu dac ta he thong YumGO.

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

-- SAMPLE DATA
INSERT INTO categories (name, is_deleted, created_at, updated_at) VALUESWelcome to YumGO! (Trang chủ đang chờ TV2 dựng)
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
  
  -- THAY ĐỔI Ở ĐÂY: Lưu shipper_id thay vì chỉ lưu text thuần tên/sđt
  shipper_id INT UNSIGNED NULL, 
  
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_orders_order_code (order_code),
  INDEX idx_orders_status (status),
  INDEX idx_orders_created_at (created_at),
  
  -- THÊM KHÓA NGOẠI LIÊN KẾT ĐẾN BẢNG SHIPPERS
  CONSTRAINT fk_orders_shipper
    FOREIGN KEY (shipper_id) REFERENCES shippers(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
