<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

$success = '';
$error = '';

// Lấy danh sách tenants, landlords và properties
$tenants_list = dbSelectAll("SELECT id, full_name, phone FROM users WHERE role = 'tenant' ORDER BY full_name");
$landlords_list = dbSelectAll("SELECT id, full_name FROM users WHERE role = 'landlord' ORDER BY full_name");
$properties_list = dbSelectAll("SELECT id, title, landlord_id FROM properties ORDER BY title");

// Xử lý thêm mới
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
        
        if ($datetime_obj && $datetime_obj > new DateTime()) {
            dbExecute(
                "INSERT INTO appointments (tenant_id, property_id, landlord_id, appointment_date, message, status)
                 VALUES (:tenant_id, :property_id, :landlord_id, :appointment_date, :message, :status)",
                [
                    ':tenant_id' => $tenant_id,
                    ':property_id' => $property_id,
                    ':landlord_id' => $landlord_id,
                    ':appointment_date' => $datetime,
                    ':message' => $message !== '' ? $message : null,
                    ':status' => $status,
                ]
            );

            if (true) {
                $success = 'Thêm lịch hẹn thành công!';
                header("refresh:2;url=appointments.php");
            } else {
                $error = 'Thêm thất bại!';
            }
        } else {
            $error = 'Ngày giờ hẹn phải trong tương lai!';
        }
    }
}

$pageTitle = "Thêm Lịch hẹn";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <div>
                <h1>➕ Thêm Lịch hẹn mới</h1>
                <p style="margin: 0.5rem 0 0 0; color: #7f8c8d; font-size: 0.95rem;">
                    Tạo lịch hẹn mới cho người thuê và người cho thuê
                </p>
            </div>
            <div></div>
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
                                <option value="<?php echo $tenant['id']; ?>">
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
                                <option value="<?php echo $landlord['id']; ?>">
                                    <?php echo htmlspecialchars($landlord['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="property_id">Bất động sản *</label>
                        <select id="property_id" name="property_id" required>
                            <option value="">Chọn người cho thuê trước</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="appointment_date">Ngày hẹn *</label>
                        <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="appointment_time">Giờ hẹn *</label>
                        <input type="time" id="appointment_time" name="appointment_time" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Trạng thái *</label>
                        <select id="status" name="status" required>
                            <option value="pending" selected>Chờ xác nhận</option>
                            <option value="confirmed">Đã xác nhận</option>
                            <option value="cancelled">Đã hủy</option>
                            <option value="completed">Hoàn thành</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label for="message">Lời nhắn</label>
                    <textarea id="message" name="message" rows="4" placeholder="Nhập lời nhắn..."></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary">✅ Thêm lịch hẹn</button>
                    <a href="../index.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const allProperties = <?php echo json_encode($properties_list); ?>;

function updateProperties() {
    const landlordId = document.getElementById('landlord_id').value;
    const propertySelect = document.getElementById('property_id');
    
    // Clear options
    propertySelect.innerHTML = '<option value="">Chọn bất động sản</option>';
    
    if (landlordId) {
        allProperties.forEach(prop => {
            if (prop.landlord_id == landlordId) {
                const option = document.createElement('option');
                option.value = prop.id;
                option.textContent = prop.title;
                propertySelect.appendChild(option);
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>

