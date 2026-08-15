const CACHE = 'ispluka-static-v2';
const STATIC_PREFIXES = ['/assets/', '/manifest.json', '/favicon.ico'];

self.addEventListener('install', event => {
  event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(key => key !== CACHE).map(key => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // Never cache application/API responses. They must always reach PHP.
  if (url.pathname.startsWith('/api/')) return;

  // Only handle static assets. This prevents the service worker from
  // corrupting/intercepting dynamic HTML and API requests.
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
