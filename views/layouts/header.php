<?php
/**
 * YumGO - Header Layout dùng chung
 */

// Đảm bảo Session đã được khởi chạy để đọc Giỏ hàng
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tính tổng số lượng sản phẩm trong giỏ hàng để hiển thị badge
$headerCartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $headerCartCount += $item['quantity'];
    }
}

// Lấy danh sách sản phẩm trong giỏ hàng để hiển thị trong Side-Drawer
$drawerCartItems = [];
$drawerSubtotal = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    global $pdo;
    require_once dirname(dirname(__DIR__)) . '/models/Food.php';
    $headerFoodModel = new Food($pdo);
    foreach ($_SESSION['cart'] as $foodId => $item) {
        $food = $headerFoodModel->getById($foodId);
        if ($food && $food['is_available']) {
            $finalPrice = $food['price'];
            if ($food['is_sale']) {
                $finalPrice = $food['price'] * (1 - $food['discount_percent'] / 100);
            }
            $itemTotal = $finalPrice * $item['quantity'];
            $drawerSubtotal += $itemTotal;
            $drawerCartItems[] = [
                'food_id' => $food['id'],
                'name' => $food['name'],
                'image' => $food['image'],
                'final_price' => $finalPrice,
                'quantity' => $item['quantity'],
                'item_total' => $itemTotal
            ];
        }
    }
}

