/* drfatemeheidari.ir — offline cache service worker (dynamic PHP edition)
 * All vendor assets are local; precache covers them.
 * Navigations stay network-first so admin edits appear immediately.
 * /admin/ and /api/ are never cached.
 */
const CACHE_VERSION = 'site-dynamic-v1';
const PRECACHE_URLS = [
  './',
  './assets/app.css',
  './assets/app.js',
  './assets/images/logo.svg',
  './assets/images/doctor.jpg',
  './assets/images/dr-fateme-heidari.jpeg',
  './assets/images/og-image.jpg',
  './assets/vendor/fonts/site-fonts.css',
  './assets/vendor/owl/owl.carousel.min.css',
  './assets/vendor/plyr/plyr.css',
  './assets/vendor/jquery/jquery-3.7.1.min.js',
  './assets/vendor/owl/owl.carousel.min.js',
  './assets/vendor/plyr/plyr.polyfilled.js',
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

  // Never cache dynamic/admin areas or cross-origin requests
  if (url.origin !== self.location.origin) return;
  if (/\/(admin|api)\//.test(url.pathname)) return;
  if (/\/(install|login)\.php/.test(url.pathname)) return;

  // Pages: network-first so content edits reach users right away
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

  // Everything else: cache-first + opportunistic fill (fonts, reels, post slides)
  event.respondWith(
    caches.match(req).then((hit) => {
      if (hit) return hit;
      return fetch(req).then((res) => {
        if (res && res.ok) {
          const copy = res.clone();
          caches.open(CACHE_VERSION).then((c) => c.put(req, copy)).catch(() => {});
        }
        return res;
      }).catch(() => new Response('آفلاین', { status: 503 }));
    })
  );
});
