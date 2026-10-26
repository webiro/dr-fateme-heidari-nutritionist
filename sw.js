/* drfatemeheidari.ir — offline cache service worker
 * Cache-first for everything; config.json stays network-first.
 * Bump CACHE_VERSION to force refresh for all users.
 */
const CACHE_VERSION = 'site-v1';
const PRECACHE_URLS = [
  './',
  './app.css',
  './app.js',
  './logo.svg',
  './images/logo.svg',
  './images/doctor.jpg',
  './images/dr-fateme-heidari.jpeg',
  './images/og-image.jpg',
  './qrcode.html',
  './asap.json',
  // CDN libraries & fonts
  'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap',
  'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,300,0,0',
  'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css',
  'https://cdn.plyr.io/3.7.8/plyr.css',
  'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js',
  'https://cdn.plyr.io/3.7.8/plyr.polyfilled.js',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_VERSION).then((cache) =>
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

  // درخواست‌های POST به ورکر رزرو (فرم نوبت) هرگز کش نشوند
  if (url.hostname === 'api.drfatemeheidari.ir') return;
  // navigation ها: network-first تا آپدیت‌های صفحه زود برسند، آفلاین از کش
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
  // config.json: network-first (آدرس ورکر ممکن است تغییر کند)
  if (url.pathname.endsWith('/config.json')) {
    event.respondWith(
      fetch(req).then((res) => {
        if (res && res.ok) {
          const copy = res.clone();
          caches.open(CACHE_VERSION).then((c) => c.put(req, copy)).catch(() => {});
        }
        return res;
      }).catch(() => caches.match(req))
    );
    return;
  }
  // بقیه: cache-first + ذخیره‌ی منابع جدید (فونت‌ها، پست‌ها، ویدیوها)
  event.respondWith(
    caches.match(req).then((hit) => {
      if (hit) return hit;
      return fetch(req).then((res) => {
        if (res && (res.ok || res.type === 'opaque')) {
          const copy = res.clone();
          caches.open(CACHE_VERSION).then((c) => c.put(req, copy)).catch(() => {});
        }
        return res;
      }).catch(() => new Response('آفلاین', { status: 503 }));
    })
  );
});
