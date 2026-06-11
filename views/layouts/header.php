<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$headerRole = $_SESSION['role'] ?? null;
$headerUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : [];
$headerIsCustomer = $headerRole === 'user';
$headerIsGuest = $headerRole === null;
$headerUserName = $headerIsCustomer ? (string)($headerUser['name'] ?? 'Khách hàng') : '';

$headerCartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $headerCartCount += (int)$item['quantity'];
    }
}

$drawerCartItems = [];
$drawerSubtotal = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    global $pdo;
    require_once dirname(dirname(__DIR__)) . '/models/Food.php';
    $headerFoodModel = new Food($pdo);

    foreach ($_SESSION['cart'] as $foodId => $item) {
        $headerCartFood = $headerFoodModel->getById((int)$foodId);
        if ($headerCartFood && $headerCartFood['is_available']) {
            $finalPrice = $headerCartFood['price'];
            if ($headerCartFood['is_sale']) {
                $finalPrice = $headerCartFood['price'] * (1 - $headerCartFood['discount_percent'] / 100);
            }
            $itemTotal = $finalPrice * $item['quantity'];
            $drawerSubtotal += $itemTotal;
            $drawerCartItems[] = [
                'food_id' => $headerCartFood['id'],
                'name' => $headerCartFood['name'],
                'image' => $headerCartFood['image'],
                'final_price' => $finalPrice,
                'quantity' => $item['quantity'],
                'item_total' => $itemTotal,
            ];
        }
    }
}

$currentPage = isset($_GET['page']) ? trim($_GET['page']) : 'home';
$metaDescription = $metaDescription ?? 'YumGO - đặt món ăn nhanh, nóng và dễ dùng.';
$metaKeywords = $metaKeywords ?? 'YumGO, đặt món ăn, giao đồ ăn, thức ăn nhanh';
$canonicalUrl = $canonicalUrl ?? (defined('BASE_URL') ? BASE_URL . '/index.php?page=' . urlencode($currentPage) : '');
$jsonLd = $jsonLd ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($metaKeywords); ?>">
    <meta name="theme-color" content="#FF6600">
    <meta name="application-name" content="YumGO">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="YumGO">
    <title><?php echo isset($title) ? htmlspecialchars($title) : 'YumGO - Đặt món ăn giao ngay'; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Noto+Serif:wght@500;600;700&display=swap&subset=vietnamese" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <?php if (!empty($canonicalUrl)): ?>
        <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">
    <?php endif; ?>
    <link rel="apple-touch-icon" href="uploads/banners/hero_combo.png">
    <link href="assets/css/user.css?v=20260608-account1" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <?php if (!empty($jsonLd)): ?>
        <script type="application/ld+json"><?php echo json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
    <?php endif; ?>
</head>
<body
    x-data="{ showCart: false, showMenu: false, showAccount: false }"
    @keydown.escape="showCart = false; showMenu = false; showAccount = false"
    class="position-relative"
    data-auth-state="<?php echo $headerIsCustomer ? 'user' : 'guest'; ?>"
