/**
 * YumGO - JavaScript xử lý tương tác Giỏ hàng bằng AJAX & Hiển thị thông báo bằng SweetAlert2 & GSAP Animations
 */

let cartNeedsRefresh = false;

document.addEventListener('DOMContentLoaded', function () {
    // Khởi chạy hiệu ứng Stagger khi tải trang (Vercel-style entrance)
    initGSAPEntranceAnimations();
    
    // Khởi tạo các sự kiện tương tác giỏ hàng bất đồng bộ
    initAJAXAddToCart();
    initAJAXCartUpdates();
    initAJAXCartRemoves();
    initCartRefreshOnOpen();
});

function markCartNeedsRefresh() {
    cartNeedsRefresh = true;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function refreshDrawerCart() {
    const drawerList = document.getElementById('drawerCartList');
    const drawerEmpty = document.getElementById('drawerEmptyState');
    const drawerFooter = document.getElementById('drawerFooter');
    const drawerSubtotal = document.getElementById('drawerSubtotal');

    return fetch('index.php?page=cart-drawer', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            return;
        }

        updateAllCartBadges(data.cart_count);

        if (data.is_empty) {
            if (drawerList) {
                drawerList.innerHTML = '';
                drawerList.style.display = 'none';
            }
            if (drawerEmpty) {
                drawerEmpty.style.display = 'block';
            }
            if (drawerFooter) {
                drawerFooter.style.display = 'none';
            }
            if (drawerSubtotal) {
                drawerSubtotal.textContent = data.subtotal;
            }
            return;
        }

        if (drawerEmpty) {
            drawerEmpty.style.display = 'none';
        }

        if (drawerList) {
                    drawerList.style.display = '';
                    drawerList.innerHTML = data.items.map(item => {
                        const imageBlock = item.image_exists && item.image ?
                            `<img src="uploads/foods/${escapeHtml(item.image)}" class="w-100 h-100" style="object-fit: cover;">` :
                            `<div class="w-100 h-100 d-flex align-items-center justify-content-center bg-warning-subtle text-warning font-bold" style="font-size: 18px;">🍔</div>`;

                        return `
                            <div class="d-flex align-items-start gap-3 py-2 border-bottom" data-food-id="${item.food_id}">
                                <div class="rounded-md overflow-hidden flex-shrink-0" style="width: 50px; height: 50px; border: 1px solid var(--yumgo-hairline);">
                                    ${imageBlock}
                                </div>
                                <div class="flex-grow-1 min-w-0 d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <h6 class="title-md text-truncate m-0 text-dark" style="font-size: 14px; line-height: 1.2; max-width: calc(100% - 40px);">${escapeHtml(item.name)}</h6>
                                        <a href="index.php?page=cart-remove&id=${item.food_id}" class="text-muted p-2 flex-shrink-0" title="Xóa món">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <span class="price-display text-primary body-md" style="font-size: 13px; font-weight: 700;">${escapeHtml(item.final_price)}</span>
                                        <div class="d-flex align-items-center gap-2">
                                            <form action="index.php?page=cart-update" method="POST" class="m-0">
                                                <input type="hidden" name="food_id" value="${item.food_id}">
                                                <input type="hidden" name="quantity" value="${item.quantity - 1}">
                                                <button type="submit" class="btn btn-sm btn-light border rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 11px;">-</button>
                                            </form>
                                            <span class="body-md fw-bold px-1" style="font-size: 13px; color: var(--yumgo-ink);">${item.quantity}</span>
                                            <form action="index.php?page=cart-update" method="POST" class="m-0">
                                                <input type="hidden" name="food_id" value="${item.food_id}">
                                                <input type="hidden" name="quantity" value="${item.quantity + 1}">
                                                <button type="submit" class="btn btn-sm btn-light border rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 11px;">+</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>`;
                    }).join('');
                }
        if (drawerFooter) {
            drawerFooter.style.display = 'block';
        }
        if (drawerSubtotal) {
            drawerSubtotal.textContent = data.subtotal;
        }
    });
}

function initCartRefreshOnOpen() {
    document.addEventListener('click', function (e) {
        const cartLink = e.target.closest('#headerCartBtn');
        if (!cartLink || !cartNeedsRefresh) {
            return;
        }

        e.preventDefault();
        cartNeedsRefresh = false;
        refreshDrawerCart();
    }, true);
}

/**
 * Hiệu ứng xuất hiện mượt mà các thành phần khi tải trang
 */
