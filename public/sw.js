/* Ruposhi POS service worker — install-only.
   Deliberately NO caching: business data (due, stock, prices) must always
   come fresh from the server. This SW exists solely to make the app
   installable (add to home screen). */
self.addEventListener('install', function () {
    self.skipWaiting();
});
self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});
self.addEventListener('fetch', function (event) {
    // Network-only passthrough — never serve from cache.
    event.respondWith(fetch(event.request));
});
