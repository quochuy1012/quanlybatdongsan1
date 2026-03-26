<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('landlord');

$currentUser = getCurrentUser();
$success = '';
$error = '';
ensureAppointmentMessagesTable();

// Xử lý cập nhật trạng thái lịch hẹn
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appointment_id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'confirm') {
        dbExecute(
            "UPDATE appointments SET status = 'confirmed' WHERE id = :id AND landlord_id = :landlord_id",
            [':id' => $appointment_id, ':landlord_id' => (int)$currentUser['id']]
        );
        if (true) {
            $success = 'Đã xác nhận lịch hẹn!';
        }
    } elseif ($action === 'cancel') {
        dbExecute(
            "UPDATE appointments SET status = 'cancelled' WHERE id = :id AND landlord_id = :landlord_id",
            [':id' => $appointment_id, ':landlord_id' => (int)$currentUser['id']]
        );
        if (true) {
            $success = 'Đã hủy lịch hẹn!';
        }
    } elseif ($action === 'complete') {
        dbExecute(
            "UPDATE appointments SET status = 'completed' WHERE id = :id AND landlord_id = :landlord_id",
            [':id' => $appointment_id, ':landlord_id' => (int)$currentUser['id']]
        );
        if (true) {
            $success = 'Đã đánh dấu hoàn thành!';
        }
    }
}

// Lấy danh sách lịch hẹn
$appointments = dbSelectAll(
    "SELECT a.*, p.title as property_title, p.address, p.district, u.full_name as tenant_name, u.phone as tenant_phone,
            (
                SELECT COUNT(*)
                FROM appointment_messages am
                WHERE am.appointment_id = a.id
                  AND am.receiver_id = :landlord_id_unread
                  AND am.is_read = 0
            ) as unread_messages
     FROM appointments a
     JOIN properties p ON a.property_id = p.id
     JOIN users u ON a.tenant_id = u.id
     WHERE a.landlord_id = :landlord_id_where
     ORDER BY a.appointment_date DESC",
    [
        ':landlord_id_unread' => (int)$currentUser['id'],
        ':landlord_id_where' => (int)$currentUser['id']
    ]
);

$pageTitle = "Lịch hẹn";
include '../includes/header.php';
?>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; gap:0.75rem; flex-wrap:wrap;">
        <h2 class="section-title" style="margin:0;">📅 Lịch hẹn</h2>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (empty($appointments)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📅</div>
            <h3>Bạn chưa có lịch hẹn nào</h3>
            <p>Chưa có người thuê nào đặt lịch hẹn xem bất động sản của bạn.</p>
        </div>
    <?php else: ?>
        <div class="appointments-grid">
            <?php foreach ($appointments as $apt): ?>
                <?php
                $statuses = [
                    'pending' => ['text' => 'Chờ xác nhận', 'color' => '#FF9800', 'bg' => '#fff3e0', 'icon' => '⏳'],
                    'confirmed' => ['text' => 'Đã xác nhận', 'color' => '#4CAF50', 'bg' => '#e8f5e9', 'icon' => '✅'],
                    'cancelled' => ['text' => 'Đã hủy', 'color' => '#f44336', 'bg' => '#ffebee', 'icon' => '❌'],
                    'completed' => ['text' => 'Hoàn thành', 'color' => '#2196F3', 'bg' => '#e3f2fd', 'icon' => '✓']
                ];
                $status = $statuses[$apt['status']] ?? ['text' => $apt['status'], 'color' => '#666', 'bg' => '#f5f5f5', 'icon' => '•'];
                ?>
                <div class="appointment-card">
                    <div class="appointment-header" style="background: <?php echo $status['bg']; ?>; border-left: 4px solid <?php echo $status['color']; ?>; display:flex; justify-content:space-between; align-items:flex-start; gap:0.75rem;">
                        <div>
                            <h3 style="margin: 0 0 0.5rem 0; color: #2c3e50; font-size: 1.2rem;">
                                <?php echo htmlspecialchars($apt['property_title']); ?>
                            </h3>
                            <p style="margin: 0; color: #666; font-size: 0.95rem;">
                                <span>📍</span> <?php echo htmlspecialchars($apt['address'] . ', ' . $apt['district']); ?>
                            </p>
                        </div>
                        <div class="status-badge" style="background: <?php echo $status['color']; ?>; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; font-weight: 600; white-space: nowrap; display:inline-flex; align-items:center; line-height:1;">
                            <?php echo $status['icon']; ?> <?php echo $status['text']; ?>
                        </div>
                    </div>
                    <div class="appointment-body">
                        <div class="appointment-info-item">
                            <span class="info-label">👤 Người thuê:</span>
                            <span class="info-value"><?php echo htmlspecialchars($apt['tenant_name']); ?></span>
                        </div>
                        <?php if ($apt['tenant_phone']): ?>
                            <div class="appointment-info-item">
                                <span class="info-label">📞 Điện thoại:</span>
                                <span class="info-value">
                                    <a href="tel:<?php echo htmlspecialchars($apt['tenant_phone']); ?>" style="color: #4CAF50; text-decoration: none;">
                                        <?php echo htmlspecialchars($apt['tenant_phone']); ?>
                                    </a>
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="appointment-info-item">
                            <span class="info-label">📅 Ngày giờ hẹn:</span>
                            <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($apt['appointment_date'])); ?></span>
                        </div>
                        <?php if (!empty($apt['message'])): ?>
                            <div class="appointment-info-item">
                                <span class="info-label">💬 Lời nhắn:</span>
                                <span class="info-value"><?php echo htmlspecialchars($apt['message']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="appointment-footer">
                        <a href="../property_detail.php?id=<?php echo $apt['property_id']; ?>&back=landlord/appointments.php" class="btn btn-secondary" style="flex: 1; text-align: center;">Xem BĐS</a>
                        <a href="appointment_chat.php?id=<?php echo $apt['id']; ?>&back=appointments.php" class="btn btn-primary" style="flex: 1; text-align: center;">
                            Nhắn tin
                            <?php if ((int)($apt['unread_messages'] ?? 0) > 0): ?>
                                <span style="margin-left: 0.35rem; background:#f44336; color:#fff; border-radius:999px; padding:0.1rem 0.45rem; font-size:0.75rem;">
                                    <?php echo (int)$apt['unread_messages']; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <?php if ($apt['status'] === 'pending'): ?>
                            <a href="?action=confirm&id=<?php echo $apt['id']; ?>" 
                               class="btn btn-primary" style="flex: 1; text-align: center; background: #4CAF50;">
                                ✅ Xác nhận
                            </a>
                            <a href="?action=cancel&id=<?php echo $apt['id']; ?>" 
                               onclick="return confirm('Bạn có chắc muốn hủy lịch hẹn này?')"
                               class="btn btn-danger" style="flex: 1; text-align: center; background: #f44336;">
                                ❌ Hủy
                            </a>
                        <?php elseif ($apt['status'] === 'confirmed'): ?>
                            <a href="?action=complete&id=<?php echo $apt['id']; ?>" 
                               class="btn btn-primary" style="flex: 1; text-align: center; background: #2196F3;">
                                ✓ Hoàn thành
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="empty-state-actions" style="margin-top: 1.5rem;">
        <a href="../index.php" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