function initGSAPEntranceAnimations() {
    // Đăng ký ScrollTrigger của GSAP nếu có sẵn
    if (typeof ScrollTrigger !== 'undefined') {
        gsap.registerPlugin(ScrollTrigger);
    }

    // Kiểm tra cấu hình prefers-reduced-motion (A11y)
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
        return; // Tắt tất cả chuyển động nếu hệ điều hành yêu cầu giảm chuyển động
    }

    // Animation cho danh sách món ăn (Food Cards) - Sử dụng ScrollTrigger khi cuộn trang
    const revealGrid = document.querySelector('.gsap-reveal-grid');
    if (revealGrid && typeof ScrollTrigger !== 'undefined') {
        gsap.from('.gsap-reveal-grid .food-card', {
            scrollTrigger: {
                trigger: '.gsap-reveal-grid',
                start: "top 85%", // Kích hoạt khi top của grid chạm 85% chiều cao viewport
                toggleActions: "play none none none"
            },
            opacity: 0,
            y: 40,
            stagger: 0.08,
            duration: 0.8,
            ease: "power3.out"
        });
    } else if (document.querySelectorAll('.food-card').length > 0) {
        // Fallback entrance animation nếu tải trực tiếp trang list món
        gsap.from('.food-card', {
            opacity: 0,
            y: 40,
            stagger: 0.06,
            duration: 0.8,
            ease: "power3.out"
        });
    }

    // Animation cho các nút danh mục món ăn (Category Pills)
    const revealStrip = document.querySelector('.gsap-reveal-strip');
    if (revealStrip && typeof ScrollTrigger !== 'undefined') {
        gsap.from('.gsap-reveal-strip .category-pill', {
            scrollTrigger: {
                trigger: '.gsap-reveal-strip',
                start: "top 90%",
                toggleActions: "play none none none"
            },
            opacity: 0,
            x: 20,
            stagger: 0.04,
            duration: 0.6,
            ease: "power2.out"
        });
    } else if (document.querySelectorAll('.category-pill').length > 0) {
        gsap.from('.category-pill', {
            opacity: 0,
            x: 20,
            stagger: 0.04,
            duration: 0.6,
            ease: "power2.out"
        });
    }

    // Animation cho các thẻ nhận xét khách hàng (Testimonial Cards)
    const revealReviews = document.querySelector('.gsap-reveal-reviews');
    if (revealReviews && typeof ScrollTrigger !== 'undefined') {
        gsap.from('.gsap-reveal-reviews .testimonial-card', {
            scrollTrigger: {
                trigger: '.gsap-reveal-reviews',
                start: "top 85%",
                toggleActions: "play none none none"
            },
            opacity: 0,
            y: 30,
            stagger: 0.08,
            duration: 0.7,
            ease: "power2.out"
        });
    }

    // Animation nhẹ cho tiêu đề trang hiển thị
    const mainTitle = document.querySelector('h1, h2.fw-bold, .display-md');
    if (mainTitle) {
        gsap.from(mainTitle, {
            opacity: 0,
            y: -10,
            duration: 0.5,
            ease: "power2.out"
        });
    }
}

/**
 * Hệ thống hiển thị Toast thông báo sử dụng SweetAlert2 (đồng bộ giao diện)
 * @param {string} message Nội dung thông báo
 * @param {string} type Loại thông báo ('success' hoặc 'error')
 */
function showToast(message, type = 'success') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: type, // 'success' hoặc 'error'
        title: message,
        customClass: {
            popup: 'rounded-md shadow-card border border-light toast-sm'
        }
    });
}

/**
 * Cập nhật số lượng giỏ hàng trên tất cả các Badge ở Header và Bottom Nav
 * @param {number} count Số lượng giỏ hàng mới
 */
function updateAllCartBadges(count) {
    // 1. Badge trên Header (Desktop & Mobile)
    const headerCartBtn = document.getElementById('headerCartBtn');
    if (headerCartBtn) {
        let badge = headerCartBtn.querySelector('.badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-full bg-danger text-white border border-light';
                badge.style.cssText = 'font-size: 10px; padding: 3px 6px;';
                headerCartBtn.appendChild(badge);
            }
            badge.textContent = count;
            
            // Nhấp nháy nhẹ badge khi số lượng thay đổi
            gsap.fromTo(badge, { scale: 0.7 }, { scale: 1, duration: 0.2, ease: "back.out(1.5)" });
        } else if (badge) {
            badge.remove();
        }
    }

    // 2. Badge trên Bottom Nav Mobile
    const mobileCartNav = document.querySelector('.bottom-nav-item.position-relative');
    if (mobileCartNav) {
        let badge = mobileCartNav.querySelector('.badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'position-absolute top-1 start-50 translate-middle badge rounded-full bg-danger text-white border border-light';
                badge.style.cssText = 'font-size: 8px; padding: 2px 4px; left: 62% !important;';
                mobileCartNav.appendChild(badge);
            }
            badge.textContent = count;
            
            gsap.fromTo(badge, { scale: 0.7 }, { scale: 1, duration: 0.2, ease: "back.out(1.5)" });
        } else if (badge) {
            badge.remove();
        }
    }
}

