# Push Notification for PWA
Push Notifications submodule for PWA on Drupal 8
https://www.drupal.org/project/pwa (dev8.x-1.1) 

## Requirements
1) php version 7.0 or higher
2) minishlink/web-push library version 4.0
3) Site domain should be SSL enabled. Push notifications only works 
on SSL enabled domains
4) Progresive Web Application module

## Installation and configurations
1) Install PWA (https://www.drupal.org/project/pwa)
2) Enable it and configure manifest, and serviceworker.
3) Look at how work module use Goggle Dev Tools. On tab Aplication check 
Manifest, Service Worker and Clear storage subtabs.
4) Install library for push messages. From your sites root folder run 'composer
require minishlink/web-push:^4.0'.
5) Go to PWA folder, and create new directory with name: modules. 
Place PWA Push inside. Enable it, use "drush en pwa_push" or admin GUI.
6) Go to https://YourSiteName.com/admin/config/pwa/push-config, push button 
"Generate keys", if you don't have error, and keys was generated, then web-push
library installed correctly. 
6.1) Note: Click on 'Generate keys' before uploading notification icon.
7) During PWA Push instalation JS code for push notification will be 
automatically placed in your serviceworker file (pwa/js/serviceworker.js).
Look if JS not exist you will must replace it manually 
from pwa/modules/pwa_push/js/PushCode.js.
7.1) !Note: The PushCode.JS code will not be deleted automatically.
8) Go to https://console.developers.google.com/apis/ and create new project.
9) Go to Credentials tab and create new API key. You must write this key in 
GCM field. (Go to https://YourSiteName.com/admin/config/pwa/push-config)
10) Select checkbox for push notification.

NOTE: How clear cache during PWA and PWA Push configurations?
1) Clear drupal cache (drush cr, drush cc) or use GUI
2) Go to Goggle Dev Tools > Aplication > Service Worker select
Offline checkbox and delete our instaled service worker.
3) Goggle Dev Tools > Aplication > Clear storage and push
Clear site data button.
4) Refresh page use "Empty Cache & Hard Reload". Read how to do it:
https://www.thewindowsclub.com/empty-cache-hard-reload-chrome
5) Go to Goggle Dev Tools > Aplication > Service Worker uncheck
offline and reload page.

That's all.


## Details
1) Settings for keys API - 
https://YourSiteName.com/admin/config/pwa/push-config
2) 'Push Notification Subscription' 
('/admin/config/advanced_pwa/config-subscription')
config page will allow you to select content types. Push notification will be 
sent to subscribed users whenever content of the selected content type is
published.
3) 'Push Notification Subscription List' 
(/admin/config/advanced_pwa/subscription-list) page will show list of 
subscribed users.
4) You can send generic notification message from 'Broadcast Push Notification' 
(/admin/config/advanced_pwa/config-broadcast) page.
5) Sending push messages in sendNotificationStart method 
(pwa/modules/pwa_push/src/Model/SubscriptionsDatastorage.php) 
6) Message data creating in pwa_push_node_form_submit method 
(pwa/modules/pwa_push/pwa_push.module) 

## Troubleshooting
Check if following requirements are met:
1) The current module works only with web-push library version 4.0
2) Web-push library 4.0 needs php version 7.0 or higher
