#Push Notification for PWA
Модуль PWA Push створений спеціально для Progresive Web Aplication.
https://www.drupal.org/project/pwa (v. :dev8.x-1.1) 

##Вимоги
1) Модуль Progresive Web Aplication
2) Сайт повинен мати SSL сертифікат (HTTPS з'єднання)
3) php версії 7.0 та вище
4) minishlink/web-push library версії 4.0

##Встановлення та налаштування
1) Встановіть PWA (https://www.drupal.org/project/pwa)
2) Увімкніть та налаштуйте, задайте конфігурацію для маніфест файла, 
та сервіс воркера. Або використовуйте стандартні налаштування модуля.
3) Перевірте його роботу за допомогою Goggle Dev Tools. На вкладці 
Aplication - перевірте Manifest, Service Worker та вкладку Clear storage.
4) Встановіть бібліотеку для роботи пуш повідомлень за допомогою 
композера composer require minishlink/web-push:^4.0.
5) В модулі PWA створіть нову папку modules та завантажте в неї модуль
PWA Push. Увімкніть його.
6) Перевірте роботу бібліотеки на вашому сайті. Перейдіть за посиланням,
https://YourSiteName.com/admin/config/pwa/push-config, та натисніть 
кнопку генерації публічного та приватного ключів. Якщо ключі автоматично 
встановляться бібліотека працює коректно.
7) При ввімкненні модуля JS код для пуш повідомлень буде автоматично 
встановленно у модуль PWA. У файл pwa/js/serviceworker.js. Перевірте його
наявність, у разі його відсутності ви можете вручну закинути його. Він 
знаходиться за шляхом, pwa/modules/pwa_push/js/PushCode.js.
8) Перейдіть за цим (https://console.developers.google.com/apis/) 
посиланням та створіть новий проект. 
9) Перейдіть у вкладку "Облікові записи/Учётные данные/Credentials" та
створіть новий ключ API. Добавте його в налаштування модуля, де раніше ми
генеруввали ключі для бібліотеки.
10) Увімкніть чекбокс надсилання пуш-повідомлень

ГОЛОВНЕ: скоріше всього підчас налаштувань та увімкнення модулів та бібліотек
сторінки та скрипти будуть закешовані. Щоб 100% оновити весь кеш необхідно:
1) Чистимо кеш сайта 
2) Відкриваємо на нашому сайті Goggle Dev Tools > Aplication > Service Worker
натискаємо чекбокс offline та видаляємо встановленого сервіс воркера.
3) Goggle Dev Tools > Aplication > Clear storage та натиснути Clear site data.
4) Оновляємо сторінку за домогою очистки кеша та жосткого перезавантаження. 
Для цього (Goggle Dev Tools має бути відкритим), лівою кнопкою миші затискаємо 
кнопку перезавантаження в браузері та вибираємо "Очистка кеша та жостке 
перезавантаження".
5) Goggle Dev Tools > Aplication > Service Worker знімаємо чекбокс Offline,
та завантажуємо сторінку.

На цьому налаштування модуля можна вважати завершним. На інших вкладках ви
можете додати інші налаштування, наприклад надсилання при створенні нового 
матеріалу. 

##Деталі
1) Налаштування ключів API - 
https://YourSiteName.com/admin/config/pwa/push-config
2) Налаштування сповіщення про створення нових нод - 
https://YourSiteName.com/admin/config/pwa/subscription-config
3) Тестування пуш сповіщень - 
https://YourSiteName.com/admin/config/pwa/broadcast-config
4) Список підписаних на оновлення людей - 
https://YourSiteName.com/admin/config/pwa/subscription-list
5) Надсилання тестових пушів або пушів про створення нод здійснюється 
під час запуску крона!
6) Надсилання пушів знаходить у методі sendNotificationStart 
(pwa/modules/pwa_push/src/Model/SubscriptionsDatastorage.php) 
7) Формування даних у методі pwa_push_node_form_submit 
(pwa/modules/pwa_push/pwa_push.module) 


##Можливі помилки під чам встановлення
1) minishlink/web-push library має бути версії 4.0, а php => 7.0
2) minishlink/web-push краще всього встановлювати за допомогою composer
3) minishlink/web-push часом вимагає php бібліотеку.
