/**
 * YumGO - JavaScript xử lý tương tác Giỏ hàng bằng AJAX & Hiển thị thông báo bằng SweetAlert2 & GSAP Animations
 */

document.addEventListener('DOMContentLoaded', function () {
    // Khởi chạy hiệu ứng Stagger khi tải trang (Vercel-style entrance)
    initGSAPEntranceAnimations();

    // Khởi tạo các sự kiện tương tác giỏ hàng bất đồng bộ
    initAJAXAddToCart();
    initAJAXCartUpdates();
    initAJAXCartRemoves();
    initThemeMode();
    initSearchSuggestions();
    initFavoriteFoods();
    initRecentlyViewedFoods();
    initCheckoutAuthLinks();
    initCheckoutConfirmation();
    initHomeHeroSlider();
    initPWARegistration();
});

function initGSAPEntranceAnimations() {
    document.querySelectorAll('.food-card, .category-pill, .testimonial-card, h1, h2.fw-bold, .display-md').forEach((el) => {
        el.style.opacity = '1';
        el.style.transform = 'none';
        el.style.visibility = 'visible';
    });
}

function showToast(message, type = 'success') {
    if (typeof Swal === 'undefined') {
        return;
    }

    const Toast = Swal.mixin({
        toast: true,
        position: window.matchMedia('(max-width: 575.98px)').matches ? 'bottom' : 'bottom-end',
        showConfirmButton: false,
        timer: 1600,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: type,
        title: message,
        customClass: {
            container: 'swal2-toast-container',
            popup: 'toast-sm rounded-md shadow-card border border-light'
        }
    });
}

function initHomeHeroSlider() {
    const slides = Array.isArray(window.yumgoHeroSlides) ? window.yumgoHeroSlides : [];
    const image = document.getElementById('homeHeroImage');
    const caption = document.getElementById('homeHeroCaption');
    const rating = document.getElementById('homeHeroRating');

    if (!image || slides.length < 2) {
        return;
    }

    let index = 0;
    setInterval(() => {
        index = (index + 1) % slides.length;
        const slide = slides[index];
        image.classList.add('is-changing');

        window.setTimeout(() => {
            image.src = slide.image;
            image.alt = slide.name || 'Món ăn nổi bật của YumGO';
            if (caption) caption.textContent = slide.caption || 'Món nóng hổi, ảnh thật, giá rõ ràng. Đặt là giao ngay!';
            if (rating) rating.textContent = slide.rating || '4.9/5';
            image.classList.remove('is-changing');
        }, 220);
    }, 3200);
}

function updateAllCartBadges(count) {
    const headerCartBtn = document.getElementById('headerCartBtn');
    if (headerCartBtn) {
        let badge = headerCartBtn.querySelector('.badge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'site-cart-badge badge';
                headerCartBtn.appendChild(badge);
            }
            badge.textContent = count;

            if (typeof gsap !== 'undefined') {
                gsap.fromTo(badge, { scale: 0.7 }, { scale: 1, duration: 0.2, ease: 'back.out(1.5)' });
            }
        } else if (badge) {
            badge.remove();
        }
    }

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

            if (typeof gsap !== 'undefined') {
                gsap.fromTo(badge, { scale: 0.7 }, { scale: 1, duration: 0.2, ease: 'back.out(1.5)' });
            }
        } else if (badge) {
            badge.remove();
        }
    }

    document.querySelectorAll('.footer-cart-count').forEach(el => {
        el.textContent = count;
    });
}

function updateFoodQuantityUI(foodId, quantity, itemTotalFormatted) {
    const elements = document.querySelectorAll(`[data-food-id="${foodId}"]`);
    elements.forEach(el => {
        // 1. Update quantity text spans
        const qtySpans = el.querySelectorAll('.d-inline-flex span, .flex-grow-1 span.body-md');
        qtySpans.forEach(span => {
            span.textContent = quantity;
        });

        // 2. Update price totals displays
        const totalSpans = el.querySelectorAll('.price-display');
        totalSpans.forEach(span => {
            span.textContent = itemTotalFormatted;
        });

        // 3. Update hidden input values in the forms
        const forms = el.querySelectorAll('form[action*="page=cart-update"]');
        forms.forEach(form => {
            const inputQty = form.querySelector('input[name="quantity"]');
            const button = form.querySelector('button');
            if (inputQty && button) {
                const isMinus = button.textContent.trim() === '-';
                inputQty.value = isMinus ? quantity - 1 : quantity + 1;
            }
        });
    });
}

