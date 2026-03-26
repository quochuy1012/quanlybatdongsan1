<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="admin-sidebar">
    <div class="sidebar-header">
        <h2>⚙️ Admin Panel</h2>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="sidebar-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <span class="icon">📊</span>
            <span>Dashboard</span>
        </a>
        <a href="users.php" class="sidebar-item <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
            <span class="icon">👥</span>
            <span>Quản lý Users</span>
        </a>
        <a href="landlords.php" class="sidebar-item <?php echo $current_page === 'landlords.php' ? 'active' : ''; ?>">
            <span class="icon">🏢</span>
            <span>Quản lý Người cho thuê</span>
        </a>
        <a href="properties.php" class="sidebar-item <?php echo $current_page === 'properties.php' ? 'active' : ''; ?>">
            <span class="icon">🏘️</span>
            <span>Quản lý BĐS</span>
        </a>
        <a href="appointments.php" class="sidebar-item <?php echo $current_page === 'appointments.php' ? 'active' : ''; ?>">
            <span class="icon">📅</span>
            <span>Lịch hẹn</span>
        </a>
        <a href="messages.php" class="sidebar-item <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>">
            <span class="icon">💬</span>
            <span>Tin nhắn</span>
        </a>
    </nav>
</div>

