<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Xử lý authentication từ Firebase
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['idToken']) || !isset($input['user'])) {
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin xác thực']);
        exit();
    }
    
    $idToken = $input['idToken'];
    $firebaseUser = $input['user'];
    
    // Verify token với Firebase Admin SDK (trong production)
    // Ở đây tôi sẽ lưu thông tin user vào database
    
    $email = $firebaseUser['email'] ?? '';
    $displayName = $firebaseUser['displayName'] ?? '';
    $photoURL = $firebaseUser['photoURL'] ?? '';
    $uid = $firebaseUser['uid'] ?? '';
    $requestedRole = $input['role'] ?? 'tenant'; // Lấy role từ request
    $isRegister = $input['isRegister'] ?? false; // Kiểm tra có phải đăng ký không
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Email không hợp lệ']);
        exit();
    }
    
    // Validate role
    if (!in_array($requestedRole, ['tenant', 'landlord', 'admin'])) {
        $requestedRole = 'tenant'; // Mặc định là tenant nếu role không hợp lệ
    }

    // Kiểm tra user đã tồn tại chưa
    $user = dbSelectOne("SELECT * FROM users WHERE email = :email", [':email' => $email]);
    
    if ($user) {
        
        // Nếu là đăng ký và user đã tồn tại, báo lỗi
        if ($isRegister) {
            echo json_encode([
                'success' => false, 
                'error' => 'Email này đã được sử dụng. Vui lòng đăng nhập thay vì đăng ký.'
            ]);
            exit();
        }
        
        // Đăng nhập với user đã tồn tại
        // Cập nhật thông tin từ Firebase nếu cần
        if (!empty($displayName) && $user['full_name'] !== $displayName) {
            dbExecute(
                "UPDATE users SET full_name = :full_name WHERE id = :id",
                [':full_name' => $displayName, ':id' => (int)$user['id']]
            );
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['firebase_uid'] = $uid;
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'role' => $user['role'],
                'email' => $user['email']
            ],
            'redirect' => getRedirectUrl($user['role'])
        ]);
    } else {
        // User chưa tồn tại, tạo mới với role được chọn
        $defaultPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        dbExecute(
            "INSERT INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, :role)",
            [
                ':full_name' => $displayName,
                ':email' => $email,
                ':password' => $defaultPassword,
                ':role' => $requestedRole,
            ]
        );
        $newUserId = dbScopeIdentity();
        
        if ($newUserId > 0) {
            
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_role'] = $requestedRole;
            $_SESSION['user_name'] = $displayName;
            $_SESSION['firebase_uid'] = $uid;
            
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $newUserId,
                    'name' => $displayName,
                    'role' => $requestedRole,
                    'email' => $email
                ],
                'redirect' => getRedirectUrl($requestedRole),
                'new_user' => true
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Không thể tạo tài khoản']);
        }
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

function getRedirectUrl($role) {
    if ($role === 'admin') {
        return 'admin/dashboard.php';
    } elseif ($role === 'landlord') {
        return 'landlord/properties.php';
    } else {
        return 'index.php';
    }
}
?>

