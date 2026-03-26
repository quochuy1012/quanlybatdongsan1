<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    header("Location: landlords.php");
    exit();
}

// Lấy thông tin landlord
$landlord = dbSelectOne(
    "SELECT * FROM users WHERE id = :id AND role = 'landlord'",
    [':id' => $user_id]
);

if (!$landlord) {
    header("Location: landlords.php");
    exit();
}

// Lấy danh sách properties của landlord
$properties = dbSelectAll(
    "SELECT * FROM properties WHERE landlord_id = :id ORDER BY created_at DESC",
    [':id' => $user_id]
);

// Lấy danh sách appointments
$appointments = dbSelectAll(
    "SELECT TOP 10 a.*, p.title as property_title, u.full_name as tenant_name
     FROM appointments a
     JOIN properties p ON a.property_id = p.id
     JOIN users u ON a.tenant_id = u.id
     WHERE a.landlord_id = :id
     ORDER BY a.created_at DESC",
    [':id' => $user_id]
);

$pageTitle = "Chi tiết Người cho thuê";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1>👤 Chi tiết Người cho thuê</h1>
            <div>
                <a href="landlord_edit.php?id=<?php echo $landlord['id']; ?>" class="btn btn-primary">Sửa</a>
            </div>
        </div>
        
        <!-- Thông tin cơ bản -->
        <div class="content-card">
            <div class="card-header">
                <h2>📋 Thông tin cơ bản</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div>
                    <p style="color: #7f8c8d; margin: 0 0 0.5rem 0; font-size: 0.9rem;">Họ và tên</p>
                    <p style="margin: 0; font-size: 1.1rem; font-weight: 600;"><?php echo htmlspecialchars($landlord['full_name']); ?></p>
                </div>
                <div>
                    <p style="color: #7f8c8d; margin: 0 0 0.5rem 0; font-size: 0.9rem;">Email</p>
                    <p style="margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($landlord['email'] ?? '-'); ?></p>
                </div>
                <div>
                    <p style="color: #7f8c8d; margin: 0 0 0.5rem 0; font-size: 0.9rem;">Số điện thoại</p>
                    <p style="margin: 0; font-size: 1.1rem;"><?php echo htmlspecialchars($landlord['phone'] ?? '-'); ?></p>
                </div>
                <div>
                    <p style="color: #7f8c8d; margin: 0 0 0.5rem 0; font-size: 0.9rem;">Ngày tạo</p>
                    <p style="margin: 0; font-size: 1.1rem;"><?php echo date('d/m/Y H:i', strtotime($landlord['created_at'])); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Thống kê -->
        <div class="stats-grid" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-card-content">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            🏘️
                        </div>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($properties); ?></h3>
                        <p>Bất động sản</p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-content">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            📅
                        </div>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($appointments); ?></h3>
                        <p>Lịch hẹn</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Danh sách Bất động sản -->
        <div class="content-card">
            <div class="card-header">
                <h2>🏘️ Bất động sản (<?php echo count($properties); ?>)</h2>
                <a href="properties.php?landlord_id=<?php echo $landlord['id']; ?>" class="btn-sm btn-view">Xem tất cả</a>
            </div>
            <?php if (empty($properties)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🏠</div>
                    <p>Chưa có bất động sản nào</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tiêu đề</th>
                                <th>Địa chỉ</th>
                                <th>Giá</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($properties, 0, 5) as $prop): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($prop['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($prop['district']); ?></td>
                                    <td><strong><?php echo number_format($prop['price']); ?> đ</strong></td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'available' => '<span class="badge badge-success">Còn trống</span>',
                                            'rented' => '<span class="badge badge-warning">Đã cho thuê</span>',
                                            'pending' => '<span class="badge badge-info">Đang chờ</span>'
                                        ];
                                        echo $status_badges[$prop['status']] ?? $prop['status'];
                                        ?>
                                    </td>
                                    <td>
                                        <a href="../property_detail.php?id=<?php echo $prop['id']; ?>" class="btn-sm btn-view" target="_blank">Xem</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Lịch hẹn gần đây -->
        <div class="content-card">
            <div class="card-header">
                <h2>📅 Lịch hẹn gần đây</h2>
                <a href="appointments.php?landlord_id=<?php echo $landlord['id']; ?>" class="btn-sm btn-view">Xem tất cả</a>
            </div>
            <?php if (empty($appointments)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📅</div>
                    <p>Chưa có lịch hẹn nào</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Bất động sản</th>
                                <th>Người thuê</th>
                                <th>Ngày hẹn</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($appointments, 0, 5) as $apt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($apt['property_title']); ?></td>
                                    <td><?php echo htmlspecialchars($apt['tenant_name']); ?></td>
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div style="margin-top: 1.5rem;">
            <a href="../index.php" class="btn btn-secondary">Quay lại</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

