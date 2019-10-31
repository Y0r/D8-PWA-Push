/**
 * Chat messages, emails, document updates, settings changes, photo uploads… anything that you want to reach the server even if user navigates away or closes the tab.
 */

self.addEventListener('push', function (event) {
  'use strict';
  console.debug('[Service Worker][PWA PUSH] Push Received.');
  let body;
  if (event.data) {
    body = event.data.text();
  }
  else {
    body = 'Push message no payload';
  }

  console.debug(`[Service Worker][PWA PUSH] Push had this data: "${body}"`);

  let str = JSON.parse(body);
  let options = {
    body: str['message'],
    icon: str['icon'],
    badge: str['icon'],
    vibrate: [100, 50, 100],
    image: str['image'],
    //At the time of writing no browser has support for this option.
    //sound: '/sites/default/files/pwa/sound/goose.mp3',
    data: {
      dateOfArrival: Date.now(),
      primaryKey: '1',
      url: str['url']
    },
    actions: [
      {
        action: 'close',
        title: 'Close'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification(str['title'], options)
  );
});

self.addEventListener('notificationclick', function (event) {
  'use strict';
  console.debug('[Service Worker][PWA PUSH] Notification click Received.');

  let notification = event.notification;
  let action = event.action;
  let url;

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
    console.debug('[Service Worker][PWA PUSH] OpenWindow(url)');
    event.waitUntil(
      clients.openWindow(url)
    );
  }
});

self.addEventListener('notificationclose', function (event) {
  'use strict';
  console.debug('[Service Worker][PWA PUSH] Closed notification: ' + primaryKey);
});