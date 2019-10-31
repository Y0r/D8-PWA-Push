(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.pwa_push = {
    attach: function (context, settings) {
      const applicationServerPublicKey = drupalSettings.pwa_push.public_key;
      const status_all = drupalSettings.pwa_push.status_all;
      const baseUrl = (window.location.protocol + '//' + window.location.host) + (drupalSettings.path.baseUrl);

      if (!(applicationServerPublicKey)) {
        return;
      }

      if (!('serviceWorker' in navigator)) {
        // Service Worker isn't supported on this browser, disable or hide UI.
        //console.debug('[PUSH_MODULE] service worker not supported');
        return;
      }

      if (!('PushManager' in window)) {
        // Push isn't supported on this browser, disable or hide UI.
        //console.debug('[PUSH_MODULE] PushManager not supported');
        return;
      }

      // Requesting notification permission
      if (!('Notification' in window)) {
        // Notification isn't supported on this browser, disable or hide UI.
        //console.debug('[PUSH_MODULE] Notification not supported');
        return;
      }
      else {
        console.debug('[PUSH_MODULE] Notification is supported');
      }

      if (Notification.permission === 'denied') {
        console.debug('[PUSH_MODULE] Notification permission denied');
        return;
      }

      if ('serviceWorker' in navigator) {
        // Request a one-off sync:
        navigator.serviceWorker.ready.then(function (registration) {
          return registration.sync.register('synFirstSync');
        });
      }

      window.addEventListener('beforeinstallprompt', function (e) {
        e.userChoice.then(function (choiceResult) {
          console.debug("[PUSH_MODULE]" + choiceResult.outcome);
          if (choiceResult.outcome === 'dismissed') {
            console.debug('[PUSH_MODULE] User cancelled homescreen install');
          }
          else {
            console.debug('[PUSH_MODULE] User added to homescreen');
          }
        });
      });

      window.addEventListener('appinstalled', (evt) => {
        app.logEvent('advanced_pwa', 'installed');
      });

      // To determine if the app was launched in standalone mode in non-Safari browsers.
      if (window.matchMedia('(display-mode: standalone)').matches) {
        console.debug('display-mode is standalone');
      }

      // To determine if the app was launched in standalone mode in Safari.
      if (window.navigator.standalone === true) {
        console.debug('display-mode is standalone');
      }

      function urlB64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
          .replace(/-/g, '+')
          .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
          outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
      }

      function updatePushSubscription() {
        navigator.serviceWorker.ready.then(function (registration) {
          registration.pushManager.getSubscription().then(function (sub) {
            if (!sub) {
              console.debug('[PUSH_MODULE] Not subscribed to push service!');
              subscribeUser();
              return;
            }
            else {
              // We have a subscription, update the database
              console.debug('[PUSH_MODULE] Subscription object: ', JSON.stringify(sub));
            }
          })
            .catch(function (e) {
              console.debug('[PUSH_MODULE] Error subscribing: ', e);
            });
        });
      }

      function subscribeUser() {
        console.debug('[PUSH_MODULE] subscribeUser');
        const applicationServerKey = urlB64ToUint8Array(applicationServerPublicKey);

        navigator.serviceWorker.ready.then(function (registration) {
          registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: applicationServerKey
          }).then(function (sub) {
            //console.log('[PUSH_MODULE] Endpoint URL: ', JSON.stringify(sub));
            return subscribeToBackEnd(sub);
          }).catch(function (e) {
            if (Notification.permission === 'denied') {
              console.debug('[PUSH_MODULE] Permission for notifications was denied');
            }
            else {
              console.debug('[PUSH_MODULE] Unable to subscribe to push', e);
            }
          });
        });
      }

      function subscribeToBackEnd(subscription) {
        const key = subscription.getKey('p256dh');
        const token = subscription.getKey('auth');
        var subcribe_url = baseUrl + 'pwa/subscribe';
        //console.log('sendSubscriptionToBackEnd ', subscription);

        return fetch(subcribe_url, {
          method: 'POST',
          body: JSON.stringify({
            endpoint: subscription.endpoint,
            key: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
            token: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null
          })
        }).then(function (resp) {
          // Transform the data into json.
          resp = resp.json();
          console.debug('[PUSH_MODULE] subscribeToBackEnd ', resp);
        }).then(function (data) {
          console.debug('[PUSH_MODULE] subscribeToBackEnd ', data);
        }).catch(function (e) {
          console.debug('[PUSH_MODULE] Unable to send subscription to backend:', e);
        });
      }

      /*
      function unsubscribeUser() {
        if ('serviceWorker' in navigator) {
          navigator.serviceWorker.ready.then(function(registration) {
            registration.pushManager.getSubscription()
            .then(function(subscription) {
              if (subscription) {
                unSubscribeFromBackEnd(subscription);
                return subscription.unsubscribe();
              }
            })
            .catch(function(e) {
              console.log('Error unsubscribing', e);
            })
            .then(function() {
              console.log('User is unsubscribed.');
              isSubscribed = false;
            });
          });
        }
      }

      function unSubscribeFromBackEnd(subscription) {
        const key = subscription.getKey('p256dh');
        const token = subscription.getKey('auth');
        var subcribe_url = baseUrl + 'advanced_pwa/unsubscribe';
        console.log('sendSubscriptionToBackEnd ', subscription);

        return fetch(subcribe_url, {
          method: 'POST',
          body: JSON.stringify({
            endpoint: subscription.endpoint,
          })
        }).then(function(resp) {
           // Transform the data into json.
           resp = resp.json();
           comsole.log('unSubscribeFromBackEnd ', resp);
        }).then(function(data) {
          console.log('unSubscribeFromBackEnd ', data);
        }).catch(function (e) {
          console.log('Unable to un-subscribe from backend:', e);
        });
      }
      */

      // Notification popup will appear when user allowed notification permission.
      var confirmationDialog = Drupal.dialog('<div class="pwa_message_div" style="display: none !important;">This site may send you push notifications.</div>', {
        title: Drupal.t('Allow website notifications?'),
        dialogClass: 'pwa-model-popup',
        resizable: false,
        buttons: [
          {
            text: Drupal.t('Allow'),
            class: 'button button--allow',
            click: function () {
              updatePushSubscription();
              confirmationDialog.close();
            }
          },
          {
            text: Drupal.t('Later'),
            class: 'button button--cancel',
            click: function () {
              confirmationDialog.close();
            }
          }
        ],
        closeOnEscape: false,
        create: function () {},
        beforeClose: false,
        close: function (event) {
          // Automatically destroy the DOM element that was used for the dialog.
          $(event.target).remove();
        }
      });

      // Checking if the user is subcribed for notification, if not popup will appear.
      navigator.serviceWorker.ready.then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.getSubscription())
        .then(subscription => {
          if (status_all === 1) {
            if (!subscription) {
              //SHOW DIALOG only on the main page
              if (window.location.href == baseUrl) {
                confirmationDialog.showModal();
              }
            }
          }
          else {
            console.debug('[PUSH_MODULE] Notification feature disabled from configuration form');
          }
        })
        .then(subscription => subscription)
        .catch(e => {
          console.debug('[PUSH_MODULE] Error when updating the subscription', e);
        });
    }
  };
})(jQuery, Drupal, drupalSettings);
