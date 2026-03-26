<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Nếu đã đăng nhập, chuyển về trang chủ
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$pageTitle = "Đăng nhập bằng Gmail";
include 'includes/header.php';

// Kiểm tra Firebase config
$firebaseEnabled = file_exists('config/firebase_config.php');
?>

<div class="container">
    <div class="form-container">
        <h2>📧 Đăng nhập bằng Gmail</h2>
        
        <?php if (!$firebaseEnabled): ?>
            <div class="alert alert-error">
                <strong>Lưu ý:</strong> Firebase chưa được cấu hình. Vui lòng cấu hình Firebase trong file <code>config/firebase_config.php</code>
            </div>
        <?php endif; ?>
        
        <div id="errorMessage" class="alert alert-error" style="display: none;"></div>
        <div id="successMessage" class="alert alert-success" style="display: none;"></div>
        
        <div style="background: #e8f5e9; padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem; text-align: center;">
            <p style="margin: 0; color: #666;">
                <strong>Đăng nhập nhanh với Google</strong><br>
                Click nút bên dưới để đăng nhập bằng tài khoản Google của bạn
            </p>
        </div>
        
        <button id="googleSignInBtn" class="btn btn-secondary btn-block" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; background: #fff; color: #333; border: 1px solid #ddd; padding: 1rem; font-size: 1.1rem;" <?php echo !$firebaseEnabled ? 'disabled' : ''; ?>>
            <svg width="24" height="24" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Đăng nhập với Google
        </button>
        
        <div id="loadingIndicator" style="display: none; text-align: center; margin-top: 1rem;">
            <p style="color: #666;">Đang xử lý đăng nhập...</p>
        </div>
        
        <div style="margin-top: 1.5rem; text-align: center;">
            <a href="index.php" style="color: #666; text-decoration: none;">Quay lại</a>
        </div>
        
        <p style="text-align: center; margin-top: 1rem; font-size: 0.9rem; color: #666;">
            Chưa có tài khoản? Đăng nhập bằng Google sẽ tự động tạo tài khoản cho bạn
        </p>
    </div>
</div>

<?php if ($firebaseEnabled): ?>
<script type="module">
    import { signInWithPopup } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js';
    
    const googleSignInBtn = document.getElementById('googleSignInBtn');
    const errorMessage = document.getElementById('errorMessage');
    const successMessage = document.getElementById('successMessage');
    const loadingIndicator = document.getElementById('loadingIndicator');
    
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
            } else {
                errorMsg += error.message || 'Vui lòng thử lại.';
            }
            
            showError(errorMsg);
            loadingIndicator.style.display = 'none';
            googleSignInBtn.disabled = false;
        }
    });
</script>
<?php endif; ?>

<script>
// Xử lý lỗi console
window.addEventListener('error', function(e) {
    if (e.filename && (e.filename.includes('extension') || e.filename.includes('onboarding'))) {
        e.preventDefault();
        return false;
    }
});

window.addEventListener('unhandledrejection', function(e) {
    if (e.reason && typeof e.reason === 'string' && e.reason.includes('onboarding')) {
        e.preventDefault();
        return false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>
