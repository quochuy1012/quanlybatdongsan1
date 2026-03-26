<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

$success = '';
$error = '';

// Admin chỉ xem và quản lý, không thêm/sửa users

// Xử lý xóa user
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    if ($user_id > 0) {
        dbExecute("DELETE FROM users WHERE id = :id", [':id' => $user_id]);
        if (true) {
            $success = 'Xóa user thành công!';
        } else {
            $error = 'Xóa user thất bại!';
        }
    }
}

// Lấy danh sách users
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $search_param = "%$search%";
    $query .= " AND (full_name LIKE :q_name OR email LIKE :q_email OR phone LIKE :q_phone)";
    $params[':q_name'] = $search_param;
    $params[':q_email'] = $search_param;
    $params[':q_phone'] = $search_param;
}

if (!empty($role_filter)) {
    $query .= " AND role = :role";
    $params[':role'] = $role_filter;
}

$query .= " ORDER BY created_at DESC";
$users = dbSelectAll($query, $params);

$pageTitle = "Quản lý Users";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <div>
                <h1>👥 Quản lý Users</h1>
                <p style="margin: 0.5rem 0 0 0; color: #7f8c8d; font-size: 0.95rem;">
                    Xem và quản lý người dùng
                </p>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="content-card">
            <div class="search-filter-bar">
                <form method="GET" action="" style="display: flex; gap: 1rem; flex: 1;">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Tìm kiếm theo tên, email, SĐT..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <select name="role" class="filter-select">
                        <option value="">Tất cả vai trò</option>
                        <option value="tenant" <?php echo $role_filter === 'tenant' ? 'selected' : ''; ?>>Người thuê</option>
                        <option value="landlord" <?php echo $role_filter === 'landlord' ? 'selected' : ''; ?>>Người cho thuê</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                    <?php if ($search || $role_filter): ?>
                        <a href="users.php" class="btn btn-secondary">Xóa bộ lọc</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>SĐT</th>
                        <th>Vai trò</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <div class="empty-state-icon">👤</div>
                                <p>Không tìm thấy user nào</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                <td>
                                    <?php
                                    $role_badges = [
                                        'tenant' => '<span class="badge badge-info">Người thuê</span>',
                                        'landlord' => '<span class="badge badge-warning">Người cho thuê</span>',
                                        'admin' => '<span class="badge badge-danger">Admin</span>'
                                    ];
                                    echo $role_badges[$user['role']] ?? $user['role'];
                                    ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?delete=1&id=<?php echo $user['id']; ?>" 
                                           onclick="return confirm('Bạn có chắc muốn xóa user này?')"
                                           class="btn-sm btn-delete" title="Xóa">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<?php include '../includes/footer.php'; ?>

