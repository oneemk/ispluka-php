const CACHE = 'ispluka-static-v3';
const STATIC_PREFIXES = ['/assets/', '/manifest.json', '/favicon.ico'];

self.addEventListener('install', event => {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.map(key => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // Do not intercept JavaScript files. This avoids the cPanel/ServiceWorker
  // corrupted-content issue seen with mikrotik-routers.js.
  if (url.pathname.endsWith('.js')) return;

  // Never intercept application/API responses.
  if (url.pathname.startsWith('/api/')) return;

  // Only cache safe static non-JS assets.
  if (!STATIC_PREFIXES.some(prefix => url.pathname === prefix || url.pathname.startsWith(prefix))) return;

  event.respondWith(
    fetch(request)
      .then(response => {
        if (response.ok) {
          const copy = response.clone();
          caches.open(CACHE).then(cache => cache.put(request, copy)).catch(() => {});
        }
        return response;
      })
      .catch(() => caches.match(request).then(cached => cached || Response.error()))
  );
});
