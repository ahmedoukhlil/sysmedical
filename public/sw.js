const CACHE_VERSION = 'mobile-v1';
const STATIC_ASSETS = [
  '/css/app.css',
  '/js/app.js',
  '/offline.html',
  '/icons/icon-192.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_VERSION).then((cache) => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_VERSION).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match('/offline.html'))
    );
    return;
  }

  const url = new URL(request.url);
  if (STATIC_ASSETS.some((path) => url.pathname === path)) {
    event.respondWith(
      caches.match(request).then((cached) => cached || fetch(request))
    );
  }
  // Tout le reste (Livewire ajax, /api/*) : laisse passer au réseau, jamais mis en cache
});
