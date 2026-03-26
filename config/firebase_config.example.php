<?php
// File mẫu cấu hình Firebase
// Copy file này thành firebase_config.php và điền thông tin từ Firebase Console

// Cấu hình Firebase
// Lấy thông tin này từ Firebase Console: https://console.firebase.google.com/
// Project Settings > General > Your apps > Web app

define('FIREBASE_API_KEY', 'YOUR_FIREBASE_API_KEY_HERE');
define('FIREBASE_AUTH_DOMAIN', 'your-project-id.firebaseapp.com');
define('FIREBASE_PROJECT_ID', 'your-project-id');
define('FIREBASE_STORAGE_BUCKET', 'your-project-id.appspot.com');
define('FIREBASE_MESSAGING_SENDER_ID', '123456789012');
define('FIREBASE_APP_ID', '1:123456789012:web:abcdef123456');

// Firebase Config Object (cho JavaScript)
function getFirebaseConfig() {
    return [
        'apiKey' => FIREBASE_API_KEY,
        'authDomain' => FIREBASE_AUTH_DOMAIN,
        'projectId' => FIREBASE_PROJECT_ID,
        'storageBucket' => FIREBASE_STORAGE_BUCKET,
        'messagingSenderId' => FIREBASE_MESSAGING_SENDER_ID,
        'appId' => FIREBASE_APP_ID
    ];
}
?>

