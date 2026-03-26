<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($full_name) || empty($password) || (empty($phone) && empty($email))) {
        $error = 'Vui lòng nhập đầy đủ thông tin! (Cần ít nhất email hoặc số điện thoại)';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } else {
        // Kiểm tra trùng email (nếu có)
        $email_exists = false;
        if (!empty($email)) {
            $email_exists = (int)dbScalar(
                "SELECT COUNT(*) FROM users WHERE email = :email AND email <> ''",
                [':email' => $email]
            ) > 0;
        }
        
        // Kiểm tra trùng phone (nếu có)
        $phone_exists = false;
        if (!empty($phone)) {
            $phone_exists = (int)dbScalar(
                "SELECT COUNT(*) FROM users WHERE phone = :phone AND phone <> ''",
                [':phone' => $phone]
            ) > 0;
        }
        
        if ($email_exists) {
            $error = 'Email đã được sử dụng!';
        } elseif ($phone_exists) {
            $error = 'Số điện thoại đã được sử dụng!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'landlord';
            
            dbExecute(
                "INSERT INTO users (full_name, phone, email, password, role)
                 VALUES (:full_name, :phone, :email, :password, :role)",
                [
                    ':full_name' => $full_name,
                    ':phone' => $phone !== '' ? $phone : null,
                    ':email' => $email !== '' ? $email : null,
                    ':password' => $hashed_password,
                    ':role' => $role,
                ]
            );
            
            if (true) {
                $success = 'Thêm người cho thuê thành công!';
                header("refresh:2;url=landlords.php");
            } else {
                $error = 'Thêm người cho thuê thất bại!';
            }
        }
    }
}

$pageTitle = "Thêm Người cho thuê";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1>➕ Thêm Người cho thuê</h1>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="content-card">
            <form method="POST" action="">
                <div class="form-group">
                    <label>Họ và tên *</label>
                    <input type="text" name="full_name" required placeholder="Nhập họ và tên" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Nhập email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="tel" name="phone" placeholder="Nhập số điện thoại" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Mật khẩu *</label>
                    <input type="password" name="password" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Thêm Người cho thuê</button>
                    <a href="../index.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

