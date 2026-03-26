<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

$success = '';
$error = '';

// Xử lý duyệt/từ chối/xóa appointment
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appointment_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($appointment_id > 0) {
        if ($action === 'approve') {
            // Duyệt lịch hẹn - chuyển từ pending sang confirmed
            dbExecute("UPDATE appointments SET status = 'confirmed' WHERE id = :id", [':id' => $appointment_id]);
            if (true) {
                $success = 'Duyệt lịch hẹn thành công!';
            } else {
                $error = 'Duyệt thất bại!';
            }
        } elseif ($action === 'reject') {
            // Từ chối - xóa lịch hẹn
            dbExecute("DELETE FROM appointments WHERE id = :id", [':id' => $appointment_id]);
            if (true) {
                $success = 'Đã từ chối và xóa lịch hẹn!';
            } else {
                $error = 'Xóa thất bại!';
            }
        } elseif ($action === 'delete') {
            // Xóa lịch hẹn
            dbExecute("DELETE FROM appointments WHERE id = :id", [':id' => $appointment_id]);
            if (true) {
                $success = 'Xóa lịch hẹn thành công!';
            } else {
                $error = 'Xóa thất bại!';
            }
        }
    }
}

// Lấy danh sách appointments
$status_filter = $_GET['status'] ?? '';
$landlord_filter = isset($_GET['landlord_id']) ? (int)$_GET['landlord_id'] : 0;

$query = "SELECT a.*, p.title as property_title, p.address, p.district, 
          u1.full_name as tenant_name, u1.phone as tenant_phone, u1.email as tenant_email,
          u2.full_name as landlord_name 
          FROM appointments a 
          JOIN properties p ON a.property_id = p.id 
          JOIN users u1 ON a.tenant_id = u1.id 
          JOIN users u2 ON a.landlord_id = u2.id 
          WHERE 1=1";

$params = [];

if (!empty($status_filter)) {
    $query .= " AND a.status = :status";
    $params[':status'] = $status_filter;
}

if ($landlord_filter > 0) {
    $query .= " AND a.landlord_id = :landlord_id";
    $params[':landlord_id'] = $landlord_filter;
}

$query .= " ORDER BY a.appointment_date DESC";
$appointments = dbSelectAll($query, $params);

// Lấy danh sách landlords cho filter (trước khi đóng kết nối)
$landlords_list = dbSelectAll("SELECT id, full_name FROM users WHERE role = 'landlord' ORDER BY full_name");

$pageTitle = "Quản lý Lịch hẹn";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <div>
                <h1>📅 Quản lý Lịch hẹn</h1>
                <p style="margin: 0.5rem 0 0 0; color: #7f8c8d; font-size: 0.95rem;">
                    Duyệt và quản lý lịch hẹn
                </p>
            </div>
            <?php if ($status_filter === 'pending' || empty($status_filter)): ?>
                <div>
                    <a href="appointments.php?status=pending" class="btn btn-primary" style="background: #2196F3;">
                        ⏳ Xem chờ duyệt (<?php 
                            echo (int)dbScalar("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
                        ?>)
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="content-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <div class="search-filter-bar" style="flex: 1; min-width: 300px;">
                    <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <?php
                        // Sử dụng danh sách landlords đã lấy ở trên
                        ?>
                        <select name="landlord_id" class="filter-select">
                            <option value="">Tất cả người cho thuê</option>
                            <?php foreach ($landlords_list as $ll): ?>
                                <option value="<?php echo $ll['id']; ?>" <?php echo $landlord_filter == $ll['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ll['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status" class="filter-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Lọc</button>
                        <?php if ($status_filter || $landlord_filter): ?>
                            <a href="appointments.php" class="btn btn-secondary">Xóa bộ lọc</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Bất động sản</th>
                        <th>Người thuê</th>
                        <th>Người cho thuê</th>
                        <th>Ngày giờ hẹn</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <div class="empty-state-icon">📅</div>
                                <p>Không có lịch hẹn nào</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $apt): ?>
                            <tr>
                                <td><?php echo $apt['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($apt['property_title']); ?></strong><br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($apt['district']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($apt['tenant_name']); ?><br>
                                    <?php if ($apt['tenant_phone']): ?>
                                        <small style="color: #666;">📞 <?php echo htmlspecialchars($apt['tenant_phone']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($apt['landlord_name']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($apt['appointment_date'])); ?></td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'pending' => '<span class="badge badge-warning">Chờ xác nhận</span>',
                                        'confirmed' => '<span class="badge badge-success">Đã xác nhận</span>',
                                        'cancelled' => '<span class="badge badge-danger">Đã hủy</span>',
                                        'completed' => '<span class="badge badge-info">Hoàn thành</span>'
                                    ];
                                    echo $status_badges[$apt['status']] ?? $apt['status'];
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <a href="appointment_view.php?id=<?php echo $apt['id']; ?>" 
                                           class="btn-sm btn-view" title="Xem chi tiết">👁️</a>
                                        <?php if ($apt['status'] === 'pending'): ?>
                                            <a href="?action=approve&id=<?php echo $apt['id']; ?>" 
                                               onclick="return confirm('Bạn có chắc muốn duyệt lịch hẹn này?')"
                                               class="btn-sm" style="background: #4CAF50; color: white; padding: 0.4rem 0.8rem; border-radius: 5px; text-decoration: none; font-size: 0.9rem;" title="Duyệt">✅</a>
                                            <a href="?action=reject&id=<?php echo $apt['id']; ?>" 
                                               onclick="return confirm('Bạn có chắc muốn từ chối và xóa lịch hẹn này?')"
                                               class="btn-sm btn-delete" title="Từ chối (Xóa)">❌</a>
                                        <?php else: ?>
                                            <a href="?action=delete&id=<?php echo $apt['id']; ?>" 
                                               onclick="return confirm('Bạn có chắc muốn xóa lịch hẹn này?')"
                                               class="btn-sm btn-delete" title="Xóa">🗑️</a>
                                        <?php endif; ?>
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

