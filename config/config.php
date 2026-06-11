<?php
declare(strict_types=1);

// Cau hinh chung, khong hardcode o noi khac.
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost/yumgo';
$host = preg_replace('/[^A-Za-z0-9.\-_:\/]/', '', $host) ?: 'localhost/yumgo';
define('BASE_URL', getenv('YUMGO_BASE_URL') ?: $scheme . '://' . $host);

define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('UPLOAD_FOOD_PATH', UPLOAD_PATH . '/foods');
define('UPLOAD_AVATAR_PATH', UPLOAD_PATH . '/avatars');
define('UPLOAD_BANNER_PATH', UPLOAD_PATH . '/banners');

define('DB_HOST', getenv('YUMGO_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('YUMGO_DB_NAME') ?: 'yumgo');
define('DB_USER', getenv('YUMGO_DB_USER') ?: 'root');
define('DB_PASS', getenv('YUMGO_DB_PASS') ?: '');

define('APP_TIMEZONE', 'Asia/Ho_Chi_Minh');

define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: '');
define('RESTAURANT_ADDRESS', 'Trường Đại học Đồng Tháp, Cao Lãnh, Đồng Tháp, Việt Nam');
define('RESTAURANT_LAT', 10.4207885);
define('RESTAURANT_LNG', 105.6436113);
define('DELIVERY_MAX_DISTANCE_KM', 10);

define('ADMIN_LOGIN_URL', BASE_URL . '/admin/login.php');
define('ADMIN_DASHBOARD_URL', BASE_URL . '/admin/index.php');
define('SHIPPER_LOGIN_URL', BASE_URL . '/shipper/login.php');
define('SHIPPER_DASHBOARD_URL', BASE_URL . '/shipper/index.php');

date_default_timezone_set(APP_TIMEZONE);
