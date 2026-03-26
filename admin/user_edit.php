<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

if ($user_id <= 0) {
    header("Location: users.php");
    exit();
}

// Lấy thông tin user
$user = dbSelectOne("SELECT * FROM users WHERE id = :id", [':id' => $user_id]);

if (!$user) {
    header("Location: users.php");
    exit();
}

// Cập nhật user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'tenant';
    $password = $_POST['password'] ?? '';
    
    if (empty($full_name)) {
        $error = 'Họ và tên không được để trống!';
    } else {
        $dupeCount = (int)dbScalar(
            "SELECT COUNT(*) FROM users WHERE (email = :email OR phone = :phone) AND id <> :id",
            [
                ':email' => $email !== '' ? $email : null,
                ':phone' => $phone !== '' ? $phone : null,
                ':id' => $user_id,
            ]
        );

        if ($dupeCount > 0) {
            $error = 'Email hoặc số điện thoại đã được sử dụng!';
        } else {
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    dbExecute(
                        "UPDATE users
                         SET full_name = :full_name,
                             phone = :phone,
                             email = :email,
                             role = :role,
                             password = :password
                         WHERE id = :id",
                        [
                            ':full_name' => $full_name,
                            ':phone' => $phone !== '' ? $phone : null,
                            ':email' => $email !== '' ? $email : null,
                            ':role' => $role,
                            ':password' => $hashed_password,
                            ':id' => $user_id,
                        ]
                    );
                }
            } else {
                dbExecute(
                    "UPDATE users
                     SET full_name = :full_name,
                         phone = :phone,
                         email = :email,
                         role = :role
                     WHERE id = :id",
                    [
                        ':full_name' => $full_name,
                        ':phone' => $phone !== '' ? $phone : null,
                        ':email' => $email !== '' ? $email : null,
                        ':role' => $role,
                        ':id' => $user_id,
                    ]
                );
            }
            
            if (empty($error)) {
                $success = 'Cập nhật user thành công!';
                $user = array_merge($user, [
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'email' => $email,
                    'role' => $role
                ]);
            } else {
                $error = 'Cập nhật thất bại!';
            }
        }
    }
}

$pageTitle = "Sửa User";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1>✏️ Sửa User</h1>
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
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Vai trò *</label>
                    <select name="role" required>
                        <option value="tenant" <?php echo $user['role'] === 'tenant' ? 'selected' : ''; ?>>Người thuê</option>
                        <option value="landlord" <?php echo $user['role'] === 'landlord' ? 'selected' : ''; ?>>Người cho thuê</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mật khẩu mới (để trống nếu không đổi)</label>
                    <input type="password" name="password" minlength="6">
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                    <a href="../index.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

