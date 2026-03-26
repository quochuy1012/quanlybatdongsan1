<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('landlord');
ensureAppointmentMessagesTable();

$currentUser = getCurrentUser();
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$backUrl = trim($_GET['back'] ?? 'appointments.php');
$backUrl = preg_match('/^(https?:|\/\/)/i', $backUrl) ? 'appointments.php' : ltrim($backUrl, '/');
if ($backUrl === '') {
    $backUrl = 'appointments.php';
}
$error = '';

if ($appointment_id <= 0) {
    header("Location: appointments.php");
    exit();
}

$appointment = dbSelectOne(
    "SELECT a.id, a.status, p.title as property_title, u.full_name as tenant_name, u.id as tenant_id
     FROM appointments a
     JOIN properties p ON p.id = a.property_id
     JOIN users u ON u.id = a.tenant_id
     WHERE a.id = :id AND a.landlord_id = :landlord_id",
    [':id' => $appointment_id, ':landlord_id' => (int)$currentUser['id']]
);

if (!$appointment) {
    header("Location: appointments.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    if ($message === '') {
        $error = 'Vui lòng nhập nội dung tin nhắn!';
    } else {
        dbExecute(
            "INSERT INTO appointment_messages (appointment_id, sender_id, receiver_id, message)
             VALUES (:appointment_id, :sender_id, :receiver_id, :message)",
            [
                ':appointment_id' => $appointment_id,
                ':sender_id' => (int)$currentUser['id'],
                ':receiver_id' => (int)$appointment['tenant_id'],
                ':message' => $message
            ]
        );
        header("Location: appointment_chat.php?id=" . $appointment_id . "&back=" . urlencode($backUrl));
        exit();
    }
}

// Đánh dấu đã đọc các tin từ tenant gửi cho landlord.
dbExecute(
    "UPDATE appointment_messages
     SET is_read = 1
     WHERE appointment_id = :appointment_id
       AND receiver_id = :receiver_id
       AND is_read = 0",
    [':appointment_id' => $appointment_id, ':receiver_id' => (int)$currentUser['id']]
);

$messages = dbSelectAll(
    "SELECT am.*, u.full_name as sender_name
     FROM appointment_messages am
     JOIN users u ON u.id = am.sender_id
     WHERE am.appointment_id = :appointment_id
     ORDER BY am.created_at ASC",
    [':appointment_id' => $appointment_id]
);

$pageTitle = "Nhắn tin người thuê";
include '../includes/header.php';
?>

<div class="container">
    <div class="chat-container">
        <h2 class="section-title" style="padding:1rem; margin:0;">💬 Nhắn tin với người thuê</h2>
        <div style="padding: 0 1rem 1rem 1rem; color:#555;">
            <strong>BĐS:</strong> <?php echo htmlspecialchars($appointment['property_title']); ?><br>
            <strong>Người thuê:</strong> <?php echo htmlspecialchars($appointment['tenant_name']); ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin:1rem;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="chat-messages" style="max-height: 420px;">
            <?php if (empty($messages)): ?>
                <p style="text-align:center; color:#666; padding:1.5rem;">Chưa có tin nhắn nào. Hãy gửi lời nhắn đầu tiên.</p>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <?php $isMine = ((int)$msg['sender_id'] === (int)$currentUser['id']); ?>
                    <div class="message <?php echo $isMine ? 'user' : 'admin'; ?>">
                        <strong><?php echo $isMine ? 'Bạn' : htmlspecialchars($msg['sender_name']); ?>:</strong><br>
                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        <div style="font-size:0.8rem; margin-top:0.35rem; opacity:.8;">
                            <?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form method="POST" action="" class="chat-input">
            <input type="text" name="message" placeholder="Nhập tin nhắn..." required>
            <button type="submit" class="btn btn-primary">Gửi</button>
        </form>
    </div>

    <div class="empty-state-actions" style="margin-top:1rem;">
        <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-secondary">Quay lại</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