/**
 * 1. Đăng ký luồng thêm vào giỏ bằng AJAX
 */
function initAJAXAddToCart() {
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && form.action.includes('page=cart-add')) {
            e.preventDefault(); // Ngăn load lại trang

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(form);
            formData.append('ajax', '1');

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (submitBtn) submitBtn.disabled = false;

                if (data.success) {
                    showToast(data.message, 'success');
                    updateAllCartBadges(data.cart_count);

                    // Nếu Drawer đang mở, làm mới nội dung giỏ hàng ngay lập tức
                    if (document.querySelector('.drawer-cart.show')) {
                        refreshDrawerCart();
                        return;
                    }

                    markCartNeedsRefresh();
                    
                    // Micro-interaction: Nút bấm phồng lên nhẹ bằng GSAP
                    if (submitBtn) {
                        gsap.fromTo(submitBtn, 
                            { scale: 0.9 }, 
                            { scale: 1.1, duration: 0.1, yoyo: true, repeat: 1, ease: "power1.inOut" }
                        );
                    }
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => {
                if (submitBtn) submitBtn.disabled = false;
                console.error("Lỗi AJAX thêm giỏ hàng:", err);
                showToast("Có lỗi xảy ra khi thêm vào giỏ hàng.", "error");
            });
        }
    });
}

/**
 * 2. Đăng ký luồng tăng/giảm số lượng trong trang giỏ hàng bằng AJAX
 */
function initAJAXCartUpdates() {
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && form.action.includes('page=cart-update')) {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(form);
            formData.append('ajax', '1');

            const row = form.closest('tr') || form.closest('.py-3.border-bottom') || form.closest('.py-2.border-bottom');
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (submitBtn) submitBtn.disabled = false;

                if (data.success) {
                    updateAllCartBadges(data.cart_count);

                    if (data.is_removed) {
                        // Nếu số lượng về 0, dòng sản phẩm trượt mất mượt mà bằng GSAP
                        if (row) {
                            gsap.to(row, {
                                opacity: 0,
                                x: -50,
                                duration: 0.35,
                                ease: "power2.inOut",
                                onComplete: () => {
                                    row.remove();
                                    checkEmptyCart();
                                }
                            });
                        } else {
                            refreshDrawerCart();
                            return;
                        }
                        showToast(data.message, 'success');
                    } else {
                        // Cập nhật text số lượng trong pill
                        const qtySpan = form.parentNode.querySelector('span');
                        if (qtySpan) qtySpan.textContent = formData.get('quantity');

                        // Cập nhật giá trị hiển thị tổng của món (Item Total)
                        if (row) {
                            const itemTotalSpan = row.querySelector('.price-display');
                            if (itemTotalSpan) {
                                itemTotalSpan.textContent = data.item_total;
                                // Hiệu ứng zoom nhẹ tổng tiền để báo hiệu đã thay đổi
                                gsap.fromTo(itemTotalSpan, { scale: 0.95 }, { scale: 1, duration: 0.15 });
                            }
                        }

                        // Đồng bộ giá trị số lượng cho form trừ (-) và cộng (+) để các click sau chính xác
                        const qty = parseInt(formData.get('quantity'));
                        const allForms = form.parentNode.querySelectorAll('form');
                        allForms.forEach(f => {
                            const inputQty = f.querySelector('input[name="quantity"]');
                            if (inputQty) {
                                if (f.querySelector('button').textContent.trim() === '-') {
                                    inputQty.value = qty - 1;
                                } else {
                                    inputQty.value = qty + 1;
                                }
                            }
                        });
                    }

                    // Cập nhật tổng tiền hóa đơn (Subtotal)
                    updateSubtotalDisplay(data.subtotal);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(err => {
                if (submitBtn) submitBtn.disabled = false;
                console.error("Lỗi AJAX cập nhật số lượng:", err);
                showToast("Lỗi khi cập nhật số lượng.", "error");
            });
        }
    });
}

