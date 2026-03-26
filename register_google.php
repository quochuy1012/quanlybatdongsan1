<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Nếu đã đăng nhập, chuyển về trang chủ
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'tenant';
    
    if (empty($full_name) || empty($email)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ!';
    } elseif (strpos($email, '@gmail.com') === false && strpos($email, '@googlemail.com') === false) {
        $error = 'Vui lòng sử dụng email Gmail!';
    } else {
        // Kiểm tra email đã tồn tại
        $exists = dbSelectOne("SELECT id FROM users WHERE email = :email", [':email' => $email]);
        
        if ($exists) {
            $error = 'Email Gmail này đã được sử dụng! Vui lòng <a href="login_google.php">đăng nhập</a> thay vì đăng ký.';
        } else {
            // Tạo mật khẩu mặc định (trong thực tế, với OAuth sẽ không cần password)
            // Người dùng có thể đổi mật khẩu sau
            $default_password = bin2hex(random_bytes(16)); // Mật khẩu ngẫu nhiên
            $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

            dbExecute(
                "INSERT INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, :role)",
                [
                    ':full_name' => $full_name,
                    ':email' => $email,
                    ':password' => $hashed_password,
                    ':role' => $role,
                ]
            );
            $user_id = dbScopeIdentity();
            
            if ($user_id > 0) {
                // Tự động đăng nhập sau khi đăng ký
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_role'] = $role;
                $_SESSION['user_name'] = $full_name;
                
                $success = 'Đăng ký thành công! Bạn đã được đăng nhập tự động.';
                header("refresh:2;url=index.php");
            } else {
                $error = 'Đăng ký thất bại! Vui lòng thử lại.';
            }
        }
    }
}

$pageTitle = "Đăng ký bằng Gmail";
include 'includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <h2>📧 Đăng ký bằng Gmail</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div style="background: #e8f5e9; padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem;">
            <p style="margin: 0; color: #666; font-size: 0.9rem;">
                <strong>Lưu ý:</strong> Bạn cần sử dụng email Gmail để đăng ký. Sau khi đăng ký, bạn sẽ được đăng nhập tự động.
            </p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Họ và tên *</label>
                <input type="text" id="full_name" name="full_name" required placeholder="Nhập họ và tên">
            </div>
            
            <div class="form-group">
                <label for="email">Email Gmail *</label>
                <input type="email" id="email" name="email" required placeholder="yourname@gmail.com" 
                       pattern="[a-z0-9._%+-]+@(gmail|googlemail)\.com$" 
                       title="Vui lòng nhập email Gmail hợp lệ">
            </div>
            
            <div class="form-group">
                <label for="role">Bạn là *</label>
                <select id="role" name="role" required>
                    <option value="tenant">Người thuê</option>
                    <option value="landlord">Người cho thuê</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Đăng ký bằng Gmail</button>
        </form>
        
        <div style="background: #fff3cd; padding: 1rem; border-radius: 5px; margin: 1.5rem 0; border-left: 4px solid #ffc107;">
            <p style="margin: 0; color: #856404; font-size: 0.9rem;">
                <strong>💡 Gợi ý:</strong> Bạn có thể đăng nhập bằng Google để tự động tạo tài khoản. 
                <a href="login_google.php" style="color: #0066cc;">Đăng nhập bằng Google ngay</a>
            </p>
        </div>
        
        <div style="margin-top: 1.5rem; text-align: center;">
            <a href="index.php" style="color: #666; text-decoration: none;">Quay lại</a>
        </div>
        
        <p style="text-align: center; margin-top: 1rem; font-size: 0.9rem; color: #666;">
            Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