function initAJAXAddToCart() {
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && form.action.includes('page=cart-add')) {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = appendCsrfToken(new FormData(form));
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
                        refreshDrawerCart(data.drawer_items || [], data.subtotal || '0đ');

                        if (submitBtn && typeof gsap !== 'undefined') {
                            gsap.fromTo(
                                submitBtn,
                                { scale: 0.9 },
                                { scale: 1.1, duration: 0.1, yoyo: true, repeat: 1, ease: 'power1.inOut' }
                            );
                        }
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(err => {
                    if (submitBtn) submitBtn.disabled = false;
                    console.error('Lỗi AJAX thêm giỏ hàng:', err);
                    showToast('Có lỗi xảy ra khi thêm vào giỏ hàng.', 'error');
                });
        }
    });
}

function initAJAXCartUpdates() {
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && form.action.includes('page=cart-update')) {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = appendCsrfToken(new FormData(form));
            formData.append('ajax', '1');

            const foodId = formData.get('food_id');

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
                        refreshDrawerCart(data.drawer_items || [], data.subtotal || '0đ');

                        if (data.is_removed) {
                            const elements = document.querySelectorAll(`[data-food-id="${foodId}"]`);
                            elements.forEach(el => {
                                if (typeof gsap !== 'undefined') {
                                    gsap.to(el, {
                                        opacity: 0,
                                        x: -50,
                                        duration: 0.35,
                                        ease: 'power2.inOut',
                                        onComplete: () => {
                                            el.remove();
                                            checkEmptyCart();
                                        }
                                    });
                                } else {
                                    el.remove();
                                    checkEmptyCart();
                                }
                            });
                            showToast(data.message, 'success');
                        } else {
                            const qty = parseInt(formData.get('quantity'), 10);
                            updateFoodQuantityUI(foodId, qty, data.item_total);
                        }

                        updateSubtotalDisplay(data.subtotal);

                        // Update unique items count
                        const uniqueCountEl = document.getElementById('cart-unique-count');
                        if (uniqueCountEl && data.drawer_items) {
                            uniqueCountEl.textContent = data.drawer_items.length;
                        }
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(err => {
                    if (submitBtn) submitBtn.disabled = false;
                    console.error('Lỗi AJAX cập nhật số lượng:', err);
                    showToast('Lỗi khi cập nhật số lượng.', 'error');
                });
        }
    });
}

function initAJAXCartRemoves() {
    document.addEventListener('click', function (e) {
        const removeLink = e.target.closest('a');
        if (removeLink && removeLink.href.includes('page=cart-remove')) {
            e.preventDefault();

            const url = new URL(removeLink.href);
            const foodId = url.searchParams.get('id');
            const ajaxUrl = removeLink.href + (removeLink.href.includes('?') ? '&' : '?') + 'ajax=1&csrf_token=' + encodeURIComponent(getCsrfToken());

            if (typeof Swal === 'undefined') {
                removeCartItem(ajaxUrl, foodId);
                return;
            }

            Swal.fire({
                title: 'Xóa món ăn?',
                text: 'Bạn có chắc chắn muốn xóa món ăn này khỏi giỏ hàng?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FF6600',
                cancelButtonColor: '#8E8EA0',
                confirmButtonText: 'Xác nhận xóa',
                cancelButtonText: 'Hủy bỏ',
                customClass: {
                    popup: 'rounded-md shadow-modal',
                    confirmButton: 'rounded-sm px-4 py-2 fw-semibold',
                    cancelButton: 'rounded-sm px-4 py-2 fw-semibold'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    removeCartItem(ajaxUrl, foodId);
                }
            });
        }
    });
}

function removeCartItem(ajaxUrl, foodId) {
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
                refreshDrawerCart(data.drawer_items || [], data.subtotal || '0đ');

                const elements = document.querySelectorAll(`[data-food-id="${foodId}"]`);
                elements.forEach(el => {
                    if (typeof gsap !== 'undefined') {
                        gsap.to(el, {
                            opacity: 0,
                            scale: 0.85,
                            duration: 0.3,
                            ease: 'power2.inOut',
                            onComplete: () => {
                                el.remove();
                                checkEmptyCart();
                            }
                        });
                    } else {
                        el.remove();
                        checkEmptyCart();
                    }
                });

                updateSubtotalDisplay(data.subtotal);

                // Update unique items count
                const uniqueCountEl = document.getElementById('cart-unique-count');
                if (uniqueCountEl && data.drawer_items) {
                    uniqueCountEl.textContent = data.drawer_items.length;
                }
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(err => {
            console.error('Lỗi AJAX xóa món:', err);
            showToast('Lỗi khi xóa món khỏi giỏ hàng.', 'error');
        });
}

