<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

$currentUser = getCurrentUser();
$success = '';
$error = '';

// Trả lời tin nhắn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $message_id = (int)$_POST['message_id'];
    $response = trim($_POST['response'] ?? '');
    
    if (empty($response)) {
        $error = 'Vui lòng nhập phản hồi!';
    } else {
        dbExecute(
            "UPDATE messages SET admin_id = :admin_id, response = :response, status = 'replied' WHERE id = :id",
            [':admin_id' => (int)$currentUser['id'], ':response' => $response, ':id' => $message_id]
        );
        
        if (true) {
            $success = 'Đã gửi phản hồi!';
        } else {
            $error = 'Gửi phản hồi thất bại!';
        }
    }
}

// Lấy danh sách tin nhắn
$filter = $_GET['filter'] ?? 'all';
$query = "SELECT m.*, u.full_name as user_name, u.email as user_email, u.phone as user_phone 
          FROM messages m 
          JOIN users u ON m.user_id = u.id 
          WHERE 1=1";

if ($filter === 'pending') {
    $query .= " AND m.status = 'pending'";
} elseif ($filter === 'replied') {
    $query .= " AND m.status = 'replied'";
}

$query .= " ORDER BY m.created_at DESC";

$messages = dbSelectAll($query);

$pageTitle = "Quản lý tin nhắn";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1>💬 Quản lý Tin nhắn</h1>
        </div>
    
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="content-card">
            <div class="search-filter-bar">
                <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">Tất cả</a>
                <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Chờ trả lời</a>
                <a href="?filter=replied" class="btn <?php echo $filter === 'replied' ? 'btn-primary' : 'btn-secondary'; ?>">Đã trả lời</a>
            </div>
            
            <?php if (empty($messages)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">💬</div>
                    <p>Không có tin nhắn nào</p>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: 1.5rem;">
                    <?php foreach ($messages as $msg): ?>
                        <div class="content-card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($msg['user_name']); ?></h3>
                                    <?php if ($msg['user_email']): ?>
                                        <p style="margin: 0.25rem 0; color: #666;">📧 <?php echo htmlspecialchars($msg['user_email']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($msg['user_phone']): ?>
                                        <p style="margin: 0.25rem 0; color: #666;">📞 <?php echo htmlspecialchars($msg['user_phone']); ?></p>
                                    <?php endif; ?>
                                    <p style="margin: 0.5rem 0 0 0; color: #666; font-size: 0.9rem;">
                                        📅 <?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?>
                                    </p>
                                </div>
                                <?php
                                $status_badges = [
                                    'pending' => '<span class="badge badge-warning">Chờ trả lời</span>',
                                    'replied' => '<span class="badge badge-success">Đã trả lời</span>'
                                ];
                                echo $status_badges[$msg['status']] ?? '';
                                ?>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; border-left: 4px solid #4CAF50;">
                                <strong>Tin nhắn:</strong><br>
                                <p style="margin: 0.5rem 0 0 0;"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                            </div>
                            
                            <?php if ($msg['response']): ?>
                                <div style="background: #e8f5e9; padding: 1rem; border-radius: 5px; border-left: 4px solid #4CAF50;">
                                    <strong>Phản hồi:</strong><br>
                                    <p style="margin: 0.5rem 0 0 0;"><?php echo nl2br(htmlspecialchars($msg['response'])); ?></p>
                                </div>
                            <?php else: ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                    <div class="form-group">
                                        <label for="response_<?php echo $msg['id']; ?>">Phản hồi:</label>
                                        <textarea id="response_<?php echo $msg['id']; ?>" name="response" required placeholder="Nhập phản hồi..." rows="4"></textarea>
                                    </div>
                                    <button type="submit" name="reply" class="btn btn-primary">Gửi phản hồi</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