// Xác định trang hiện tại để gán class active
$currentPage = isset($_GET['page']) ? trim($_GET['page']) : 'home';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) : 'YumGO - Đặt Món Ăn Giao Ngay'; ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom CSS (Design System Tokens) -->
    <link href="assets/css/user.css" rel="stylesheet">
    
    <!-- GSAP (GreenSock Animation Platform) for high-end micro-interactions -->
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
    
    <!-- SweetAlert2 for premium popup alerts & confirmations -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Alpine.js for lightweight frontend state management -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body x-data="{ showCart: false, showMenu: false }" @keydown.escape="showCart = false; showMenu = false" class="position-relative">
    <!-- Toast Notification Container -->
    <div id="toast-container"></div>

    <!-- Top Bar Navigation (Sticky Header) -->
    <nav class="navbar navbar-expand-lg bg-canvas border-bottom sticky-top py-2 py-lg-3">
        <div class="container px-3">
            <!-- Hamburger button for mobile menu -->
            <button class="btn btn-link text-dark p-0 me-3 d-lg-none border-0" @click="showMenu = true" aria-label="Menu">
                <i class="bi bi-list fs-3"></i>
            </button>

            <!-- Brand Logo -->
            <a class="navbar-brand d-flex align-items-center" href="index.php?page=home">
                <span class="fs-4 fw-bold" style="color: var(--yumgo-primary);">
                    Yum<span style="color: var(--yumgo-ink);">GO</span>
                </span>
                <span class="ms-2 badge bg-warning text-dark rounded-pill fs-7 py-1 px-2 d-none d-sm-inline-block">v1.0</span>
            </a>

            <!-- Desktop Links -->
            <div class="collapse navbar-collapse justify-content-center d-none d-lg-block" id="desktopNav">
                <ul class="navbar-nav gap-2">
                    <li class="nav-item">
                        <a class="nav-link px-3 fw-medium <?php echo $currentPage === 'home' ? 'active text-primary' : 'text-secondary'; ?>" href="index.php?page=home">Trang Chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 fw-medium <?php echo $currentPage === 'foods' ? 'active text-primary' : 'text-secondary'; ?>" href="index.php?page=foods">Thực Đơn</a>
                    </li>
                </ul>
            </div>

            <!-- Header Action Elements -->
            <div class="d-flex align-items-center gap-3">
                <!-- Cart Icon with Badge (Triggers slide-out Drawer) -->
                <a href="index.php?page=cart" @click.prevent="showCart = true" class="position-relative p-2 text-dark fs-5" id="headerCartBtn">
                    <i class="bi bi-bag"></i>
                    <?php if ($headerCartCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-full bg-danger text-white border border-light" style="font-size: 10px; padding: 3px 6px;">
                            <?php echo $headerCartCount; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </nav>

    <!-- Side-Drawer Cart (Alpine.js / CSS slide transition) -->
    <div class="drawer-overlay" :class="showCart ? 'show' : ''" @click="showCart = false"></div>
    <div class="drawer-cart" :class="showCart ? 'show' : ''" x-cloak>
        <div class="drawer-header">
            <h5 class="m-0 fw-bold d-flex align-items-center"><i class="bi bi-bag me-2 text-primary"></i>Giỏ hàng của bạn</h5>
            <button type="button" class="btn-close" @click="showCart = false" aria-label="Close"></button>
        </div>
        <div class="drawer-body">
            <!-- If Cart is Empty -->
            <div x-show="<?php echo empty($drawerCartItems) ? 'true' : 'false'; ?>" class="text-center py-5">
                <div class="fs-1 mb-3" style="opacity: 0.5;">🛒</div>
                <h6 class="fw-bold text-dark">Giỏ hàng đang trống</h6>
                <p class="body-md text-secondary">Duyệt thực đơn và lựa chọn các món ăn ngon lành từ YumGO nhé!</p>
                <a href="index.php?page=foods" @click="showCart = false" class="btn btn-primary-yumgo rounded-pill px-4 py-2 mt-2">Duyệt món ngay</a>
            </div>
            
            <!-- If Cart has items -->
            <div x-show="<?php echo !empty($drawerCartItems) ? 'true' : 'false'; ?>" class="d-flex flex-column gap-3" id="drawerCartList">
                <?php foreach ($drawerCartItems as $item): ?>
                    <div class="d-flex align-items-center gap-3 py-2 border-bottom" data-food-id="<?php echo $item['food_id']; ?>">
                        <!-- Food Image -->
                        <div class="rounded-md overflow-hidden flex-shrink-0" style="width: 50px; height: 50px; border: 1px solid var(--yumgo-hairline);">
                            <?php 
                            $imgFile = !empty($item['image']) ? $item['image'] : '';
                            $imgExists = !empty($imgFile) && file_exists(PATH_ROOT . '/uploads/foods/' . $imgFile);
                            if ($imgExists): 
                            ?>
                                <img src="uploads/foods/<?php echo $imgFile; ?>" class="w-100 h-100" style="object-fit: cover;">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-warning-subtle text-warning font-bold" style="font-size: 18px;">🍔</div>
                            <?php endif; ?>
                        </div>
                        <!-- Info -->
                        <div class="flex-grow-1 min-w-0">
                            <h6 class="title-md text-truncate m-0 text-dark" style="font-size: 14px;"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <span class="price-display text-primary body-md" style="font-size: 13px; font-weight: 700;"><?php echo number_format($item['final_price'], 0, ',', '.'); ?>đ</span>
                            <!-- Quantity Controls -->
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <form action="index.php?page=cart-update" method="POST" class="m-0">
                                    <input type="hidden" name="food_id" value="<?php echo $item['food_id']; ?>">
                                    <input type="hidden" name="quantity" value="<?php echo $item['quantity'] - 1; ?>">
                                    <button type="submit" class="btn btn-sm btn-light border rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 11px;">-</button>
                                </form>
                                <span class="body-md fw-bold px-1" style="font-size: 13px; color: var(--yumgo-ink);"><?php echo $item['quantity']; ?></span>
                                <form action="index.php?page=cart-update" method="POST" class="m-0">
                                    <input type="hidden" name="food_id" value="<?php echo $item['food_id']; ?>">
                                    <input type="hidden" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">
                                    <button type="submit" class="btn btn-sm btn-light border rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 11px;">+</button>
                                </form>
                              </div>
                        </div>
                        <!-- Delete Button -->
                        <a href="index.php?page=cart-remove&id=<?php echo $item['food_id']; ?>" class="text-muted p-2" title="Xóa món">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div x-show="<?php echo !empty($drawerCartItems) ? 'true' : 'false'; ?>" class="drawer-footer">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="body-md text-secondary">Tổng tạm tính:</span>
                <span class="price-display text-primary fs-5" id="drawerSubtotal"><?php echo number_format($drawerSubtotal, 0, ',', '.'); ?>đ</span>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php?page=cart" class="btn btn-outline-secondary w-50 rounded-pill py-2 body-md fw-semibold">Xem Chi Tiết</a>
                <a href="index.php?page=checkout" class="btn btn-primary-yumgo w-50 rounded-pill py-2 body-md fw-semibold">Thanh Toán <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>

    <!-- Left Sidebar Menu (Alpine.js) -->
    <div class="menu-overlay" :class="showMenu ? 'show' : ''" @click="showMenu = false"></div>
    <div class="sidebar-menu" :class="showMenu ? 'show' : ''" x-cloak>
        <div class="drawer-header">
            <span class="fs-4 fw-bold" style="color: var(--yumgo-primary);">
                Yum<span style="color: var(--yumgo-ink);">GO</span>
            </span>
            <button type="button" class="btn-close" @click="showMenu = false" aria-label="Close"></button>
        </div>
        <div class="drawer-body">
            <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.5px;">Điều Hướng</h6>
            <div class="d-flex flex-column gap-2 mb-4">
                <a href="index.php?page=home" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'home' ? 'bg-light text-primary' : 'text-secondary'; ?>">
                    <i class="bi bi-house-door me-2"></i>Trang Chủ
                </a>
                <a href="index.php?page=foods" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'foods' ? 'bg-light text-primary' : 'text-secondary'; ?>">
                    <i class="bi bi-card-list me-2"></i>Thực Đơn
                </a>
                <a href="index.php?page=cart" class="nav-link py-2 px-3 rounded-md fw-medium <?php echo $currentPage === 'cart' ? 'bg-light text-primary' : 'text-secondary'; ?>">
                    <i class="bi bi-bag me-2"></i>Giỏ Hàng
                </a>
            </div>

            <h6 class="text-uppercase text-muted fw-bold mb-3" style="font-size: 11px; letter-spacing: 0.5px;">Tích Hợp Nhóm</h6>
            <div class="d-flex flex-column gap-2">
                <a href="index.php?page=checkout" class="nav-link py-2 px-3 text-secondary rounded-md body-md">
                    <i class="bi bi-credit-card me-2"></i>Thanh Toán
                </a>
                <a href="index.php?page=order-history" class="nav-link py-2 px-3 text-secondary rounded-md body-md">
                    <i class="bi bi-clock-history me-2"></i>Lịch Sử Đơn Hàng
                </a>
                <a href="index.php?page=admin-dashboard" class="nav-link py-2 px-3 text-secondary rounded-md body-md">
                    <i class="bi bi-person-workspace me-2"></i>Bảng Quản Trị (Admin)
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Container Wrapper -->
    <main class="pb-5 mb-5 mb-lg-0">
