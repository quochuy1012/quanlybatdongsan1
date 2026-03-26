<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

$success = '';
$error = '';

// Xử lý xóa landlord
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    if ($user_id > 0) {
        // Kiểm tra xem landlord có properties không
        $propertyCount = (int)dbScalar("SELECT COUNT(*) FROM properties WHERE landlord_id = :id", [':id' => $user_id]);
        
        if ($propertyCount > 0) {
            $error = 'Không thể xóa! Người cho thuê này đang có ' . $propertyCount . ' bất động sản.';
        } else {
            dbExecute("DELETE FROM users WHERE id = :id AND role = 'landlord'", [':id' => $user_id]);
            if (true) {
                $success = 'Xóa người cho thuê thành công!';
            } else {
                $error = 'Xóa thất bại!';
            }
        }
    }
}

// Lấy danh sách landlords
$search = $_GET['search'] ?? '';

$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM properties WHERE landlord_id = u.id) as property_count,
          (SELECT COUNT(*) FROM appointments WHERE landlord_id = u.id) as appointment_count
          FROM users u 
          WHERE u.role = 'landlord'";

$params = [];

if (!empty($search)) {
    $search_param = "%$search%";
    $query .= " AND (u.full_name LIKE :q_name OR u.email LIKE :q_email OR u.phone LIKE :q_phone)";
    $params[':q_name'] = $search_param;
    $params[':q_email'] = $search_param;
    $params[':q_phone'] = $search_param;
}

$query .= " ORDER BY u.created_at DESC";
$landlords = dbSelectAll($query, $params);

$pageTitle = "Quản lý Người cho thuê";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1>🏢 Quản lý Người cho thuê</h1>
            <button onclick="document.getElementById('addLandlordModal').style.display='block'" class="btn btn-primary">
                + Thêm Người cho thuê
            </button>
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
                    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                    <?php if ($search): ?>
                        <a href="landlords.php" class="btn btn-secondary">Xóa bộ lọc</a>
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
                        <th>Số BĐS</th>
                        <th>Số lịch hẹn</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($landlords)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <div class="empty-state-icon">👤</div>
                                <p>Không tìm thấy người cho thuê nào</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($landlords as $landlord): ?>
                            <tr>
                                <td><?php echo $landlord['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($landlord['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($landlord['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($landlord['phone'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo $landlord['property_count']; ?> BĐS</span>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?php echo $landlord['appointment_count']; ?> lịch hẹn</span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($landlord['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="landlord_edit.php?id=<?php echo $landlord['id']; ?>" class="btn-sm btn-edit">Sửa</a>
                                        <a href="landlord_view.php?id=<?php echo $landlord['id']; ?>" class="btn-sm btn-view">Xem</a>
                                        <a href="?delete=1&id=<?php echo $landlord['id']; ?>" 
                                           onclick="return confirm('Bạn có chắc muốn xóa người cho thuê này? Lưu ý: Chỉ có thể xóa khi không còn bất động sản nào.')"
                                           class="btn-sm btn-delete">Xóa</a>
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

<!-- Modal Thêm Landlord -->
<div id="addLandlordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>➕ Thêm Người cho thuê mới</h2>
            <button class="close" onclick="document.getElementById('addLandlordModal').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="landlord_add.php" id="addLandlordForm">
            <div class="form-group">
                <label>Họ và tên *</label>
                <input type="text" name="full_name" required placeholder="Nhập họ và tên">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Nhập email (tùy chọn)">
                <small style="color: #666; font-size: 0.85rem;">Cần ít nhất email hoặc số điện thoại</small>
            </div>
            <div class="form-group">
                <label>Số điện thoại</label>
                <input type="tel" name="phone" placeholder="Nhập số điện thoại (tùy chọn)">
                <small style="color: #666; font-size: 0.85rem;">Cần ít nhất email hoặc số điện thoại</small>
            </div>
            <div class="form-group">
                <label>Mật khẩu *</label>
                <input type="password" name="password" required minlength="6" placeholder="Tối thiểu 6 ký tự">
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary btn-block">Thêm Người cho thuê</button>
                <button type="button" class="btn btn-secondary btn-block" onclick="document.getElementById('addLandlordModal').style.display='none'">Hủy</button>
            </div>
        </form>
    </div>
</div>

<script>
// Đóng modal khi click outside
window.onclick = function(event) {
    const modal = document.getElementById('addLandlordModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Validate form trước khi submit
document.getElementById('addLandlordForm')?.addEventListener('submit', function(e) {
    const email = this.querySelector('input[name="email"]').value.trim();
    const phone = this.querySelector('input[name="phone"]').value.trim();
    
    if (!email && !phone) {
        e.preventDefault();
        alert('Vui lòng nhập ít nhất email hoặc số điện thoại!');
        return false;
    }
});
</script>

<?php include '../includes/footer.php'; ?>

