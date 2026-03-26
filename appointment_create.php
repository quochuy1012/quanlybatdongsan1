<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();
requireRole('tenant');

$property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
$currentUser = getCurrentUser();
$success = '';
$error = '';

if ($property_id <= 0) {
    header("Location: index.php");
    exit();
}

// Lấy thông tin property
$property = dbSelectOne(
    "SELECT p.*, u.id as landlord_id
     FROM properties p
     JOIN users u ON p.landlord_id = u.id
     WHERE p.id = :id",
    [':id' => $property_id]
);

if (!$property) {
    header("Location: index.php");
    exit();
}

// Xử lý đặt lịch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $message = trim($_POST['message'] ?? '');
    
    if (empty($appointment_date) || empty($appointment_time)) {
        $error = 'Vui lòng chọn ngày và giờ hẹn!';
    } else {
        $datetime = $appointment_date . ' ' . $appointment_time . ':00';
        $datetime_obj = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        
        if ($datetime_obj && $datetime_obj > new DateTime()) {
            dbExecute(
                "INSERT INTO appointments (tenant_id, property_id, landlord_id, appointment_date, message)
                 VALUES (:tenant_id, :property_id, :landlord_id, :appointment_date, :message)",
                [
                    ':tenant_id' => (int)$currentUser['id'],
                    ':property_id' => $property_id,
                    ':landlord_id' => (int)$property['landlord_id'],
                    ':appointment_date' => $datetime,
                    ':message' => $message !== '' ? $message : null,
                ]
            );

            if (true) {
                $success = 'Đặt lịch hẹn thành công! Người cho thuê sẽ xác nhận với bạn.';
                header("refresh:2;url=tenant/appointments.php");
            } else {
                $error = 'Đặt lịch thất bại! Vui lòng thử lại.';
            }
        } else {
            $error = 'Ngày giờ hẹn phải trong tương lai!';
        }
    }
}

$pageTitle = "Đặt lịch hẹn";
include 'includes/header.php';
?>

<div class="container">
    <div class="form-container" style="max-width: 600px;">
        <h2 style="text-align: center; margin-bottom: 2rem; color: #2c3e50;">📅 Đặt lịch hẹn xem nhà</h2>
        
        <div style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border-left: 4px solid #4CAF50; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 0.75rem 0; color: #2c3e50; font-size: 1.3rem;">
                <?php echo htmlspecialchars($property['title']); ?>
            </h3>
            <p style="margin: 0; color: #666; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>📍</span>
                <span><?php echo htmlspecialchars($property['address'] . ', ' . $property['district']); ?></span>
            </p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="appointment_date">
                    <span style="font-size: 1.2rem; margin-right: 0.5rem;">📅</span>
                    Ngày hẹn *
                </label>
                <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>" style="font-size: 1rem;">
            </div>
            
            <div class="form-group">
                <label for="appointment_time">
                    <span style="font-size: 1.2rem; margin-right: 0.5rem;">🕐</span>
                    Giờ hẹn *
                </label>
                <select id="appointment_time" name="appointment_time" required style="font-size: 1rem;">
                    <option value="">Chọn giờ hẹn</option>
                    <?php for ($h = 8; $h <= 18; $h++): ?>
                        <option value="<?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>:00">
                            <?php echo str_pad($h, 2, '0', STR_PAD_LEFT); ?>:00
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="message">
                    <span style="font-size: 1.2rem; margin-right: 0.5rem;">💬</span>
                    Lời nhắn (tùy chọn)
                </label>
                <textarea id="message" name="message" placeholder="Nhập lời nhắn cho người cho thuê (ví dụ: Tôi muốn xem nhà vào buổi sáng, vui lòng xác nhận...)..." rows="4" style="font-size: 1rem; resize: vertical;"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary btn-block" style="flex: 1; padding: 1rem; font-size: 1.1rem; font-weight: 600; border-radius: 10px; box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);">
                    ✅ Đặt lịch hẹn
                </button>
                <a href="property_detail.php?id=<?php echo $property_id; ?>" class="btn btn-secondary btn-block" style="flex: 1; padding: 1rem; font-size: 1rem; text-align: center; border-radius: 10px;">
                    ← Hủy
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

