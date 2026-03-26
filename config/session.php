<?php
// Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Lấy thông tin user hiện tại
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }

    $user_id = (int)$_SESSION['user_id'];
    return dbSelectOne("SELECT * FROM users WHERE id = :id", [':id' => $user_id]);
}

// Kiểm tra quyền
function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

// Lấy base path
function getBasePath() {
    $script_path = $_SERVER['PHP_SELF'];
    if (strpos($script_path, '/tenant/') !== false || 
        strpos($script_path, '/landlord/') !== false || 
        strpos($script_path, '/admin/') !== false) {
        return '../';
    }
    return '';
}

// Redirect nếu chưa đăng nhập
function requireLogin() {
    if (!isLoggedIn()) {
        $base = getBasePath();
        header("Location: " . $base . "login.php");
        exit();
    }
}

// Redirect nếu không có quyền
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        $base = getBasePath();
        header("Location: " . $base . "index.php");
        exit();
    }
}

// Lấy trang trước đó an toàn để dùng cho nút "Quay lại"
function getBackUrl($fallback = 'index.php') {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if (!empty($ref)) {
        $refHost = parse_url($ref, PHP_URL_HOST);
        $curHost = $_SERVER['HTTP_HOST'] ?? '';
        if ($refHost === null || $refHost === $curHost) {
            return $ref;
        }
    }
    return $fallback;
}
?>

