// firebase-messaging-sw.js
importScripts('https://www.gstatic.com/firebasejs/9.6.11/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.6.11/firebase-messaging-compat.js');

const firebaseConfig = {
    apiKey: "AIzaSyAiZfPUPzwrM24kxpCvvQ5pvUgQi4KiXbY",
    authDomain: "wafir-3dc48.firebaseapp.com",
    projectId: "wafir-3dc48",
    storageBucket: "wafir-3dc48.appspot.com",
    messagingSenderId: "<1040529370893",
    appId: "1:1040529370893:web:7031adb4f2a399075bca44",
};

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

messaging.onBackgroundMessage(function(payload) {
    console.log('Received background message: ', payload);
    const notificationTitle = payload.notification?.title || 'عنوان الإشعار';
    const notificationOptions = {
        body: payload.notification?.body || '',
    };
    self.registration.showNotification(notificationTitle, notificationOptions);
});
// /* global importScripts, firebase, self */

// importScripts('/firebase-config.js'); // يحمّل self._FIREBASE من الراوت
// importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js');
// importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js');
//
// // 1) تهيئة Firebase داخل الـSW
// firebase.initializeApp(self._FIREBASE.config);
//
// // 2) الحصول على messaging instance
// const messaging = firebase.messaging();
//
// // 3) التعامل مع رسائل الخلفية (app في background أو مغلق)
// // ملاحظة: في Web، payload المعتاد يحتوي notification:{title, body, icon} و data:{...}
// messaging.onBackgroundMessage((payload) => {
//     // نحاول استخراج العنوان والنص من notification
//     const notif = payload.notification || {};
//     const title = notif.title || 'إشعار';
//     const body  = notif.body  || '';
//     const icon  = notif.icon  || '/favicon.ico';
//
//     // نمرر أي بيانات إضافية إلى الإشعار لتسليمها إلى click handler
//     const data = payload.data || {};
//
//     // خيارات الإشعار
//     const options = {
//         body,
//         icon,
//         data,              // تُستخدم للتعامل مع click
//         badge: '/favicon.ico',
//         // tag: 'order-status',   // يمكنكِ وضع tag لتجميع الإشعارات
//         // renotify: true,
//     };
//
//     // عرض الإشعار
//     self.registration.showNotification(title, options);
// });
//
// // 4) عند النقر على الإشعار
// self.addEventListener('notificationclick', (event) => {
//     event.notification.close();
//     const data = event.notification.data || {};
//     // مثال: فتح صفحة معينة من موقعك حسب data.link
//     const link = data.link || '/';
//     event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
//         // إن كان تبويب مفتوح لنفس الرابط، فعّليه
//         for (const client of clientList) {
//             if ('focus' in client) {
//                 // ممكن تتحقق من client.url وتختاري
//                 return client.focus();
//             }
//         }
//         // وإلا افتحي تبويب جديد
//         if (clients.openWindow) {
//             return clients.openWindow(link);
//         }
//     }));
// });
//
// // 5) (اختياري) أحداث lifecycle
// self.addEventListener('install', (e) => {
//     // SW جاهز
//     self.skipWaiting();
// });
//
// self.addEventListener('activate', (e) => {
//     // تفعيل SW فورًا
//     self.clients.claim();
// });
