//file config.php
<?php
declare(strict_types=1);

// Cau hinh chung, khong hardcode o noi khac.
define('BASE_URL', 'http://localhost/yumgo');

define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('UPLOAD_FOOD_PATH', UPLOAD_PATH . '/foods');
define('UPLOAD_AVATAR_PATH', UPLOAD_PATH . '/avatars');
define('UPLOAD_BANNER_PATH', UPLOAD_PATH . '/banners');

define('DB_HOST', 'localhost');
define('DB_NAME', 'yumgo');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_TIMEZONE', 'Asia/Ho_Chi_Minh');

define('ADMIN_LOGIN_URL', BASE_URL . '/admin/login.php');
define('ADMIN_DASHBOARD_URL', BASE_URL . '/admin/index.php');
define('SHIPPER_LOGIN_URL', BASE_URL . '/shipper/login.php');
define('SHIPPER_DASHBOARD_URL', BASE_URL . '/shipper/index.php');

date_default_timezone_set(APP_TIMEZONE);
