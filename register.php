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
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'tenant';
    
    if (empty($full_name) || empty($password) || (empty($phone) && empty($email))) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } else {
        // Kiểm tra email hoặc phone đã tồn tại
        $exists = dbSelectOne(
            "SELECT id FROM users WHERE email = :email OR phone = :phone",
            [':email' => $email, ':phone' => $phone]
        );
        
        if ($exists) {
            $error = 'Email hoặc số điện thoại đã được sử dụng!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            dbExecute(
                "INSERT INTO users (full_name, phone, email, password, role) VALUES (:full_name, :phone, :email, :password, :role)",
                [
                    ':full_name' => $full_name,
                    ':phone' => $phone !== '' ? $phone : null,
                    ':email' => $email !== '' ? $email : null,
                    ':password' => $hashed_password,
                    ':role' => $role,
                ]
            );

            if (true) {
                $success = 'Đăng ký thành công! Vui lòng đăng nhập.';
                header("refresh:2;url=login.php");
            } else {
                $error = 'Đăng ký thất bại! Vui lòng thử lại.';
            }
        }
    }
}

$pageTitle = "Đăng ký";
include 'includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <h2>📝 Đăng ký tài khoản</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Họ và tên *</label>
                <input type="text" id="full_name" name="full_name" required placeholder="Nhập họ và tên">
            </div>
            
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="tel" id="phone" name="phone" placeholder="Nhập số điện thoại">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Nhập email">
            </div>
            
            <div class="form-group">
                <label for="role">Bạn là *</label>
                <select id="role" name="role" required>
                    <option value="tenant">Người thuê</option>
                    <option value="landlord">Người cho thuê</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Mật khẩu *</label>
                <input type="password" id="password" name="password" required placeholder="Tối thiểu 6 ký tự">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu *</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Nhập lại mật khẩu">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Đăng ký</button>
        </form>
        
        <div style="margin: 1.5rem 0; text-align: center; position: relative;">
            <div style="border-top: 1px solid #ddd; margin: 1rem 0;"></div>
            <span style="background: white; padding: 0 1rem; position: relative; top: -10px; color: #666;">Hoặc</span>
        </div>
        
        <?php 
        // Kiểm tra Firebase config
        $firebaseEnabled = file_exists('config/firebase_config.php');
        ?>
        
        <div id="googleRegisterError" class="alert alert-error" style="display: none; margin-bottom: 1rem;"></div>
        <div id="googleRegisterSuccess" class="alert alert-success" style="display: none; margin-bottom: 1rem;"></div>
        
        <div id="googleRegisterRoleSelection" style="margin-bottom: 1rem;">
            <div class="form-group">
                <label for="google_role">Bạn là (cho đăng ký bằng Google) *</label>
                <select id="google_role" name="google_role" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem;">
                    <option value="">-- Chọn vai trò --</option>
                    <option value="tenant">Người thuê</option>
                    <option value="landlord">Người cho thuê</option>
                </select>
            </div>
        </div>
        
        <button id="googleSignUpBtn" class="btn btn-secondary btn-block" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: #fff; color: #333; border: 1px solid #ddd; padding: 0.75rem; margin-bottom: 1rem;" <?php echo !$firebaseEnabled ? 'disabled title="Firebase chưa được cấu hình"' : ''; ?>>
            <svg width="20" height="20" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Đăng ký bằng Gmail
        </button>
        
        <div id="googleRegisterLoading" style="display: none; text-align: center; margin-bottom: 1rem;">
            <p style="color: #666; font-size: 0.9rem;">Đang xử lý đăng ký...</p>
        </div>
        
        <?php if (!$firebaseEnabled): ?>
            <p style="text-align: center; margin-bottom: 1rem; font-size: 0.85rem; color: #ff9800;">
                <small>💡 Firebase chưa được cấu hình. Xem hướng dẫn trong <a href="FIREBASE_SETUP.md" target="_blank">FIREBASE_SETUP.md</a></small>
            </p>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 1rem;">
            Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
        </p>
    </div>
</div>

<?php if ($firebaseEnabled): ?>
<script type="module">
    import { signInWithPopup } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
    
    const googleSignUpBtn = document.getElementById('googleSignUpBtn');
    const errorMessage = document.getElementById('googleRegisterError');
    const successMessage = document.getElementById('googleRegisterSuccess');
    const loadingIndicator = document.getElementById('googleRegisterLoading');
    
    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.style.display = 'block';
        successMessage.style.display = 'none';
    }
    
    function showSuccess(message) {
        successMessage.textContent = message;
        successMessage.style.display = 'block';
        errorMessage.style.display = 'none';
    }
    
    function hideMessages() {
        errorMessage.style.display = 'none';
        successMessage.style.display = 'none';
    }
    
    if (googleSignUpBtn && window.firebaseAuth && window.googleProvider) {
        googleSignUpBtn.addEventListener('click', async function() {
            try {
                hideMessages();
                
                // Kiểm tra role đã được chọn chưa
                const roleSelect = document.getElementById('google_role');
                const selectedRole = roleSelect ? roleSelect.value : '';
                
                if (!selectedRole) {
                    showError('Vui lòng chọn vai trò của bạn trước khi đăng ký!');
                    return;
                }
                
                loadingIndicator.style.display = 'block';
                googleSignUpBtn.disabled = true;
                
                // Sign in with Google
                const result = await signInWithPopup(window.firebaseAuth, window.googleProvider);
                const user = result.user;
                
                // Get ID token
                const idToken = await user.getIdToken();
                
                // Send to server for verification and session creation
                const response = await fetch('firebase_auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        idToken: idToken,
                        user: {
                            uid: user.uid,
                            email: user.email,
                            displayName: user.displayName,
                            photoURL: user.photoURL
                        },
                        role: selectedRole,
                        isRegister: true
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess(data.new_user ? 'Đăng ký và đăng nhập thành công!' : 'Tài khoản đã tồn tại. Đăng nhập thành công!');
                    
                    // Redirect after short delay
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    showError(data.error || 'Đăng ký thất bại. Vui lòng thử lại.');
                    loadingIndicator.style.display = 'none';
                    googleSignUpBtn.disabled = false;
                }
            } catch (error) {
                console.error('Firebase Auth Error:', error);
                
                let errorMsg = 'Đăng ký thất bại. ';
                if (error.code === 'auth/popup-closed-by-user') {
                    errorMsg += 'Bạn đã đóng cửa sổ đăng ký.';
                } else if (error.code === 'auth/popup-blocked') {
                    errorMsg += 'Cửa sổ popup bị chặn. Vui lòng cho phép popup và thử lại.';
                } else if (error.code === 'auth/unauthorized-domain') {
                    errorMsg += 'Domain chưa được cấu hình trong Firebase.';
                } else {
                    errorMsg += error.message || 'Vui lòng thử lại.';
                }
                
                showError(errorMsg);
                loadingIndicator.style.display = 'none';
                googleSignUpBtn.disabled = false;
            }
        });
    }
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

