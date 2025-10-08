// public/firebase-messaging-sw.js
importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyDT12lG2vjiUMRJqy21MVzxTh-Q1maK4zU",
    authDomain: "waffer-fa237.firebaseapp.com",
    projectId: "waffer-fa237",
    messagingSenderId: "467018159330",
    appId: "1:467018159330:web:df3e5a93a5e3df4a2b7abb",
    vapidKey: "BP_t5Y8xs86HzdiPt9B8nYvAoXxsQeFJJ6aRvpigoQJbgAyY3og9-VXbJhOMNxmJkGkDoDni-Udp7iyL6Bq2K60"


});

// لازم يكون في sw موجود حتى FCM يقدر يستقبل رسائل الخلفية
const messaging = firebase.messaging();

// استلام الرسائل بالخلفية (Background)
messaging.onBackgroundMessage((payload) => {
    // نعرض إشعار نظامي
    const title = payload.notification?.title || 'New notification';
    const options = {
        body: payload.notification?.body || '',
        data: payload.data || {},
    };
    self.registration.showNotification(title, options);
});
