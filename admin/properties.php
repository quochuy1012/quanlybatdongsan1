<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

$success = '';
$error = '';

// Xử lý duyệt/từ chối/xóa property
if (isset($_GET['action']) && isset($_GET['id'])) {
    $property_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($property_id > 0) {
        if ($action === 'approve') {
            // Duyệt bài - chuyển từ pending sang available
            dbExecute("UPDATE properties SET status = 'available' WHERE id = :id", [':id' => $property_id]);
            if (true) {
                $success = 'Duyệt bài đăng thành công!';
            } else {
                $error = 'Duyệt thất bại!';
            }
        } elseif ($action === 'reject') {
            // Từ chối - chuyển từ pending sang rejected (hoặc xóa)
            // Ở đây ta sẽ xóa luôn nếu vi phạm
            dbExecute("DELETE FROM properties WHERE id = :id", [':id' => $property_id]);
            if (true) {
                $success = 'Đã từ chối và xóa bài đăng vi phạm!';
            } else {
                $error = 'Xóa thất bại!';
            }
        } elseif ($action === 'delete') {
            // Xóa bài đăng
            dbExecute("DELETE FROM properties WHERE id = :id", [':id' => $property_id]);
            if (true) {
                $success = 'Xóa bất động sản thành công!';
            } else {
                $error = 'Xóa thất bại!';
            }
        }
    }
}

// Lấy danh sách properties
$search = $_GET['search'] ?? '';
$district_filter = $_GET['district'] ?? '';
$status_filter = $_GET['status'] ?? '';
$landlord_filter = isset($_GET['landlord_id']) ? (int)$_GET['landlord_id'] : 0;

$query = "SELECT p.*, u.full_name as landlord_name 
          FROM properties p 
          JOIN users u ON p.landlord_id = u.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $search_param = "%$search%";
    $query .= " AND (p.title LIKE :q_title OR p.address LIKE :q_address OR u.full_name LIKE :q_landlord)";
    $params[':q_title'] = $search_param;
    $params[':q_address'] = $search_param;
    $params[':q_landlord'] = $search_param;
}

if (!empty($district_filter)) {
    $query .= " AND p.district = :district";
    $params[':district'] = $district_filter;
}

if (!empty($status_filter)) {
    $query .= " AND p.status = :status";
    $params[':status'] = $status_filter;
}

if ($landlord_filter > 0) {
    $query .= " AND p.landlord_id = :landlord_id";
    $params[':landlord_id'] = $landlord_filter;
}

$query .= " ORDER BY p.created_at DESC";
$properties = dbSelectAll($query, $params);

// Lấy danh sách landlords cho filter (trước khi đóng kết nối)
$landlords_list = dbSelectAll("SELECT id, full_name FROM users WHERE role = 'landlord' ORDER BY full_name");

