-- Luu mon vua xem theo tai khoan nguoi dung.
-- Guest van dung localStorage, user da dang nhap dung bang nay de dong bo giua trinh duyet/thiet bi.

CREATE TABLE IF NOT EXISTS user_recent_foods (
  user_id INT UNSIGNED NOT NULL,
  food_id INT UNSIGNED NOT NULL,
  viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, food_id),
  INDEX idx_user_recent_foods_viewed_at (user_id, viewed_at),
  CONSTRAINT fk_user_recent_foods_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_user_recent_foods_food
    FOREIGN KEY (food_id) REFERENCES foods(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
