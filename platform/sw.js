/* DIET TISA Platform — offline cache service worker
 * Strategy: cache-first for everything; runtime-cache any new GET.
 * Bump CACHE_VERSION to force a full refresh for all users.
 */
const CACHE_VERSION = 'diettisa-v1';
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
  // CDN libraries
  'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap',
  'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,300..700,0..1,-50..200',
  'https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css',
  'https://code.jquery.com/jquery-3.6.0.min.js',
  'https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js',
  'https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js',
  'https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js',
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
