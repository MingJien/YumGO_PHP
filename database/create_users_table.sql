-- Tao bang tai khoan khach hang cho database da import truoc do.
-- Chay file nay neu database hien tai chua co bang users.

SET NAMES utf8mb4;
SET time_zone = '+07:00';

USE yumgo;

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
