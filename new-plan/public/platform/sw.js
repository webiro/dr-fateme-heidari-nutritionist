/* پلتفرم تغذیه‌ساز — offline cache service worker (dynamic edition)
 * Navigations are network-first so panel edits and platform updates reach
 * the doctor immediately; vendor assets stay cache-first for offline use.
 * Bump CACHE_VERSION to force a full refresh.
 */
const CACHE_VERSION = 'diettisa-v6';
const PRECACHE_URLS = [
  './images/soop.png',
  './images/mast.png',
  './images/shir.png',
  './images/chai.png',
  './images/mive.png',
  './images/berenj.png',
  './images/mokamel.png',
  // کتابخانه‌ها و فونت‌ها — لوکال (vendor)
  './vendor/fonts/vazirmatn.css',
  './vendor/fonts/webfonts/vazirmatn-01.woff2',
  './vendor/fonts/webfonts/vazirmatn-02.woff2',
  './vendor/fonts/webfonts/vazirmatn-03.woff2',
  './vendor/fonts/material-symbols.css',
  './vendor/fonts/webfonts/material-symbols-01.woff2',
  './vendor/persian-datepicker.min.css',
  './vendor/jquery-3.6.0.min.js',
  './vendor/persian-date.min.js',
  './vendor/persian-datepicker.min.js',
  './vendor/html2canvas.min.js',
  './vendor/jspdf.umd.min.js',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_VERSION).then((cache) =>
      // هیچ آیتمی نباید کل نصب را خراب کند؛ line-by-line تا همه‌ی موفق‌ها ذخیره شوند
      Promise.allSettled(PRECACHE_URLS.map((u) => cache.add(new Request(u, { cache: 'reload' }))))
    ).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_VERSION).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  // API calls (proxy / AI / records) never cached
  if (url.pathname.endsWith('.php')) return;

  // Pages (navigation): network-first so updates arrive immediately
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).then((res) => {
        if (res && res.ok) {
          const copy = res.clone();
          caches.open(CACHE_VERSION).then((c) => c.put(req, copy)).catch(() => {});
        }
        return res;
      }).catch(() => caches.match(req).then((hit) => hit || caches.match('./')))
    );
    return;
  }

  // Everything else: cache-first + opportunistic fill
  event.respondWith(
    caches.match(req).then((hit) => {
      if (hit) return hit;
      return fetch(req).then((res) => {
        if (res && res.ok) {
          const copy = res.clone();
          caches.open(CACHE_VERSION).then((c) => c.put(req, copy)).catch(() => {});
        }
        return res;
      }).catch(() => new Response('آفلاین — این منبع قبلاً کش نشده است.', {
        status: 503,
        headers: { 'Content-Type': 'text/plain; charset=utf-8' },
      }));
    })
  );
});
