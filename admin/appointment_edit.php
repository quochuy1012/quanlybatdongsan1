<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

if ($appointment_id <= 0) {
    header("Location: appointments.php");
    exit();
}

// Lấy thông tin appointment
$conn = getDBConnection();
$appointment = dbSelectOne(
    "SELECT a.*, p.title as property_title
     FROM appointments a
     JOIN properties p ON a.property_id = p.id
     WHERE a.id = :id",
    [':id' => $appointment_id]
);

if (!$appointment) {
    header("Location: appointments.php");
    exit();
}

// Lấy danh sách tenants và landlords
$tenants_list = dbSelectAll("SELECT id, full_name, phone FROM users WHERE role = 'tenant' ORDER BY full_name");
$landlords_list = dbSelectAll("SELECT id, full_name FROM users WHERE role = 'landlord' ORDER BY full_name");
$properties_list = dbSelectAll("SELECT id, title, landlord_id FROM properties ORDER BY title");

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenant_id = isset($_POST['tenant_id']) ? (int)$_POST['tenant_id'] : 0;
    $property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
    $landlord_id = isset($_POST['landlord_id']) ? (int)$_POST['landlord_id'] : 0;
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $message = trim($_POST['message'] ?? '');
    $status = $_POST['status'] ?? 'pending';
    
    if (empty($appointment_date) || empty($appointment_time) || $tenant_id <= 0 || $property_id <= 0 || $landlord_id <= 0) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        $datetime = $appointment_date . ' ' . $appointment_time . ':00';
        $datetime_obj = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        
        if ($datetime_obj) {
            dbExecute(
                "UPDATE appointments
                 SET tenant_id = :tenant_id,
                     property_id = :property_id,
                     landlord_id = :landlord_id,
                     appointment_date = :appointment_date,
                     message = :message,
                     status = :status
                 WHERE id = :id",
                [
                    ':tenant_id' => $tenant_id,
                    ':property_id' => $property_id,
                    ':landlord_id' => $landlord_id,
                    ':appointment_date' => $datetime,
                    ':message' => $message !== '' ? $message : null,
                    ':status' => $status,
                    ':id' => $appointment_id,
                ]
            );

            if (true) {
                $success = 'Cập nhật lịch hẹn thành công!';
                // Cập nhật lại thông tin appointment
                $appointment = dbSelectOne(
                    "SELECT a.*, p.title as property_title
                     FROM appointments a
                     JOIN properties p ON a.property_id = p.id
                     WHERE a.id = :id",
                    [':id' => $appointment_id]
                );
            } else {
                $error = 'Cập nhật thất bại!';
            }
        } else {
            $error = 'Ngày giờ không hợp lệ!';
        }
    }
}

// Parse datetime
$appointment_datetime = new DateTime($appointment['appointment_date']);
$appointment_date_value = $appointment_datetime->format('Y-m-d');
$appointment_time_value = $appointment_datetime->format('H:i');

$pageTitle = "Sửa Lịch hẹn";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <div>
                <h1>✏️ Sửa Lịch hẹn #<?php echo $appointment['id']; ?></h1>
                <p style="margin: 0.5rem 0 0 0; color: #7f8c8d; font-size: 0.95rem;">
                    Cập nhật thông tin lịch hẹn
                </p>
            </div>
            <div>
                <a href="appointment_view.php?id=<?php echo $appointment['id']; ?>" class="btn btn-secondary">👁️ Xem</a>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="content-card">
            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                    <div class="form-group">
                        <label for="tenant_id">Người thuê *</label>
                        <select id="tenant_id" name="tenant_id" required>
                            <option value="">Chọn người thuê</option>
                            <?php foreach ($tenants_list as $tenant): ?>
                                <option value="<?php echo $tenant['id']; ?>" <?php echo $appointment['tenant_id'] == $tenant['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tenant['full_name'] . ($tenant['phone'] ? ' - ' . $tenant['phone'] : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="landlord_id">Người cho thuê *</label>
                        <select id="landlord_id" name="landlord_id" required onchange="updateProperties()">
                            <option value="">Chọn người cho thuê</option>
                            <?php foreach ($landlords_list as $landlord): ?>
                                <option value="<?php echo $landlord['id']; ?>" <?php echo $appointment['landlord_id'] == $landlord['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($landlord['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="property_id">Bất động sản *</label>
                        <select id="property_id" name="property_id" required>
                            <option value="">Chọn bất động sản</option>
                            <?php foreach ($properties_list as $prop): ?>
                                <?php if ($prop['landlord_id'] == $appointment['landlord_id']): ?>
                                    <option value="<?php echo $prop['id']; ?>" 
                                            data-landlord="<?php echo $prop['landlord_id']; ?>"
                                            <?php echo $appointment['property_id'] == $prop['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prop['title']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="appointment_date">Ngày hẹn *</label>
                        <input type="date" id="appointment_date" name="appointment_date" required value="<?php echo htmlspecialchars($appointment_date_value); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="appointment_time">Giờ hẹn *</label>
                        <input type="time" id="appointment_time" name="appointment_time" required value="<?php echo htmlspecialchars($appointment_time_value); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Trạng thái *</label>
                        <select id="status" name="status" required>
                            <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                            <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                            <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                            <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label for="message">Lời nhắn</label>
                    <textarea id="message" name="message" rows="4" placeholder="Nhập lời nhắn..."><?php echo htmlspecialchars($appointment['message'] ?? ''); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
                    <a href="appointment_view.php?id=<?php echo $appointment['id']; ?>" class="btn btn-secondary">👁️ Xem</a>
                    <a href="../index.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateProperties() {
    const landlordId = document.getElementById('landlord_id').value;
    const propertySelect = document.getElementById('property_id');
    const options = propertySelect.querySelectorAll('option[data-landlord]');
    
    // Reset
    propertySelect.innerHTML = '<option value="">Chọn bất động sản</option>';
    
    if (landlordId) {
        options.forEach(option => {
            if (option.getAttribute('data-landlord') == landlordId) {
                propertySelect.appendChild(option.cloneNode(true));
            }
        });
    } else {
        // Show all if no landlord selected
        options.forEach(option => {
            propertySelect.appendChild(option.cloneNode(true));
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>

