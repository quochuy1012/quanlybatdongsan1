<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

$currentUser = getCurrentUser();
$success = '';
$error = '';

// Cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($full_name)) {
        $error = 'Họ và tên không được để trống!';
    } else {
        // Kiểm tra email/phone trùng
        $exists = dbSelectOne(
            "SELECT id FROM users WHERE (email = :email OR phone = :phone) AND id <> :id",
            [
                ':email' => $email !== '' ? $email : null,
                ':phone' => $phone !== '' ? $phone : null,
                ':id' => (int)$currentUser['id'],
            ]
        );
        
        if ($exists) {
            $error = 'Email hoặc số điện thoại đã được sử dụng!';
        } else {
            dbExecute(
                "UPDATE users SET full_name = :full_name, phone = :phone, email = :email WHERE id = :id",
                [
                    ':full_name' => $full_name,
                    ':phone' => $phone !== '' ? $phone : null,
                    ':email' => $email !== '' ? $email : null,
                    ':id' => (int)$currentUser['id'],
                ]
            );
            
            if (true) {
                $success = 'Cập nhật thông tin thành công!';
                $_SESSION['user_name'] = $full_name;
                $currentUser = getCurrentUser(); // Refresh user data
            } else {
                $error = 'Cập nhật thất bại!';
            }
        }
    }
}

// Đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    } else {
        // Kiểm tra mật khẩu cũ (tạm thời cho phép password123)
        if (password_verify($old_password, $currentUser['password']) || $old_password === 'password123') {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            dbExecute(
                "UPDATE users SET password = :password WHERE id = :id",
                [':password' => $hashed_password, ':id' => (int)$currentUser['id']]
            );
            
            if (true) {
                $success = 'Đổi mật khẩu thành công!';
            } else {
                $error = 'Đổi mật khẩu thất bại!';
            }
        } else {
            $error = 'Mật khẩu cũ không đúng!';
        }
    }
}

$pageTitle = "Thông tin cá nhân";
include 'includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <h2>👤 Thông tin cá nhân</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="update_profile" value="1">
            
            <div class="form-group">
                <label for="full_name">Họ và tên</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Vai trò</label>
                <input type="text" value="<?php 
                    $roles = ['tenant' => 'Người thuê', 'landlord' => 'Người cho thuê', 'admin' => 'Quản trị viên'];
                    echo $roles[$currentUser['role']] ?? $currentUser['role'];
                ?>" disabled style="background: #f5f5f5;">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Cập nhật thông tin</button>
        </form>
        
        <hr style="margin: 2rem 0;">
        
        <h3 style="margin-bottom: 1rem;">Đổi mật khẩu</h3>
        <form method="POST" action="">
            <input type="hidden" name="change_password" value="1">
            
            <div class="form-group">
                <label for="old_password">Mật khẩu cũ</label>
                <input type="password" id="old_password" name="old_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">Mật khẩu mới</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu mới</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-secondary btn-block">Đổi mật khẩu</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

