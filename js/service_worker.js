// Version: 1.0
var cacheName = 'syn-advanced_pwa-cache-v1';
var filesToCache = [
  '/',
  'manifest.json'
];

// Listen for install event, set callback
self.addEventListener('install', function (event) {
  'use strict';
  // console.log('Service worker installing...');
  event.waitUntil(
    caches.open(cacheName).then(function (cache) {
      return cache.addAll(filesToCache);
    }).then(function () {
      return self.skipWaiting();
    })
  );
});

// Fired when the Service Worker starts up
self.addEventListener('activate', function (event) {
  'use strict';
  // console.log('Service Worker: Activating....');

  event.waitUntil(
    caches.keys().then(keyList => {
      return Promise.all(keyList.map(key => {
        if (key !== cacheName) {
          return caches.delete(key);
        }
      }));
    }));
  return self.clients.claim();
});

self.addEventListener('fetch', function (event) {
  'use strict';
  // console.log('Service Worker: Fetch', event.request.url);
  if (event.request.url.match('^.*(/admin/).*$') || event.request.url.match('^.*(/node/[0-9]+/edit).*$') || event.request.url.match('^.*(/node/*/delete).*$')) {
    return false;
  }
  else {
    event.respondWith(
      caches.open(cacheName).then(function (cache) {
        return cache.match(event.request).then(function (response) {
          return response || fetch(event.request).then(function (response) {
            cache.put(event.request, response);
            return response;
          });
        }).catch(function () {
          // If both fail, show a generic fallback:
          return caches.match('/offline');
        });
      })
    );
  }
});

/**
 * Chat messages, emails, document updates, settings changes, photo uploads… anything that you want to reach the server even if user navigates away or closes the tab.
 */
self.addEventListener('sync', function (event) {
  'use strict';
  if (event.tag === 'synFirstSync') {
    event.waitUntil(
      caches.open(cacheName).then(function (cache) {
        return cache.addAll(filesToCache);
      }).then(function () {
        return self.skipWaiting();
      })
    );
  }
});

self.addEventListener('push', function (event) {
  'use strict';
  // console.log('[Service Worker] Push Received.');
  var body;
  if (event.data) {
    body = event.data.text();
  }
  else {
    body = 'Push message no payload';
  }

  // console.log(`[Service Worker] Push had this data: "${body}"`);
  var str = JSON.parse(body);
  var options = {
    body: str['message'],
    icon: str['icon'],
    badge: str['icon'],
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: '1',
      url: str['url']
    },
    actions: [
      {action: 'close', title: 'Close'}
    ]
  };
  event.waitUntil(
    self.registration.showNotification(str['title'], options)
  );
});

self.addEventListener('notificationclick', function (event) {
  'use strict';
  // console.log('[Service Worker] Notification click Received.');

  var notification = event.notification;
  var action = event.action;
  var url;

  if (notification.data.url) {
    url = notification.data.url;
  }
  else {
    url = '/';
  }

  if (action === 'close') {
    notification.close();
  }
  else {
    event.waitUntil(
      clients.openWindow(url)
    );
  }
});

self.addEventListener('notificationclose', function (event) {
  'use strict';
  // console.log('Closed notification: ' + primaryKey);
});
