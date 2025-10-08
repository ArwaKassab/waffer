// public/firebase-messaging-sw.js
importScripts('/firebase-config.js');
importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js');

firebase.initializeApp(self._FIREBASE.config);
const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    const title = payload.data?.title || payload.notification?.title || 'Waffer';
    const options = {
        body: payload.data?.body || payload.notification?.body || '',
        data: payload.data || {},
    };
    self.registration.showNotification(title, options);
});
