<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('tenant');

$currentUser = getCurrentUser();
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$backUrl = trim($_GET['back'] ?? 'appointments.php');
$backUrl = preg_match('/^(https?:|\/\/)/i', $backUrl) ? 'appointments.php' : ltrim($backUrl, '/');
if ($backUrl === '') {
    $backUrl = 'appointments.php';
}
$success = '';
$error = '';

if ($appointment_id <= 0) {
    header("Location: appointments.php");
    exit();
}

$appointment = dbSelectOne(
    "SELECT a.*, p.title as property_title, p.address, p.district
     FROM appointments a
     JOIN properties p ON a.property_id = p.id
     WHERE a.id = :id AND a.tenant_id = :tenant_id",
    [':id' => $appointment_id, ':tenant_id' => (int)$currentUser['id']]
);

if (!$appointment) {
    header("Location: appointments.php");
    exit();
}

if (!in_array($appointment['status'], ['pending', 'confirmed'], true)) {
    $error = 'Chỉ lịch hẹn chờ xác nhận hoặc đã xác nhận mới được chỉnh sửa.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
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
                "UPDATE appointments
                 SET appointment_date = :appointment_date,
                     message = :message,
                     status = 'pending',
                     updated_at = SYSUTCDATETIME()
                 WHERE id = :id AND tenant_id = :tenant_id",
                [
                    ':appointment_date' => $datetime,
                    ':message' => $message !== '' ? $message : null,
                    ':id' => $appointment_id,
                    ':tenant_id' => (int)$currentUser['id']
                ]
            );
            header("Location: " . $backUrl);
            exit();
        } else {
            $error = 'Ngày giờ hẹn phải trong tương lai!';
        }
    }
}

$dt = new DateTime($appointment['appointment_date']);
$dateValue = $dt->format('Y-m-d');
$timeValue = $dt->format('H:i');

$pageTitle = "Sửa lịch hẹn";
include '../includes/header.php';
?>

<div class="container">
    <div class="form-container" style="max-width: 640px;">
        <h2>✏️ Sửa lịch hẹn</h2>

        <div style="background:#f5f7fa; border-radius:8px; padding:1rem; margin-bottom:1rem;">
            <strong><?php echo htmlspecialchars($appointment['property_title']); ?></strong><br>
            <small><?php echo htmlspecialchars($appointment['address'] . ', ' . $appointment['district']); ?></small>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="appointment_date">Ngày hẹn *</label>
                <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($dateValue); ?>">
            </div>

            <div class="form-group">
                <label for="appointment_time">Giờ hẹn *</label>
                <select id="appointment_time" name="appointment_time" required>
                    <option value="">Chọn giờ hẹn</option>
                    <?php for ($h = 8; $h <= 18; $h++):
                        $value = str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':00';
                    ?>
                        <option value="<?php echo $value; ?>" <?php echo $timeValue === $value ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="message">Lời nhắn</label>
                <textarea id="message" name="message" rows="4"><?php echo htmlspecialchars($appointment['message'] ?? ''); ?></textarea>
            </div>

            <div style="display:flex; gap:0.75rem; margin-top:1rem;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Lưu thay đổi</button>
            </div>
        </form>

        <div class="empty-state-actions" style="margin-top: 1.25rem;">
            <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-secondary">Quay lại</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

