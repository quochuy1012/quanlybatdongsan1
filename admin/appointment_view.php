<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($appointment_id <= 0) {
    header("Location: appointments.php");
    exit();
}

// Lấy thông tin appointment
$appointment = dbSelectOne(
    "SELECT a.*,
            p.title as property_title, p.address, p.district, p.price, p.area, p.bedrooms, p.bathrooms,
            u1.full_name as tenant_name, u1.phone as tenant_phone, u1.email as tenant_email,
            u2.full_name as landlord_name, u2.phone as landlord_phone, u2.email as landlord_email
     FROM appointments a
     JOIN properties p ON a.property_id = p.id
     JOIN users u1 ON a.tenant_id = u1.id
     JOIN users u2 ON a.landlord_id = u2.id
     WHERE a.id = :id",
    [':id' => $appointment_id]
);

if (!$appointment) {
    header("Location: appointments.php");
    exit();
}

$pageTitle = "Chi tiết Lịch hẹn";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <div>
                <h1>👁️ Chi tiết Lịch hẹn #<?php echo $appointment['id']; ?></h1>
                <p style="margin: 0.5rem 0 0 0; color: #7f8c8d; font-size: 0.95rem;">
                    Xem thông tin chi tiết về lịch hẹn này
                </p>
            </div>
            <div></div>
        </div>
        
        <div class="content-card">
            <?php
            $statuses = [
                'pending' => ['text' => 'Chờ xác nhận', 'color' => '#FF9800', 'bg' => '#fff3e0'],
                'confirmed' => ['text' => 'Đã xác nhận', 'color' => '#4CAF50', 'bg' => '#e8f5e9'],
                'cancelled' => ['text' => 'Đã hủy', 'color' => '#f44336', 'bg' => '#ffebee'],
                'completed' => ['text' => 'Hoàn thành', 'color' => '#2196F3', 'bg' => '#e3f2fd']
            ];
            $status = $statuses[$appointment['status']] ?? ['text' => $appointment['status'], 'color' => '#666', 'bg' => '#f5f5f5'];
            ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <!-- Thông tin Lịch hẹn -->
                <div>
                    <h3 style="margin-bottom: 1rem; color: #2c3e50; border-bottom: 2px solid #4CAF50; padding-bottom: 0.5rem;">
                        📅 Thông tin Lịch hẹn
                    </h3>
                    <div style="background: <?php echo $status['bg']; ?>; padding: 1rem; border-radius: 8px; border-left: 4px solid <?php echo $status['color']; ?>; margin-bottom: 1rem;">
                        <div style="font-weight: 600; color: <?php echo $status['color']; ?>; font-size: 1.1rem;">
                            Trạng thái: <?php echo $status['text']; ?>
                        </div>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📅 Ngày giờ hẹn:</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($appointment['appointment_date'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📝 Lời nhắn:</span>
                        <span class="info-value"><?php echo !empty($appointment['message']) ? nl2br(htmlspecialchars($appointment['message'])) : '<em style="color: #999;">Không có</em>'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">🕐 Tạo lúc:</span>
                        <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($appointment['created_at'])); ?></span>
                    </div>
                </div>
                
                <!-- Thông tin Bất động sản -->
                <div>
                    <h3 style="margin-bottom: 1rem; color: #2c3e50; border-bottom: 2px solid #4CAF50; padding-bottom: 0.5rem;">
                        🏘️ Bất động sản
                    </h3>
                    <div class="info-row">
                        <span class="info-label">Tiêu đề:</span>
                        <span class="info-value"><strong><?php echo htmlspecialchars($appointment['property_title']); ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📍 Địa chỉ:</span>
                        <span class="info-value"><?php echo htmlspecialchars($appointment['address'] . ', ' . $appointment['district']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">💰 Giá:</span>
                        <span class="info-value"><?php echo number_format($appointment['price']); ?> đ/tháng</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">📐 Diện tích:</span>
                        <span class="info-value"><?php echo number_format($appointment['area'], 1); ?> m²</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">🛏️ Phòng ngủ:</span>
                        <span class="info-value"><?php echo $appointment['bedrooms']; ?> PN</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">🚿 Phòng tắm:</span>
                        <span class="info-value"><?php echo $appointment['bathrooms']; ?> WC</span>
                    </div>
                    <div style="margin-top: 1rem;">
                        <a href="../property_detail.php?id=<?php echo $appointment['property_id']; ?>" target="_blank" class="btn btn-primary">Xem chi tiết BĐS</a>
                    </div>
                </div>
                
                <!-- Thông tin Người thuê -->
                <div>
                    <h3 style="margin-bottom: 1rem; color: #2c3e50; border-bottom: 2px solid #4CAF50; padding-bottom: 0.5rem;">
                        👤 Người thuê
                    </h3>
                    <div class="info-row">
                        <span class="info-label">Tên:</span>
                        <span class="info-value"><strong><?php echo htmlspecialchars($appointment['tenant_name']); ?></strong></span>
                    </div>
                    <?php if ($appointment['tenant_phone']): ?>
                        <div class="info-row">
                            <span class="info-label">📞 Điện thoại:</span>
                            <span class="info-value">
                                <a href="tel:<?php echo htmlspecialchars($appointment['tenant_phone']); ?>" style="color: #4CAF50; text-decoration: none;">
                                    <?php echo htmlspecialchars($appointment['tenant_phone']); ?>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if ($appointment['tenant_email']): ?>
                        <div class="info-row">
                            <span class="info-label">📧 Email:</span>
                            <span class="info-value">
                                <a href="mailto:<?php echo htmlspecialchars($appointment['tenant_email']); ?>" style="color: #4CAF50; text-decoration: none;">
                                    <?php echo htmlspecialchars($appointment['tenant_email']); ?>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Thông tin Người cho thuê -->
                <div>
                    <h3 style="margin-bottom: 1rem; color: #2c3e50; border-bottom: 2px solid #4CAF50; padding-bottom: 0.5rem;">
                        🏢 Người cho thuê
                    </h3>
                    <div class="info-row">
                        <span class="info-label">Tên:</span>
                        <span class="info-value"><strong><?php echo htmlspecialchars($appointment['landlord_name']); ?></strong></span>
                    </div>
                    <?php if ($appointment['landlord_phone']): ?>
                        <div class="info-row">
                            <span class="info-label">📞 Điện thoại:</span>
                            <span class="info-value">
                                <a href="tel:<?php echo htmlspecialchars($appointment['landlord_phone']); ?>" style="color: #4CAF50; text-decoration: none;">
                                    <?php echo htmlspecialchars($appointment['landlord_phone']); ?>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if ($appointment['landlord_email']): ?>
                        <div class="info-row">
                            <span class="info-label">📧 Email:</span>
                            <span class="info-value">
                                <a href="mailto:<?php echo htmlspecialchars($appointment['landlord_email']); ?>" style="color: #4CAF50; text-decoration: none;">
                                    <?php echo htmlspecialchars($appointment['landlord_email']); ?>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e0e0e0; display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="../index.php" class="btn btn-secondary">Quay lại</a>
            </div>
        </div>
    </div>
</div>

<style>
.info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
    gap: 1rem;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #666;
    min-width: 150px;
}

.info-value {
    color: #2c3e50;
    text-align: right;
    flex: 1;
    word-break: break-word;
}
</style>

<?php include '../includes/footer.php'; ?>