function updateSubtotalDisplay(subtotalStr) {
    const summaryCard = document.querySelector('.card.p-4');
    if (summaryCard) {
        const detailSubtotal = summaryCard.querySelector('.body-lg.text-dark');
        if (detailSubtotal) {
            detailSubtotal.textContent = subtotalStr;
            if (typeof gsap !== 'undefined') {
                gsap.fromTo(detailSubtotal, { scale: 0.95 }, { scale: 1, duration: 0.15 });
            }
        }

        const finalSubtotal = summaryCard.querySelector('.price-display');
        if (finalSubtotal) {
            finalSubtotal.textContent = subtotalStr;
            if (typeof gsap !== 'undefined') {
                gsap.fromTo(finalSubtotal, { scale: 0.95 }, { scale: 1, duration: 0.15 });
            }
        }
    }

    const drawerSubtotal = document.getElementById('drawerSubtotal');
    if (drawerSubtotal) {
        drawerSubtotal.textContent = subtotalStr;
        if (typeof gsap !== 'undefined') {
            gsap.fromTo(drawerSubtotal, { scale: 0.95 }, { scale: 1, duration: 0.15 });
        }
    }
}

function refreshDrawerCart(items, subtotalStr) {
    if (!Array.isArray(items)) {
        return fetch('index.php?page=cart-drawer', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateAllCartBadges(data.cart_count || 0);
                    refreshDrawerCart(data.items || [], data.subtotal || '0đ');
                }
            })
            .catch(error => {
                console.error('Lỗi làm mới drawer giỏ hàng:', error);
            });
    }

    const list = document.getElementById('drawerCartList');
    const emptyState = document.querySelector('.drawer-empty-state');
    const footer = document.getElementById('drawerCartFooter');

    if (!list) {
        return;
    }

    const hasItems = Array.isArray(items) && items.length > 0;
    list.innerHTML = hasItems ? items.map(renderDrawerCartItem).join('') : '';
    list.style.display = hasItems ? 'flex' : 'none';

    if (emptyState) {
        emptyState.style.display = hasItems ? 'none' : 'block';
    }

    if (footer) {
        footer.style.display = hasItems ? 'block' : 'none';
    }

    updateSubtotalDisplay(subtotalStr);
}

function renderDrawerCartItem(item) {
    const foodId = parseInt(item.food_id, 10);
    const quantity = parseInt(item.quantity, 10);
    const safeName = escapeHtml(item.name || 'Món ăn');
    const safePrice = escapeHtml(item.final_price || '0đ');
    const safeImage = item.image_url ? escapeHtml(item.image_url) : '';
    const imageHtml = safeImage
        ? `<img src="${safeImage}" class="w-100 h-100" style="object-fit: cover;" alt="${safeName}">`
        : '<div class="w-100 h-100 d-flex align-items-center justify-content-center bg-warning-subtle text-warning fw-bold">Y</div>';

    return `
        <div class="d-flex align-items-center gap-3 py-2 border-bottom" data-food-id="${foodId}">
            <div class="rounded-md overflow-hidden flex-shrink-0" style="width: 50px; height: 50px; border: 1px solid var(--yumgo-hairline);">
                ${imageHtml}
            </div>
            <div class="flex-grow-1 min-w-0">
                <h6 class="title-md text-truncate m-0 text-dark" style="font-size: 14px;">${safeName}</h6>
                <span class="price-display text-primary body-md" style="font-size: 13px; font-weight: 700;">${safePrice}</span>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <form action="index.php?page=cart-update" method="POST" class="m-0">
                        <input type="hidden" name="csrf_token" value="${escapeHtml(getCsrfToken())}">
                        <input type="hidden" name="food_id" value="${foodId}">
                        <input type="hidden" name="quantity" value="${quantity - 1}">
                        <button type="submit" class="btn btn-sm btn-light border rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 11px;">-</button>
                    </form>
                    <span class="body-md fw-bold px-1" style="font-size: 13px; color: var(--yumgo-ink);">${quantity}</span>
                    <form action="index.php?page=cart-update" method="POST" class="m-0">
                        <input type="hidden" name="csrf_token" value="${escapeHtml(getCsrfToken())}">
                        <input type="hidden" name="food_id" value="${foodId}">
                        <input type="hidden" name="quantity" value="${quantity + 1}">
                        <button type="submit" class="btn btn-sm btn-light border rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 11px;">+</button>
                    </form>
                </div>
            </div>
            <a href="index.php?page=cart-remove&id=${foodId}&csrf_token=${encodeURIComponent(getCsrfToken())}" class="text-muted p-2" title="Xóa món">
                <i class="bi bi-trash"></i>
            </a>
        </div>
    `;
}