>
    <div id="toast-container"></div>

    <nav class="site-nav">
        <div class="site-nav-inner">
            <button class="site-icon-btn d-lg-none" @click="showMenu = true" aria-label="Mở menu">
                <i class="bi bi-list"></i>
            </button>

            <a class="site-brand" href="index.php?page=home" aria-label="YumGO trang chủ">
                <strong><span>Yum</span>GO</strong>
            </a>

            <div class="site-links d-none d-lg-flex">
                <a class="<?php echo $currentPage === 'home' ? 'active' : ''; ?>" href="index.php?page=home">Trang chủ</a>
                <a class="<?php echo $currentPage === 'foods' ? 'active' : ''; ?>" href="index.php?page=foods">Thực đơn</a>
                <a class="<?php echo $currentPage === 'favorites' ? 'active' : ''; ?>" href="index.php?page=favorites">Yêu thích</a>
                <a class="<?php echo $currentPage === 'support' ? 'active' : ''; ?>" href="index.php?page=support">Hỗ trợ</a>
            </div>

            <div class="site-actions">
                <a class="site-search-pill d-none d-sm-inline-flex" href="index.php?page=foods">
                    <span>Tìm món</span>
                    <i class="bi bi-search"></i>
                </a>
                <div class="site-account-menu d-none d-md-inline-flex" @click.outside="showAccount = false">
                    <button type="button" class="site-account-trigger <?php echo in_array($currentPage, ['login', 'register', 'account', 'order-search', 'order-history', 'order-tracking'], true) ? 'active' : ''; ?>" @click="showAccount = !showAccount" :aria-expanded="showAccount.toString()" aria-haspopup="true">
                        <i class="bi <?php echo $headerIsCustomer ? 'bi-person-check' : 'bi-person'; ?>"></i>
                        <span><?php echo $headerIsCustomer ? htmlspecialchars($headerUserName) : 'Tài khoản'; ?></span>
                        <i class="bi bi-chevron-down account-chevron"></i>
                    </button>
                    <div class="site-account-dropdown" x-cloak x-show="showAccount" x-transition>
                        <?php if ($headerIsGuest): ?>
                            <a href="index.php?page=login" class="<?php echo $currentPage === 'login' ? 'active' : ''; ?>">
                                <i class="bi bi-box-arrow-in-right"></i>
                                Đăng nhập
                            </a>
                            <a href="index.php?page=register" class="<?php echo $currentPage === 'register' ? 'active' : ''; ?>">
                                <i class="bi bi-person-plus"></i>
                                Đăng ký
                            </a>
                            <a href="index.php?page=order-search" class="<?php echo in_array($currentPage, ['order-search', 'order-tracking'], true) ? 'active' : ''; ?>">
                                <i class="bi bi-receipt"></i>
                                Tra cứu đơn hàng
                            </a>
                        <?php else: ?>
                            <a href="index.php?page=account" class="<?php echo $currentPage === 'account' ? 'active' : ''; ?>">
                                <i class="bi bi-person-circle"></i>
                                Tài khoản
                            </a>
                            <a href="index.php?page=favorites" class="<?php echo $currentPage === 'favorites' ? 'active' : ''; ?>">
                                <i class="bi bi-heart"></i>
                                Yêu thích
                            </a>
                            <a href="index.php?page=order-history" class="<?php echo in_array($currentPage, ['order-history', 'order-tracking'], true) ? 'active' : ''; ?>">
                                <i class="bi bi-receipt"></i>
                                Đơn hàng
                            </a>
                            <a href="index.php?page=logout">
                                <i class="bi bi-box-arrow-right"></i>
                                Đăng xuất
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="button" class="site-icon-btn theme-toggle-btn" id="themeToggleBtn" aria-label="Chuyển chế độ sáng tối">
                    <i class="bi bi-moon-stars"></i>
                </button>
                <a href="index.php?page=cart" @click.prevent="showCart = true" class="site-icon-btn position-relative" id="headerCartBtn" aria-label="Mở giỏ hàng">
                    <i class="bi bi-bag"></i>
                    <?php if ($headerCartCount > 0): ?>
                        <span class="site-cart-badge badge"><?php echo $headerCartCount; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="drawer-overlay" :class="showCart ? 'show' : ''" @click="showCart = false"></div>
    <div class="drawer-cart" :class="showCart ? 'show' : ''" x-cloak>
        <div class="drawer-header">
            <h5 class="m-0 fw-bold d-flex align-items-center"><i class="bi bi-bag me-2 text-primary"></i>Giỏ hàng của bạn</h5>
            <button type="button" class="border-0 bg-transparent p-0 d-flex align-items-center justify-content-center" @click="showCart = false" aria-label="Đóng" style="color: var(--yumgo-ink); width: 32px; height: 32px;">
                <i class="bi bi-x-lg fs-4"></i>
            </button>
        </div>

        <div class="drawer-body">
            <div x-show="<?php echo empty($drawerCartItems) ? 'true' : 'false'; ?>" class="drawer-empty-state text-center py-5">
                <div class="fs-1 mb-3" style="opacity: 0.5;"><i class="bi bi-bag"></i></div>
                <h6 class="fw-bold text-dark">Giỏ hàng đang trống</h6>
                <p class="body-md text-secondary">Duyệt thực đơn và chọn các món đang nóng hổi tại YumGO.</p>
                <a href="index.php?page=foods" @click="showCart = false" class="btn btn-primary-yumgo rounded-pill px-4 py-2 mt-2">Duyệt món ngay</a>
            </div>

            <div x-show="<?php echo !empty($drawerCartItems) ? 'true' : 'false'; ?>" class="d-flex flex-column gap-3" id="drawerCartList">
                <?php foreach ($drawerCartItems as $item): ?>
                    <div class="d-flex align-items-center gap-3 py-2 border-bottom" data-food-id="<?php echo (int)$item['food_id']; ?>">
                        <div class="rounded-md overflow-hidden flex-shrink-0" style="width: 50px; height: 50px; border: 1px solid var(--yumgo-hairline);">
                            <?php
                            $imgFile = !empty($item['image']) ? $item['image'] : '';
                            $imgExists = !empty($imgFile) && file_exists(PATH_ROOT . '/uploads/foods/' . $imgFile);
                            ?>
                            <?php if ($imgExists): ?>
                                <img src="uploads/foods/<?php echo htmlspecialchars($imgFile); ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-warning-subtle text-warning fw-bold">Y</div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <h6 class="title-md text-truncate m-0 text-dark" style="font-size: 14px;"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <span class="price-display text-primary body-md" style="font-size: 13px; font-weight: 700;"><?php echo number_format($item['final_price'], 0, ',', '.'); ?>đ</span>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <form action="index.php?page=cart-update" method="POST" class="m-0">
                                    <input type="hidden" name="food_id" value="<?php echo (int)$item['food_id']; ?>">
                                    <input type="hidden" name="quantity" value="<?php echo (int)$item['quantity'] - 1; ?>">
                                    <button type="submit" class="btn btn-sm btn-light border rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 11px;">-</button>
                                </form>
                                <span class="body-md fw-bold px-1" style="font-size: 13px; color: var(--yumgo-ink);"><?php echo (int)$item['quantity']; ?></span>
                                <form action="index.php?page=cart-update" method="POST" class="m-0">
                                    <input type="hidden" name="food_id" value="<?php echo (int)$item['food_id']; ?>">
                                    <input type="hidden" name="quantity" value="<?php echo (int)$item['quantity'] + 1; ?>">
                                    <button type="submit" class="btn btn-sm btn-light border rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 11px;">+</button>
                                </form>
                            </div>
                        </div>
                        <a href="index.php?page=cart-remove&id=<?php echo (int)$item['food_id']; ?>" class="p-2" title="Xóa món" style="color: #ff4d4d !important;">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div x-show="<?php echo !empty($drawerCartItems) ? 'true' : 'false'; ?>" class="drawer-footer" id="drawerCartFooter">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="body-md text-secondary">Tổng tạm tính:</span>
                <span class="price-display text-primary fs-5" id="drawerSubtotal"><?php echo number_format($drawerSubtotal, 0, ',', '.'); ?>đ</span>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php?page=cart" class="btn btn-outline-secondary w-50 rounded-pill py-2 body-md fw-semibold">Xem chi tiết</a>
                <a href="index.php?page=checkout" class="btn btn-primary-yumgo w-50 rounded-pill py-2 body-md fw-semibold">Thanh toán</a>
            </div>
        </div>
    </div>

    <div class="menu-overlay" :class="showMenu ? 'show' : ''" @click="showMenu = false"></div>
    <div class="sidebar-menu" :class="showMenu ? 'show' : ''" x-cloak>
        <div class="drawer-header">
            <a class="site-brand" href="index.php?page=home" aria-label="YumGO trang chủ">
                <strong><span>Yum</span>GO</strong>
            </a>
            <button type="button" class="border-0 bg-transparent p-0 d-flex align-items-center justify-content-center" @click="showMenu = false" aria-label="Đóng" style="color: var(--yumgo-ink); width: 32px; height: 32px;">
                <i class="bi bi-x-lg fs-4"></i>
            </button>
        </div>
        <div class="drawer-body">
            <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.5px;">Điều hướng</h6>
            <div class="d-flex flex-column gap-2 mb-4">
                <a href="index.php?page=home" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'home' ? 'active' : 'text-secondary'; ?>">Trang chủ</a>
                <a href="index.php?page=foods" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'foods' ? 'active' : 'text-secondary'; ?>">Thực đơn</a>
                <a href="index.php?page=favorites" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'favorites' ? 'active' : 'text-secondary'; ?>">Món yêu thích</a>
                <a href="index.php?page=cart" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'cart' ? 'active' : 'text-secondary'; ?>">Giỏ hàng</a>
                <a href="index.php?page=checkout" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'checkout' ? 'active' : 'text-secondary'; ?>">Thanh toán</a>
                <a href="index.php?page=order-search" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'order-search' ? 'active' : 'text-secondary'; ?>">Tra cứu đơn hàng</a>
                <a href="index.php?page=order-history" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'order-history' ? 'active' : 'text-secondary'; ?>">Lịch sử đơn hàng</a>
                <a href="index.php?page=support" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'support' ? 'active' : 'text-secondary'; ?>">Hỗ trợ</a>
                <?php if ($headerIsGuest): ?>
                    <a href="index.php?page=login" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'login' ? 'active' : 'text-secondary'; ?>">Đăng nhập</a>
                    <a href="index.php?page=register" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'register' ? 'active' : 'text-secondary'; ?>">Đăng ký</a>
                <?php elseif ($headerIsCustomer): ?>
                    <div class="px-3 py-2 rounded-md border" style="background: var(--yumgo-canvas-soft); border-color: var(--yumgo-hairline) !important;">
                        <div class="small text-muted">Tài khoản</div>
                        <div class="fw-semibold text-dark-theme-ink" style="color: var(--yumgo-ink);"><?php echo htmlspecialchars($headerUserName); ?></div>
                    </div>
                    <a href="index.php?page=account" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'account' ? 'active' : 'text-secondary'; ?>">Tài khoản của tôi</a>
                    <a href="index.php?page=logout" class="nav-link py-2 px-3 rounded-md fw-medium text-secondary">Đăng xuất</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <main>
