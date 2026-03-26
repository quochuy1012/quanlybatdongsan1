<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

$currentUser = getCurrentUser();
$success = '';
$error = '';

// Gửi tin nhắn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    
    if (empty($message)) {
        $error = 'Vui lòng nhập tin nhắn!';
    } else {
        dbExecute(
            "INSERT INTO messages (user_id, message) VALUES (:user_id, :message)",
            [':user_id' => (int)$currentUser['id'], ':message' => $message]
        );

        if (true) {
            $success = 'Tin nhắn đã được gửi! Chúng tôi sẽ liên hệ với bạn sớm nhất.';
        } else {
            $error = 'Gửi tin nhắn thất bại!';
        }
    }
}

// Lấy tin nhắn của user
$messages = dbSelectAll(
    "SELECT * FROM messages WHERE user_id = :user_id ORDER BY created_at DESC",
    [':user_id' => (int)$currentUser['id']]
);

$pageTitle = "Hỗ trợ";
include 'includes/header.php';
?>

<div class="container">
    <div class="chat-container">
        <div style="padding:1rem;">
            <h2 class="section-title" style="margin: 0;">💬 Chat với Admin</h2>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin: 1rem;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error" style="margin: 1rem;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="chat-messages">
            <?php if (empty($messages)): ?>
                <p style="text-align: center; color: #666; padding: 2rem;">
                    Chưa có tin nhắn nào. Hãy gửi tin nhắn để được hỗ trợ!
                </p>
            <?php else: ?>
                <?php foreach (array_reverse($messages) as $msg): ?>
                    <div class="message user">
                        <strong>Bạn:</strong><br>
                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        <div style="font-size: 0.8rem; margin-top: 0.5rem; opacity: 0.8;">
                            <?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?>
                        </div>
                    </div>
                    
                    <?php if ($msg['response']): ?>
                        <div class="message admin">
                            <strong>Admin:</strong><br>
                            <?php echo nl2br(htmlspecialchars($msg['response'])); ?>
                        </div>
                    <?php elseif ($msg['status'] === 'pending'): ?>
                        <div class="message admin">
                            <strong>Hệ thống:</strong><br>
                            Cảm ơn bạn đã liên hệ. Chúng tôi sẽ liên hệ người hỗ trợ cho bạn.
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="" class="chat-input">
            <input type="text" name="message" placeholder="Nhập tin nhắn của bạn..." required>
            <button type="submit" class="btn btn-primary">Gửi</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