function checkEmptyCart() {
    // Only count body items, ignore drawer items
    const rows = document.querySelectorAll('table tbody tr, .d-block.d-md-none [data-food-id]');

    if (rows.length === 0 && window.location.search.includes('page=cart')) {
        window.location.reload();
        return;
    }
}

const YUMGO_FAVORITES_KEY = 'favorite_foods';
const YUMGO_RECENT_KEY = 'recent_foods';
const YUMGO_THEME_KEY = 'theme';
const YUMGO_GUEST_STORAGE_NOTICE_KEY = 'yumgo_guest_storage_notice_seen';

function isGuestUser() {
    return document.body?.dataset.authState !== 'user';
}

function notifyGuestStorageOnce() {
    if (!isGuestUser() || localStorage.getItem(YUMGO_GUEST_STORAGE_NOTICE_KEY)) {
        return;
    }

    localStorage.setItem(YUMGO_GUEST_STORAGE_NOTICE_KEY, '1');
    showToast('Đang lưu trên thiết bị này. Đăng nhập để giữ danh sách ổn định hơn.', 'info');
}

function migrateStoredIds(oldKey, newKey) {
    if (localStorage.getItem(newKey)) return;
    const oldValue = localStorage.getItem(oldKey);
    if (oldValue) {
        localStorage.setItem(newKey, oldValue);
    }
}

function readStoredIds(key) {
    try {
        const parsed = JSON.parse(localStorage.getItem(key) || '[]');
        if (!Array.isArray(parsed)) return [];
        return parsed.map(id => parseInt(id, 10)).filter(id => Number.isInteger(id) && id > 0);
    } catch (error) {
        return [];
    }
}

function writeStoredIds(key, ids, limit) {
    const cleaned = Array.from(new Set(ids.map(id => parseInt(id, 10)).filter(id => Number.isInteger(id) && id > 0))).slice(0, limit);
    localStorage.setItem(key, JSON.stringify(cleaned));
    return cleaned;
}

function idsToParam(ids) {
    return ids.join(',');
}

function initFavoriteFoods() {
    migrateStoredIds('yumgo.favoriteFoodIds', YUMGO_FAVORITES_KEY);
    const toggles = document.querySelectorAll('.favorite-toggle[data-food-id]');
    let currentFavorites = readStoredIds(YUMGO_FAVORITES_KEY);

    const authState = document.body?.dataset.authState || 'guest';
    const userNameElement = document.querySelector('.site-account-trigger span');
    const userName = userNameElement ? userNameElement.textContent.trim() : '';
    
    if (sessionStorage.getItem('yumgo_last_auth_state') !== authState || 
        sessionStorage.getItem('yumgo_last_user_name') !== userName) {
        sessionStorage.removeItem('yumgo_favorites_synced');
        sessionStorage.setItem('yumgo_last_auth_state', authState);
        sessionStorage.setItem('yumgo_last_user_name', userName);
    }

    function syncButtons(ids) {
        document.querySelectorAll('.favorite-toggle[data-food-id]').forEach(btn => {
            const id = parseInt(btn.dataset.foodId, 10);
            const isActive = ids.includes(id);
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = isActive ? 'bi bi-heart-fill' : 'bi bi-heart';
            }
        });
    }

    if (!isGuestUser() && !sessionStorage.getItem('yumgo_favorites_synced')) {
        const localParam = idsToParam(currentFavorites);
        const formData = new FormData();
        formData.append('ids', localParam);
        appendCsrfToken(formData);

        fetch('index.php?page=favorite-sync', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && Array.isArray(data.ids)) {
                writeStoredIds(YUMGO_FAVORITES_KEY, data.ids, 48);
                sessionStorage.setItem('yumgo_favorites_synced', '1');
                syncButtons(data.ids);
            }
        })
        .catch(err => {
            console.error('Lỗi sync favorites:', err);
        });
    }

    syncButtons(currentFavorites);

    toggles.forEach(btn => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const id = parseInt(btn.dataset.foodId, 10);
            if (!Number.isInteger(id) || id <= 0) return;

            let ids = readStoredIds(YUMGO_FAVORITES_KEY);
            let added = false;
            if (ids.includes(id)) {
                ids = ids.filter(item => item !== id);
                showToast('Đã bỏ món khỏi yêu thích.', 'success');
            } else {
                ids.unshift(id);
                showToast('Đã lưu món vào yêu thích.', 'success');
                added = true;
                notifyGuestStorageOnce();
            }
            ids = writeStoredIds(YUMGO_FAVORITES_KEY, ids, 48);
            syncButtons(ids);

            if (!isGuestUser()) {
                const formData = new FormData();
                formData.append('food_id', id);
                appendCsrfToken(formData);
                fetch('index.php?page=favorite-toggle', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        showToast(data.message, 'error');
                        let rollbackIds = readStoredIds(YUMGO_FAVORITES_KEY);
                        if (added) {
                            rollbackIds = rollbackIds.filter(item => item !== id);
                        } else {
                            rollbackIds.unshift(id);
                        }
                        writeStoredIds(YUMGO_FAVORITES_KEY, rollbackIds, 48);
                        syncButtons(rollbackIds);
                    }
                })
                .catch(err => {
                    console.error('Lỗi toggle favorite:', err);
                });
            }

            if (document.querySelector('[data-favorites-page="1"]')) {
                const url = new URL(window.location.href);
                if (ids.length > 0) {
                    url.searchParams.set('ids', idsToParam(ids));
                } else {
                    url.searchParams.delete('ids');
                }
                window.location.href = url.toString();
            }
        });
    });

    const favoritesPage = document.querySelector('[data-favorites-page="1"]');
    if (favoritesPage) {
        const url = new URL(window.location.href);
        const idsParam = url.searchParams.get('ids') || '';
        const ids = readStoredIds(YUMGO_FAVORITES_KEY);
        const wantedParam = idsToParam(ids);
        if (wantedParam && idsParam !== wantedParam) {
            url.searchParams.set('ids', wantedParam);
            window.location.replace(url.toString());
        } else if (ids.length > 0) {
            notifyGuestStorageOnce();
        }
    }
}

