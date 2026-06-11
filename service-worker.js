const YUMGO_CACHE = 'yumgo-shell-v7';
const SHELL_ASSETS = [
  './',
  './index.php?page=home',
  './index.php?page=foods',
  './index.php?page=cart',
  './assets/css/user.css?v=20260608-account1',
  './assets/js/cart.js?v=20260608-cartpersist2',
  './uploads/banners/hero_combo.png',
  './manifest.json',
  './offline.html'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(YUMGO_CACHE)
      .then((cache) => cache.addAll(SHELL_ASSETS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.map((key) => {
        if (key !== YUMGO_CACHE) {
          return caches.delete(key);
        }
        return null;
      })))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);
  const page = url.searchParams.get('page');

  // Bypass cache for AJAX requests or dynamic pages/actions
  const isAjax = url.searchParams.has('ajax') || 
                 request.headers.get('X-Requested-With') === 'XMLHttpRequest' ||
                 request.headers.get('accept')?.includes('application/json');

  const dynamicPages = [
    'checkout', 'process-checkout', 'cancel-order', 'order-edit', 'order-invoice',
    'cart-add', 'cart-update', 'cart-remove', 'cart-drawer',
    'favorite-toggle', 'favorite-sync',
    'login', 'register', 'logout',
    'account-update', 'account-password',
    'food-suggest', 'admin-dashboard', 'reorder'
  ];

  if (isAjax || (page && dynamicPages.includes(page))) {
    return;
  }

  if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response && response.status === 200 && response.type === 'basic') {
            const clone = response.clone();
            caches.open(YUMGO_CACHE).then((cache) => cache.put(request, clone));
          }
          return response;
        })
        .catch(() => caches.match(request).then((cached) => cached || caches.match('./offline.html')))
    );
    return;
  }

  event.respondWith(
    caches.match(request).then((cached) => {
      const network = fetch(request)
        .then((response) => {
          if (response && response.status === 200 && response.type === 'basic') {
            const clone = response.clone();
            caches.open(YUMGO_CACHE).then((cache) => cache.put(request, clone));
          }
          return response;
        })
        .catch(() => cached);

      return cached || network;
    })
  );
});
