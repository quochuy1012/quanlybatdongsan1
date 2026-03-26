<?php
// Xác định base path
$base_path = '';
if (strpos($_SERVER['PHP_SELF'], '/tenant/') !== false || 
    strpos($_SERVER['PHP_SELF'], '/landlord/') !== false || 
    strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    $base_path = '../';
}

require_once $base_path . 'config/session.php';
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Bất Động Sản - Tìm nhà trọ, căn hộ'; ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php if (file_exists($base_path . 'config/firebase_config.php')): ?>
        <?php require_once $base_path . 'config/firebase_config.php'; ?>
        <script type="module">
            // Import Firebase SDK
            import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js';
            import { getAuth, GoogleAuthProvider } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
            
            // Firebase configuration
            const firebaseConfig = <?php echo json_encode(getFirebaseConfig()); ?>;
            
            // Initialize Firebase
            const app = initializeApp(firebaseConfig);
            const auth = getAuth(app);
            const provider = new GoogleAuthProvider();
            
            // Make auth and provider available globally
            window.firebaseAuth = auth;
            window.googleProvider = provider;
        </script>
    <?php endif; ?>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="<?php echo $base_path; ?>index.php">
                        <h1>🏠 Bất Động Sản</h1>
                    </a>
                </div>
                <nav class="nav">
                    <a href="<?php echo $base_path; ?>index.php">Trang chủ</a>
                    <?php if (isLoggedIn()): ?>
                        <?php if (hasRole('landlord')): ?>
                            <a href="<?php echo $base_path; ?>landlord/properties.php">Quản lý BĐS</a>
                            <a href="<?php echo $base_path; ?>landlord/appointments.php">Lịch hẹn</a>
                        <?php elseif (hasRole('admin')): ?>
                            <a href="<?php echo $base_path; ?>admin/dashboard.php">Quản trị</a>
                        <?php else: ?>
                            <a href="<?php echo $base_path; ?>tenant/appointments.php">Lịch hẹn của tôi</a>
                        <?php endif; ?>
                        <?php if (!hasRole('admin')): ?>
                            <a href="<?php echo $base_path; ?>chat.php">Hỗ trợ</a>
                        <?php endif; ?>
                        <a href="<?php echo $base_path; ?>profile.php"><?php echo htmlspecialchars($currentUser['full_name']); ?></a>
                        <a href="<?php echo $base_path; ?>logout.php">Đăng xuất</a>
                    <?php else: ?>
                        <a href="<?php echo $base_path; ?>login.php">Đăng nhập</a>
                        <a href="<?php echo $base_path; ?>register.php">Đăng ký</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
    <main class="main-content">

