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
        console.debug('service worker not supported');
        return;
      }

      if (!('PushManager' in window)) {
        // Push isn't supported on this browser, disable or hide UI.
        console.debug('PushManager not supported');
        return;
      }

      // Requesting notification permission
      if (!('Notification' in window)) {
        // Notification isn't supported on this browser, disable or hide UI.
        console.debug('Notification not supported');
        return;
      }
      else {
        console.debug('Notification is supported');
      }

      if (Notification.permission === 'denied') {
        console.debug('Notification permission denied');
        return;
      }


      // register service worker
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('serviceworker-advanced_pwa_js', {scope: '/'})
          .then(function (registration) {
            // console.log('Registration successful, scope is:', registration.scope);
          })
          .catch(function (error) {
            // console.log('Service worker registration failed, error:', error);
          });

        // Then later, request a one-off sync:
        navigator.serviceWorker.ready.then(function (registration) {
          return registration.sync.register('synFirstSync');
        });
      }

      window.addEventListener('beforeinstallprompt', function (e) {
        e.userChoice.then(function (choiceResult) {
          // console.log(choiceResult.outcome);
          if (choiceResult.outcome === 'dismissed') {
            // console.log('User cancelled homescreen install');
          }
          else {
            // console.log('User added to homescreen');
          }
        });
      });


      window.addEventListener('appinstalled', (evt) => {
        app.logEvent('advanced_pwa', 'installed');
      });

      // To determine if the app was launched in standalone mode in non-Safari browsers.
      if (window.matchMedia('(display-mode: standalone)').matches) {
        // console.log('display-mode is standalone');
      }

      // To determine if the app was launched in standalone mode in Safari.
      if (window.navigator.standalone === true) {
        // console.log('display-mode is standalone');
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
              // console.log('Not subscribed to push service!');
              subscribeUser();
              return;
            }
            else {
              // We have a subscription, update the database
              // console.log('Subscription object: ', JSON.stringify(sub));
            }
          })
            .catch(function (e) {
              //  console.log('Error subscribing: ', e);
            });
        });
      }

      function subscribeUser() {
        // console.log('subscribeUser');
        const applicationServerKey = urlB64ToUint8Array(applicationServerPublicKey);

        navigator.serviceWorker.ready.then(function (registration) {
          registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: applicationServerKey
          }).then(function (sub) {
            // console.log('Endpoint URL: ', JSON.stringify(sub));
            return subscribeToBackEnd(sub);
          }).catch(function (e) {
            if (Notification.permission === 'denied') {
              // console.warn('Permission for notifications was denied');
            }
            else {
              // console.error('Unable to subscribe to push', e);
            }
          });
        });
      }

      function subscribeToBackEnd(subscription) {
        const key = subscription.getKey('p256dh');
        const token = subscription.getKey('auth');
        var subcribe_url = baseUrl + 'advanced_pwa/subscribe';
        // console.log('sendSubscriptionToBackEnd ', subscription);

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
          //  console.log('subscribeToBackEnd ', resp);
        }).then(function (data) {
          // console.log('subscribeToBackEnd ', data);
        }).catch(function (e) {
          // console.log('Unable to send subscription to backend:', e);
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
      var confirmationDialog = Drupal.dialog('<div class="advanced_pwa_message_div" style="display: none !important;">This site may send you push notifications.</div>', {
        title: Drupal.t('Allow website notifications?'),
        dialogClass: 'advanced_pwa-model-popup',
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
        create: function () {

        },
        beforeClose: false,
        close: function (event) {
          // Automatically destroy the DOM element that was used for the dialog.
          // $(event.target).remove();
        }
      });
        // Checking if the user is subcribed for notification, if not popup will appear.
      navigator.serviceWorker.ready.then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.getSubscription())
        .then(subscription => {
          if (status_all === 1) {
            if (!subscription) {
              // We aren't subscribed to push, so enable subscription.
              confirmationDialog.showModal();
              // return;.
            }
          }
          else {
            // console.log('notification feature disabled from configuration form');
          }
        })
        .then(subscription => subscription)
        .catch(e => {
          // console.error('Error when updating the subscription', e);
        });
    }
  };
})(jQuery, Drupal, drupalSettings);