function initRecentlyViewedFoods() {
    migrateStoredIds('yumgo.recentFoodIds', YUMGO_RECENT_KEY);

    if (!isGuestUser()) {
        return;
    }

    const detailPage = document.querySelector('.food-detail-page[data-current-food-id]');
    if (detailPage) {
        const id = parseInt(detailPage.dataset.currentFoodId, 10);
        if (Number.isInteger(id) && id > 0) {
            const ids = readStoredIds(YUMGO_RECENT_KEY).filter(item => item !== id);
            ids.unshift(id);
            writeStoredIds(YUMGO_RECENT_KEY, ids, 8);
        }
    }

    const recentSection = document.querySelector('[data-recent-section="1"]');
    if (recentSection) {
        const ids = readStoredIds(YUMGO_RECENT_KEY).slice(0, 6);
        const url = new URL(window.location.href);
        const currentParam = url.searchParams.get('recent_ids') || '';
        const wantedParam = idsToParam(ids);

        if (wantedParam && currentParam !== wantedParam) {
            url.searchParams.set('recent_ids', wantedParam);
            window.location.replace(url.toString());
        } else if (ids.length > 0) {
            notifyGuestStorageOnce();
        }
    }
}

function initCheckoutAuthLinks() {
    if (!isGuestUser()) {
        return;
    }

    document.querySelectorAll('a[href*="page=checkout"]').forEach(link => {
        link.setAttribute('href', 'index.php?page=login&redirect=checkout');
    });
}

