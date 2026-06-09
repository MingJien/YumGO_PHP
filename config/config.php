<?php
declare(strict_types=1);

// Chặn truy cập trực tiếp (Bảo mật bổ sung)
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// Tự động nạp autoload của Composer và cấu hình Dotenv (nếu có)
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

if (class_exists('Dotenv\Dotenv') && file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
}

// Cấu hình múi giờ hệ thống
define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? 'Asia/Ho_Chi_Minh');
date_default_timezone_set(APP_TIMEZONE);

// Đường dẫn thư mục gốc của dự án
define('PATH_ROOT', dirname(__DIR__));

// Cấu hình Database kết nối PDO từ biến môi trường (fallback về cấu hình mặc định)
// Hỗ trợ Port (nếu có DB_PORT, ta nối vào host thành 'host;port=xxx' để PDO DSN của TV1 nhận dạng chính xác)
$dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
if ($dbPort !== '3306') {
    $dbHost .= ';port=' . $dbPort;
}
define('DB_HOST', $dbHost);
define('DB_NAME', $_ENV['DB_NAME'] ?? 'yumgo');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Cấu hình đường dẫn URL gốc của dự án (Tự động nhận diện động hoặc fallback từ .env)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_dir = rtrim($base_dir, '/');
define('BASE_URL', $_ENV['BASE_URL'] ?? ($protocol . $host . $base_dir));

// Cấu hình đường dẫn vật lý trên server (dùng cho upload ảnh)
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads');
define('UPLOAD_FOOD_PATH', UPLOAD_PATH . '/foods');
define('UPLOAD_AVATAR_PATH', UPLOAD_PATH . '/avatars');
define('UPLOAD_BANNER_PATH', UPLOAD_PATH . '/banners');

// Các đường dẫn URL phân quyền hệ thống của TV1
define('ADMIN_LOGIN_URL', BASE_URL . '/admin/login.php');
define('ADMIN_DASHBOARD_URL', BASE_URL . '/admin/index.php');
define('SHIPPER_LOGIN_URL', BASE_URL . '/shipper/login.php');
define('SHIPPER_DASHBOARD_URL', BASE_URL . '/shipper/index.php');