/**
 * 3. Đăng ký luồng xóa món bằng nút ✕ tại trang giỏ hàng
 */
function initAJAXCartRemoves() {
    document.addEventListener('click', function (e) {
        const removeLink = e.target.closest('a');
        if (removeLink && removeLink.href.includes('page=cart-remove')) {
            e.preventDefault();

            const row = removeLink.closest('tr') || removeLink.closest('.py-3.border-bottom') || removeLink.closest('.py-2.border-bottom');
            const ajaxUrl = removeLink.href + '&ajax=1';

            // Thay thế hộp thoại confirm thô sơ bằng SweetAlert2 cực kỳ hiện đại
            Swal.fire({
                title: 'Xóa món ăn?',
                text: "Bạn có chắc chắn muốn xóa món ăn này khỏi giỏ hàng?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF6600', // Tông cam YumGO
                cancelButtonColor: '#8E8EA0',  // Tông xám nhạt
                confirmButtonText: 'Xác nhận xóa',
                cancelButtonText: 'Hủy bỏ',
                customClass: {
                    container: 'swal2-container-high',
                    popup: 'rounded-md shadow-modal swal2-popup-high',
                    backdrop: 'swal2-backdrop-high',
                    confirmButton: 'rounded-sm px-4 py-2 fw-semibold',
                    cancelButton: 'rounded-sm px-4 py-2 fw-semibold'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(ajaxUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            updateAllCartBadges(data.cart_count);
                            
                            // Hiệu ứng dòng sản phẩm thu nhỏ và biến mất mượt mà
                            if (row) {
                                gsap.to(row, {
                                    opacity: 0,
                                    scale: 0.85,
                                    duration: 0.3,
                                    ease: "power2.inOut",
                                    onComplete: () => {
                                        row.remove();
                                        checkEmptyCart();
                                    }
                                });
                            } else {
                                refreshDrawerCart();
                                return;
                            }
                            
                            updateSubtotalDisplay(data.subtotal);
                        } else {
                            showToast(data.message, 'error');
                        }
                    })
                    .catch(err => {
                        console.error("Lỗi AJAX xóa món:", err);
                        showToast("Lỗi khi xóa món khỏi giỏ hàng.", "error");
                    });
                }
            });
        }
    });
}

/**
 * Cập nhật hiển thị tổng tiền hóa đơn trên giao diện (giỏ hàng chính và Drawer)
 * @param {string} subtotalStr Chuỗi số tiền đã format (ví dụ: "70.000đ")
 */
function updateSubtotalDisplay(subtotalStr) {
    const summaryCard = document.querySelector('.card.p-4');
    if (summaryCard) {
        // Cập nhật Subtotal ở phần chi tiết
        const detailSubtotal = summaryCard.querySelector('.body-lg.text-dark');
        if (detailSubtotal) {
            detailSubtotal.textContent = subtotalStr;
            gsap.fromTo(detailSubtotal, { scale: 0.95 }, { scale: 1, duration: 0.15 });
        }

        // Cập nhật Tổng tiền cuối cùng
        const finalSubtotal = summaryCard.querySelector('.price-display');
        if (finalSubtotal) {
            finalSubtotal.textContent = subtotalStr;
            gsap.fromTo(finalSubtotal, { scale: 0.95 }, { scale: 1, duration: 0.15 });
        }
    }

    // Cập nhật tổng tiền trong Side-Drawer Cart
    const drawerSubtotal = document.getElementById('drawerSubtotal');
    if (drawerSubtotal) {
        drawerSubtotal.textContent = subtotalStr;
        gsap.fromTo(drawerSubtotal, { scale: 0.95 }, { scale: 1, duration: 0.15 });
    }
}

/**
 * Kiểm tra xem giỏ hàng đã rỗng hoàn toàn chưa. Nếu rỗng, tự reload để hiển thị trạng thái trống.
 */
function checkEmptyCart() {
    const rows = document.querySelectorAll('table tbody tr, .d-block.d-md-none .py-3.border-bottom, .drawer-cart .d-flex.align-items-center.py-2.border-bottom');
    const drawerRows = document.querySelectorAll('#drawerCartList > div');
    
    // Nếu ở trang giỏ hàng chính và bị trống
    if (rows.length === 0 && window.location.search.includes('page=cart')) {
        window.location.reload();
        return;
    }

    // Nếu ở các trang khác mà giỏ hàng Drawer bị trống
    if (drawerRows.length === 0) {
        refreshDrawerCart();
    }
}
