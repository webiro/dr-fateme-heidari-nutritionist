/* DIET TISA Platform — offline cache service worker
 * Strategy: cache-first for everything; runtime-cache any new GET.
 * Bump CACHE_VERSION to force a full refresh for all users.
 */
const CACHE_VERSION = 'diettisa-v4';
const PRECACHE_URLS = [
  './',
  './index.htm',
  './data/README.txt',
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
  './vendor/xlsx.full.min.js',
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
  // فقط GET؛ درخواست‌های API (OpenRouter) هرگز کش نشوند
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.pathname.endsWith('/foods.xlsx')) return; // همیشه نسخه تازه اکسل مرجع
  if (url.hostname === 'openrouter.ai') return;

  event.respondWith(
    caches.match(req, { ignoreSearch: url.hostname === 'fonts.googleapis.com' }).then((hit) => {
      if (hit) return hit;
      return fetch(req).then((res) => {
        // پاسخ معتبر یا opaque (CDN) را برای دفعات بعد ذخیره کن
        if (res && (res.ok || res.type === 'opaque')) {
          const copy = res.clone();
          caches.open(CACHE_VERSION).then((c) => c.put(req, copy)).catch(() => {});
        }
        return res;
      }).catch(() => {
        // آفلاین و در کش هم نیست
        return new Response('آفلاین — این منبع قبلاً کش نشده است.', {
          status: 503,
          headers: { 'Content-Type': 'text/plain; charset=utf-8' },
        });
      });
    })
  );
});