$pageTitle = "Quản lý Bất động sản";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <div>
                <h1>🏘️ Quản lý Bất động sản</h1>
                <p style="margin: 0.5rem 0 0 0; color: #7f8c8d; font-size: 0.95rem;">
                    Duyệt và quản lý bài đăng bất động sản
                </p>
            </div>
            <?php if ($status_filter === 'pending' || empty($status_filter)): ?>
                <div>
                    <a href="properties.php?status=pending" class="btn btn-primary" style="background: #FF9800;">
                        ⏳ Xem chờ duyệt (<?php 
                            echo (int)dbScalar("SELECT COUNT(*) FROM properties WHERE status = 'pending'");
                        ?>)
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="content-card">
            <div class="search-filter-bar">
                <form method="GET" action="" style="display: flex; gap: 1rem; flex: 1; flex-wrap: wrap;">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Tìm kiếm theo tiêu đề, địa chỉ, người cho thuê..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <?php
                    // Sử dụng danh sách landlords đã lấy ở trên
                    ?>
                    <select name="landlord_id" class="filter-select">
                        <option value="">Tất cả người cho thuê</option>
                        <?php foreach ($landlords_list as $ll): ?>
                            <option value="<?php echo $ll['id']; ?>" <?php echo $landlord_filter == $ll['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ll['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="district" class="filter-select">
                        <option value="">Tất cả quận/huyện</option>
                        <?php
                        $districts = ['Thủ Đức', 'Bình Thạnh', 'Gò Vấp', 'Quận 1', 'Quận 2', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6', 'Quận 7', 'Quận 8', 'Quận 9', 'Quận 10', 'Quận 11', 'Quận 12'];
                        foreach ($districts as $d):
                        ?>
                            <option value="<?php echo $d; ?>" <?php echo $district_filter === $d ? 'selected' : ''; ?>>
                                <?php echo $d; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="filter-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>✅ Đã duyệt (Còn trống)</option>
                        <option value="rented" <?php echo $status_filter === 'rented' ? 'selected' : ''; ?>>Đã cho thuê</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>⏳ Chờ duyệt</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                    <?php if ($search || $district_filter || $status_filter || $landlord_filter): ?>
                        <a href="properties.php" class="btn btn-secondary">Xóa bộ lọc</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tiêu đề</th>
                        <th>Người cho thuê</th>
                        <th>Địa chỉ</th>
                        <th>Giá</th>
                        <th>Diện tích</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($properties)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <div class="empty-state-icon">🏠</div>
                                <p>Không tìm thấy bất động sản nào</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($properties as $property): ?>
                            <tr>
                                <td><?php echo $property['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($property['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($property['landlord_name']); ?></td>
                                <td><?php echo htmlspecialchars($property['district']); ?></td>
                                <td><strong><?php echo number_format($property['price']); ?> đ</strong></td>
                                <td><?php echo $property['area']; ?> m²</td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'available' => '<span class="badge badge-success">✅ Đã duyệt</span>',
                                        'rented' => '<span class="badge badge-warning">Đã cho thuê</span>',
                                        'pending' => '<span class="badge badge-info">⏳ Chờ duyệt</span>'
                                    ];
                                    echo $status_badges[$property['status']] ?? $property['status'];
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <button type="button"
                                                class="btn-sm btn-view"
                                                title="Xem chi tiết"
                                                onclick="openPropertyPreview(<?php echo (int)$property['id']; ?>)">
                                            👁️
                                        </button>
                                        <?php if ($property['status'] === 'pending'): ?>
                                            <a href="?action=approve&id=<?php echo $property['id']; ?>" 
                                               onclick="return confirm('Bạn có chắc muốn duyệt bài đăng này?')"
                                               class="btn-sm" style="background: #4CAF50; color: white; padding: 0.4rem 0.8rem; border-radius: 5px; text-decoration: none; font-size: 0.9rem;" title="Duyệt bài">✅</a>
                                            <a href="?action=reject&id=<?php echo $property['id']; ?>" 
                                               onclick="return confirm('Bạn có chắc muốn từ chối và xóa bài đăng này?')"
                                               class="btn-sm btn-delete" title="Từ chối (Xóa)">❌</a>
                                        <?php else: ?>
                                            <a href="?action=delete&id=<?php echo $property['id']; ?>" 
                                               onclick="return confirm('Bạn có chắc muốn xóa bất động sản này?')"
                                               class="btn-sm btn-delete" title="Xóa">🗑️</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal xem nhanh chi tiết BĐS -->
<div id="propertyPreviewModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 9999; padding: 2rem;">
    <div style="position: relative; width: min(1200px, 96vw); height: min(90vh, 900px); margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 10px 35px rgba(0,0,0,0.35);">
        <button type="button"
                onclick="closePropertyPreview()"
                aria-label="Đóng"
                title="Đóng"
                style="position:absolute; top:10px; right:10px; width:36px; height:36px; border:none; border-radius:999px; background:#111827; color:#fff; font-size:22px; line-height:1; cursor:pointer; z-index:10; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.25);"
                onmouseover="this.style.background='#ef4444'"
                onmouseout="this.style.background='#111827'">
            ×
        </button>
        <div style="display:flex; justify-content:space-between; align-items:center; padding: 0.75rem 1rem; border-bottom: 1px solid #eee;">
            <strong>👁️ Xem nhanh bất động sản</strong>
        </div>
        <iframe id="propertyPreviewFrame"
                src="about:blank"
                style="width:100%; height: calc(100% - 52px); border:none;"
                loading="lazy"></iframe>
    </div>
</div>

<script>
function openPropertyPreview(propertyId) {
    const modal = document.getElementById('propertyPreviewModal');
    const frame = document.getElementById('propertyPreviewFrame');
    frame.src = '../property_detail.php?id=' + encodeURIComponent(propertyId) + '&embedded=1';
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closePropertyPreview() {
    const modal = document.getElementById('propertyPreviewModal');
    const frame = document.getElementById('propertyPreviewFrame');
    modal.style.display = 'none';
    frame.src = 'about:blank';
    document.body.style.overflow = '';
}

document.getElementById('propertyPreviewModal').addEventListener('click', function (e) {
    if (e.target === this) {
        closePropertyPreview();
    }
});
</script>

<?php include '../includes/footer.php'; ?>