function initCheckoutConfirmation() {
    const form = document.querySelector('form[data-checkout-form="1"]');
    if (!form || typeof Swal === 'undefined') {
        return;
    }

    form.addEventListener('submit', function (event) {
        if (form.dataset.confirmed === '1') {
            return;
        }

        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const fullName = form.querySelector('[name="full_name"]')?.value.trim() || '';
        const phone = form.querySelector('[name="phone"]')?.value.trim() || '';
        const address = form.querySelector('[name="address"]')?.value.trim() || '';
        const paymentInput = form.querySelector('[name="payment_method"]:checked');
        const paymentLabel = paymentInput?.value === 'Banking' ? 'Chuyển khoản ngân hàng' : 'Thanh toán khi nhận hàng (COD)';
        const note = form.querySelector('[name="note"]')?.value.trim() || 'Không có';

        Swal.fire({
            title: 'Xác nhận đặt hàng',
            html: `
                <div class="checkout-confirm-summary">
                    <div><span>Khách hàng</span><strong>${escapeHtml(fullName)}</strong></div>
                    <div><span>Số điện thoại</span><strong>${escapeHtml(phone)}</strong></div>
                    <div><span>Địa chỉ</span><strong>${escapeHtml(address)}</strong></div>
                    <div><span>Ghi chú</span><strong>${escapeHtml(note)}</strong></div>
                    <div><span>Thanh toán</span><strong>${escapeHtml(paymentLabel)}</strong></div>
                    <hr>
                    <div><span>Tạm tính</span><strong>${escapeHtml(form.dataset.subtotal || '0đ')}</strong></div>
                    <div><span>Phí giao hàng</span><strong>${escapeHtml(form.dataset.shipping || '0đ')}</strong></div>
                    <div class="checkout-confirm-total"><span>Tổng dự kiến</span><strong>${escapeHtml(form.dataset.total || '0đ')}</strong></div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Đặt hàng',
            cancelButtonText: 'Kiểm tra lại',
            confirmButtonColor: '#FF6600',
            cancelButtonColor: '#8E8EA0',
            customClass: {
                popup: 'rounded-md shadow-modal checkout-confirm-popup',
                confirmButton: 'rounded-sm px-4 py-2 fw-semibold',
                cancelButton: 'rounded-sm px-4 py-2 fw-semibold'
            }
        }).then(result => {
            if (result.isConfirmed) {
                form.dataset.confirmed = '1';
                HTMLFormElement.prototype.submit.call(form);
            }
        });
    });
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function appendCsrfToken(formData) {
    if (formData instanceof FormData && !formData.has('csrf_token')) {
        formData.append('csrf_token', getCsrfToken());
    }
    return formData;
}
function initThemeMode() {
    const btn = document.getElementById('themeToggleBtn');
    const root = document.body;
    const savedTheme = localStorage.getItem(YUMGO_THEME_KEY);
    const initialTheme = (savedTheme === 'dark' || savedTheme === 'light') ? savedTheme : 'light';

    function applyTheme(theme) {
        root.setAttribute('data-theme', theme);
        localStorage.setItem(YUMGO_THEME_KEY, theme);

        if (btn) {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
            }
            btn.setAttribute('aria-label', theme === 'dark' ? 'Chuyển sang chế độ sáng' : 'Chuyển sang chế độ tối');
        }
    }

    applyTheme(initialTheme);

    if (btn) {
        btn.addEventListener('click', () => {
            const nextTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(nextTheme);
            showToast(nextTheme === 'dark' ? 'Đã bật Dark Mode.' : 'Đã bật Light Mode.', 'success');
        });
    }
}

function initSearchSuggestions() {
    document.querySelectorAll('.yumgo-search-input').forEach(input => {
        const form = input.closest('form');
        if (!form) return;

        const box = form.querySelector('.search-suggestion-box');
        if (!box) return;

        let controller = null;
        let debounceTimer = null;

        function closeBox() {
            box.innerHTML = '';
            box.classList.remove('show');
        }

        input.addEventListener('input', () => {
            const keyword = input.value.trim();
            clearTimeout(debounceTimer);

            if (keyword.length < 2) {
                closeBox();
                return;
            }

            debounceTimer = setTimeout(() => {
                if (controller) controller.abort();
                controller = new AbortController();

                fetch('index.php?page=food-suggest&q=' + encodeURIComponent(keyword), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal
                })
                    .then(res => res.json())
                    .then(data => {
                        const items = data.items || [];
                        if (items.length === 0) {
                            closeBox();
                            return;
                        }

                        box.innerHTML = items.map(item => `
                            <a href="${item.url}" class="search-suggestion-item">
                                <span class="search-suggestion-icon"><i class="bi bi-search-heart"></i></span>
                                <span class="search-suggestion-copy">
                                    <strong>${escapeHtml(item.name)}</strong>
                                    <small>${escapeHtml(item.category_name)} • ${escapeHtml(item.price)}${item.is_available ? '' : ' • Hết hàng'}</small>
                                </span>
                            </a>
                        `).join('');
                        box.classList.add('show');
                    })
                    .catch(error => {
                        if (error.name !== 'AbortError') {
                            closeBox();
                        }
                    });
            }, 220);
        });

        document.addEventListener('click', (event) => {
            if (!form.contains(event.target)) {
                closeBox();
            }
        });
    });
}

function initPWARegistration() {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('service-worker.js').catch((error) => {
            console.warn('YumGO service worker registration failed:', error);
        });
    });
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
