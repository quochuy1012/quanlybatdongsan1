<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

// Thống kê tổng quan
$stats = [];

// Tổng số user
$stats['total_users'] = (int)dbScalar("SELECT COUNT(*) FROM users");

// Tổng số property
$stats['total_properties'] = (int)dbScalar("SELECT COUNT(*) FROM properties");

// Tổng số appointment
$stats['total_appointments'] = (int)dbScalar("SELECT COUNT(*) FROM appointments");

// Tổng số message chưa trả lời
$stats['pending_messages'] = (int)dbScalar("SELECT COUNT(*) FROM messages WHERE status = 'pending'");

// Thống kê chi tiết
$role_details = dbSelectOne("SELECT 
    COUNT(CASE WHEN role = 'tenant' THEN 1 END) as tenants,
    COUNT(CASE WHEN role = 'landlord' THEN 1 END) as landlords,
    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins
    FROM users");

$property_status = dbSelectOne("SELECT 
    COUNT(CASE WHEN status = 'available' THEN 1 END) as available_props,
    COUNT(CASE WHEN status = 'rented' THEN 1 END) as rented_props,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_props
    FROM properties");

$appointment_status = dbSelectOne("SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_apts,
    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_apts,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_apts,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_apts
    FROM appointments");

// Thống kê theo role
$role_stats = dbSelectAll("SELECT role, COUNT(*) as count FROM users GROUP BY role");

// Thống kê tìm kiếm theo khu vực
$search_stats = dbSelectAll(
    "SELECT TOP 10 district, SUM(search_count) as total
     FROM search_statistics
     GROUP BY district
     ORDER BY total DESC"
);

// Properties mới nhất
$recent_properties = dbSelectAll(
    "SELECT TOP 5 p.*, u.full_name as landlord_name
     FROM properties p
     JOIN users u ON p.landlord_id = u.id
     ORDER BY p.created_at DESC"
);

// Appointments gần đây
$recent_appointments = dbSelectAll(
    "SELECT TOP 5 a.*, p.title as property_title, u.full_name as tenant_name
     FROM appointments a
     JOIN properties p ON a.property_id = p.id
     JOIN users u ON a.tenant_id = u.id
     ORDER BY a.created_at DESC"
);

// Users mới đăng ký
$recent_users = dbSelectAll("SELECT TOP 5 * FROM users ORDER BY created_at DESC");

$pageTitle = "Bảng điều khiển Admin";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <div>
                <h1>📊 Dashboard</h1>
                <p style="margin: 0.5rem 0 0 0; color: #7f8c8d; font-size: 0.95rem;">
                    Xin chào, <strong><?php echo htmlspecialchars(getCurrentUser()['full_name']); ?></strong> | 
                    <?php echo date('d/m/Y H:i'); ?>
                </p>
            </div>
        </div>
    
        <!-- Thống kê tổng quan -->
        <div class="stats-grid">
            <div class="stat-card stat-card-primary">
                <div class="stat-card-content">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                <path d="M16 7c0-2.21-1.79-4-4-4S8 4.79 8 7s1.79 4 4 4 4-1.79 4-4zm-4 7c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p>Tổng người dùng</p>
                        <div class="stat-detail">
                            <span class="stat-badge badge-info"><?php echo $role_details['tenants']; ?> Người thuê</span>
                            <span class="stat-badge badge-warning"><?php echo $role_details['landlords']; ?> Người cho thuê</span>
                        </div>
                    </div>
                </div>
                <a href="users.php" class="stat-card-link">Xem tất cả →</a>
            </div>
            
            <div class="stat-card stat-card-success">
                <div class="stat-card-content">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_properties']; ?></h3>
                        <p>Bất động sản</p>
                        <div class="stat-detail">
                            <span class="stat-badge badge-info" style="background: #2196F3; color: white; font-weight: bold;">
                                ⏳ <?php echo $property_status['pending_props']; ?> Chờ duyệt
                            </span>
                            <span class="stat-badge badge-success"><?php echo $property_status['available_props']; ?> Đã duyệt</span>
                            <span class="stat-badge badge-warning"><?php echo $property_status['rented_props']; ?> Đã cho thuê</span>
                        </div>
                    </div>
                </div>
                <a href="properties.php" class="stat-card-link">Xem tất cả →</a>
            </div>
            
            <div class="stat-card stat-card-info">
                <div class="stat-card-content">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_appointments']; ?></h3>
                        <p>Lịch hẹn</p>
                        <div class="stat-detail">
                            <span class="stat-badge badge-info" style="background: #FF9800; color: white; font-weight: bold;">
                                ⏳ <?php echo $appointment_status['pending_apts']; ?> Chờ duyệt
                            </span>
                            <span class="stat-badge badge-success"><?php echo $appointment_status['confirmed_apts']; ?> Đã xác nhận</span>
                            <span class="stat-badge badge-info"><?php echo $appointment_status['completed_apts']; ?> Hoàn thành</span>
                        </div>
                    </div>
                </div>
                <a href="appointments.php" class="stat-card-link">Xem tất cả →</a>
            </div>

            <div class="stat-card stat-card-warning">
                <div class="stat-card-content">
                    <div class="stat-icon-wrapper">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_messages']; ?></h3>
                        <p>Tin nhắn chờ</p>
                        <div class="stat-detail">
                            <span class="stat-badge badge-danger">Cần xử lý</span>
                        </div>
                    </div>
                </div>
                <a href="messages.php" class="stat-card-link">Xem tất cả →</a>
            </div>

        </div>
        
        <!-- Grid Layout cho Charts và Tables -->
        <div class="dashboard-grid">
            <!-- Biểu đồ thống kê người dùng -->
            <div class="content-card chart-card">
                <div class="card-header">
                    <h2>👥 Phân bố người dùng</h2>
                    <span class="card-badge">Theo vai trò</span>
                </div>
                <div class="chart-container">
                    <canvas id="roleChart"></canvas>
                </div>
            </div>
            
            <!-- Biểu đồ thống kê tìm kiếm -->
            <div class="content-card chart-card">
                <div class="card-header">
                    <h2>🔍 Tìm kiếm theo khu vực</h2>
                    <span class="card-badge">Top 10</span>
                </div>
                <div class="chart-container">
                    <canvas id="searchChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Grid Layout cho Tables -->
        <div class="dashboard-grid-3">
            <!-- Bảng thống kê tìm kiếm -->
            <div class="content-card">
                <div class="card-header">
                    <h2>📈 Top khu vực</h2>
                    <a href="properties.php" class="btn-sm btn-view">Xem tất cả</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Khu vực</th>
                                <th style="text-align: right;">Lượt tìm</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($search_stats)): ?>
                                <tr>
                                    <td colspan="2" class="empty-state">Chưa có dữ liệu</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_slice($search_stats, 0, 5) as $stat): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($stat['district']); ?></strong></td>
                                        <td style="text-align: right;"><span class="badge badge-primary"><?php echo number_format($stat['total']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Properties mới nhất -->
            <div class="content-card">
                <div class="card-header">
                    <h2>🏘️ BĐS mới nhất</h2>
                    <a href="properties.php" class="btn-sm btn-view">Xem tất cả</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tiêu đề</th>
                                <th style="text-align: right;">Giá</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_properties)): ?>
                                <tr>
                                    <td colspan="2" class="empty-state">Chưa có BĐS</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_properties as $prop): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars(mb_substr($prop['title'], 0, 30)) . (mb_strlen($prop['title']) > 30 ? '...' : ''); ?></strong><br>
                                            <small style="color: #666;"><?php echo htmlspecialchars($prop['district']); ?></small>
                                        </td>
                                        <td style="text-align: right;">
                                            <strong style="color: #4CAF50;"><?php echo number_format($prop['price']); ?> đ</strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Users mới nhất -->
            <div class="content-card">
                <div class="card-header">
                    <h2>👤 Users mới</h2>
                    <a href="users.php" class="btn-sm btn-view">Xem tất cả</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Vai trò</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_users)): ?>
                                <tr>
                                    <td colspan="2" class="empty-state">Chưa có user</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></td>
                                        <td>
                                            <?php
                                            $role_badges = [
                                                'tenant' => '<span class="badge badge-info">Người thuê</span>',
                                                'landlord' => '<span class="badge badge-warning">Người cho thuê</span>',
                                                'admin' => '<span class="badge badge-danger">Admin</span>'
                                            ];
                                            echo $role_badges[$user['role']] ?? $user['role'];
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script>
// Biểu đồ thống kê role
const roleCtx = document.getElementById('roleChart');
if (roleCtx) {
    const roles = <?php echo json_encode(array_column($role_stats, 'role')); ?>;
    const counts = <?php echo json_encode(array_column($role_stats, 'count')); ?>;
    const roleLabels = roles.map(r => {
        const labels = {'tenant': 'Người thuê', 'landlord': 'Người cho thuê', 'admin': 'Admin'};
        return labels[r] || r;
    });
    
    new Chart(roleCtx, {
        type: 'doughnut',
        data: {
            labels: roleLabels,
            datasets: [{
                data: counts,
                backgroundColor: [
                    'rgba(76, 175, 80, 0.9)',
                    'rgba(255, 152, 0, 0.9)',
                    'rgba(33, 150, 243, 0.9)'
                ],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            },
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1500
            }
        }
    });
}

// Biểu đồ thống kê tìm kiếm
const searchCtx = document.getElementById('searchChart');
if (searchCtx) {
    const districts = <?php echo json_encode(array_column($search_stats, 'district')); ?>;
    const searches = <?php echo json_encode(array_column($search_stats, 'total')); ?>;
    
    new Chart(searchCtx, {
        type: 'bar',
        data: {
            labels: districts,
            datasets: [{
                label: 'Số lượt tìm kiếm',
                data: searches,
                backgroundColor: 'rgba(76, 175, 80, 0.8)',
                borderColor: 'rgba(76, 175, 80, 1)',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeInOutQuart'
            }
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>

