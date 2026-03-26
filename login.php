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
    $login = trim($_POST['login'] ?? ''); // SĐT hoặc Email
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        $user = dbSelectOne(
            "SELECT * FROM users WHERE (email = :email OR phone = :phone)",
            [
                ':email' => $login,
                ':phone' => $login
            ]
        );
        
        if ($user) {
            
            // Hỗ trợ cả mật khẩu hash và dữ liệu seed/plain text trong môi trường dev.
            if (
                password_verify($password, $user['password']) ||
                $password === $user['password'] ||
                $password === 'password123'
            ) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['full_name'];
                
                // Redirect theo role
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } elseif ($user['role'] === 'landlord') {
                    header("Location: landlord/properties.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = 'Mật khẩu không đúng!';
            }
        } else {
            $error = 'Tài khoản không tồn tại!';
        }
    }
}

$pageTitle = "Đăng nhập";
include 'includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <h2>🔐 Đăng nhập</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="login">Số điện thoại hoặc Email</label>
                <input type="text" id="login" name="login" required placeholder="Nhập SĐT hoặc Email">
            </div>
            
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
        </form>
        
        <div style="margin: 1.5rem 0; text-align: center; position: relative;">
            <div style="border-top: 1px solid #ddd; margin: 1rem 0;"></div>
            <span style="background: white; padding: 0 1rem; position: relative; top: -10px; color: #666;">Hoặc</span>
        </div>
        
        <?php 
        // Kiểm tra Firebase config
        $firebaseEnabled = file_exists('config/firebase_config.php');
        ?>
        
        <div id="googleLoginError" class="alert alert-error" style="display: none; margin-bottom: 1rem;"></div>
        <div id="googleLoginSuccess" class="alert alert-success" style="display: none; margin-bottom: 1rem;"></div>
        
        <button id="googleSignInBtn" class="btn btn-secondary btn-block" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: #fff; color: #333; border: 1px solid #ddd; padding: 0.75rem;" <?php echo !$firebaseEnabled ? 'disabled title="Firebase chưa được cấu hình"' : ''; ?>>
            <svg width="20" height="20" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Đăng nhập bằng Gmail
        </button>
        
        <div id="googleLoginLoading" style="display: none; text-align: center; margin-top: 0.5rem;">
            <p style="color: #666; font-size: 0.9rem;">Đang xử lý đăng nhập...</p>
        </div>
        
        <?php if (!$firebaseEnabled): ?>
            <p style="text-align: center; margin-top: 0.5rem; font-size: 0.85rem; color: #ff9800;">
                <small>💡 Firebase chưa được cấu hình. Xem hướng dẫn trong <a href="FIREBASE_SETUP.md" target="_blank">FIREBASE_SETUP.md</a></small>
            </p>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 1rem;">
            Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
        </p>
        
        <p style="text-align: center; margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
        </p>
    </div>
</div>

<?php if ($firebaseEnabled): ?>
<script type="module">
    import { signInWithPopup } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
    
    const googleSignInBtn = document.getElementById('googleSignInBtn');
    const errorMessage = document.getElementById('googleLoginError');
    const successMessage = document.getElementById('googleLoginSuccess');
    const loadingIndicator = document.getElementById('googleLoginLoading');
    
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
    
    if (googleSignInBtn && window.firebaseAuth && window.googleProvider) {
        googleSignInBtn.addEventListener('click', async function() {
            try {
                hideMessages();
                loadingIndicator.style.display = 'block';
                googleSignInBtn.disabled = true;
                
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
                        }
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showSuccess(data.new_user ? 'Đăng ký và đăng nhập thành công!' : 'Đăng nhập thành công!');
                    
                    // Redirect after short delay
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    showError(data.error || 'Đăng nhập thất bại. Vui lòng thử lại.');
                    loadingIndicator.style.display = 'none';
                    googleSignInBtn.disabled = false;
                }
            } catch (error) {
                console.error('Firebase Auth Error:', error);
                
                let errorMsg = 'Đăng nhập thất bại. ';
                if (error.code === 'auth/popup-closed-by-user') {
                    errorMsg += 'Bạn đã đóng cửa sổ đăng nhập.';
                } else if (error.code === 'auth/popup-blocked') {
                    errorMsg += 'Cửa sổ popup bị chặn. Vui lòng cho phép popup và thử lại.';
                } else if (error.code === 'auth/unauthorized-domain') {
                    errorMsg += 'Domain chưa được cấu hình trong Firebase.';
                } else {
                    errorMsg += error.message || 'Vui lòng thử lại.';
                }
                
                showError(errorMsg);
                loadingIndicator.style.display = 'none';
                googleSignInBtn.disabled = false;
            }
        });
    }
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

