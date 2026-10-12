// Service Worker for Foodio Desktop App
const CACHE_NAME = 'foodio-desktop-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Pass live requests through directly for live real-time WebSocket and polling sync
    event.respondWith(fetch(event.request).catch(() => caches.match(event.request)));
});
