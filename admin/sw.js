'use strict';

const CACHE_VERSION = 'gc-admin-static-v2.0.0';
const APP_SCOPE = new URL('./', self.location.href);
const STATIC_ASSETS = [
  'offline.html',
  'manifest.webmanifest',
  'assets/css/admin.css?v=2.0.0',
  'assets/js/admin.js?v=2.0.0',
  'assets/vendor/bootstrap/css/bootstrap.min.css',
  'assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
  'assets/img/app-icon-180.png',
  'assets/img/app-icon-192.png',
  'assets/img/app-icon-512.png',
  'assets/img/app-icon-maskable-512.png'
].map((path) => new URL(path, APP_SCOPE).href);

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_VERSION).then((cache) => Promise.allSettled(
      STATIC_ASSETS.map((asset) => cache.add(new Request(asset, { cache: 'reload' })))
    ))
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key.startsWith('gc-admin-static-') && key !== CACHE_VERSION).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin || !url.pathname.startsWith(APP_SCOPE.pathname)) return;

  const isNavigation = request.mode === 'navigate' || request.destination === 'document';
  if (isNavigation) {
    event.respondWith(
      fetch(request, { cache: 'no-store' }).catch(() => caches.match(new URL('offline.html', APP_SCOPE).href))
    );
    return;
  }

  const isStaticAsset = url.pathname.includes(`${APP_SCOPE.pathname}assets/`) || url.pathname.endsWith('/manifest.webmanifest');
  if (!isStaticAsset) return;

  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) return cached;
      return fetch(request).then((response) => {
        if (response.ok) {
          const copy = response.clone();
          caches.open(CACHE_VERSION).then((cache) => cache.put(request, copy));
        }
        return response;
      });
    })
  );
});
