<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('landlord');

$currentUser = getCurrentUser();
$success = '';
$error = '';

// Toggle status (chuyển đổi giữa available và rented)
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $property_id = (int)$_GET['id'];
    // Kiểm tra property thuộc về landlord và đã được duyệt
    $property = dbSelectOne(
        "SELECT status FROM properties WHERE id = :id AND landlord_id = :landlord_id",
        [':id' => $property_id, ':landlord_id' => (int)$currentUser['id']]
    );
    
    if ($property) {
        $current_status = $property['status'];
        
        // Chỉ cho phép toggle nếu đã được duyệt (available hoặc rented)
        if ($current_status === 'available' || $current_status === 'rented') {
            $new_status = $current_status === 'available' ? 'rented' : 'available';
            dbExecute(
                "UPDATE properties SET status = :status WHERE id = :id AND landlord_id = :landlord_id",
                [':status' => $new_status, ':id' => $property_id, ':landlord_id' => (int)$currentUser['id']]
            );
            
            if (true) {
                $status_label = $new_status === 'available' ? 'Còn trống' : 'Hết phòng';
                $success = "Đã cập nhật trạng thái thành: {$status_label}";
            } else {
                $error = 'Cập nhật trạng thái thất bại!';
            }
        } else {
            $error = 'Chỉ có thể thay đổi trạng thái khi bài đăng đã được duyệt!';
        }
    } else {
        $error = 'Không tìm thấy bất động sản!';
    }
}

// Xóa bất động sản
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $property_id = (int)$_GET['id'];
    dbExecute(
        "DELETE FROM properties WHERE id = :id AND landlord_id = :landlord_id",
        [':id' => $property_id, ':landlord_id' => (int)$currentUser['id']]
    );
    
    if (true) {
        $success = 'Đã xóa bất động sản!';
    } else {
        $error = 'Xóa thất bại!';
    }
}

// Lấy danh sách bất động sản
$properties = dbSelectAll(
    "SELECT * FROM properties WHERE landlord_id = :landlord_id ORDER BY created_at DESC",
    [':landlord_id' => (int)$currentUser['id']]
);

$pageTitle = "Quản lý bất động sản";
include '../includes/header.php';
?>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 class="section-title" style="margin: 0;">🏘️ Quản lý bất động sản</h2>
            <p style="margin: 0.5rem 0 0 0; color: #666; font-size: 0.95rem;">
                Quản lý các bài đăng bất động sản của bạn
            </p>
        </div>
        <a href="property_add.php" class="btn btn-primary">+ Thêm bất động sản</a>
    </div>
    
    <?php 
    // Đếm số properties theo trạng thái
    $pending_count = 0;
    $available_count = 0;
    $rented_count = 0;
    foreach ($properties as $prop) {
        if ($prop['status'] === 'pending') $pending_count++;
        elseif ($prop['status'] === 'available') $available_count++;
        elseif ($prop['status'] === 'rented') $rented_count++;
    }
    ?>
    
    <?php if ($pending_count > 0): ?>
        <div class="alert" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-left: 4px solid #2196F3; padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.5rem;">⏳</span>
                <div>
                    <strong style="color: #1976D2; font-size: 1.05rem;">Bạn có <?php echo $pending_count; ?> bài đăng đang chờ admin duyệt</strong>
                    <p style="margin: 0.5rem 0 0 0; color: #555; font-size: 0.95rem;">
                        Bài đăng của bạn sẽ được admin xác minh và duyệt trong thời gian sớm nhất. Sau khi được duyệt, bài đăng sẽ hiển thị trên website cho người thuê xem.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($properties)): ?>
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
            <div style="padding: 0.75rem 1.25rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 3px solid #2196F3;">
                <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.25rem;">⏳ Chờ duyệt</div>
                <div style="font-size: 1.5rem; font-weight: bold; color: #2196F3;"><?php echo $pending_count; ?></div>
            </div>
            <div style="padding: 0.75rem 1.25rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 3px solid #4CAF50;">
                <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.25rem;">✅ Còn trống</div>
                <div style="font-size: 1.5rem; font-weight: bold; color: #4CAF50;"><?php echo $available_count; ?></div>
            </div>
            <div style="padding: 0.75rem 1.25rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 3px solid #FF9800;">
                <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.25rem;">🔒 Hết phòng</div>
                <div style="font-size: 1.5rem; font-weight: bold; color: #FF9800;"><?php echo $rented_count; ?></div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (empty($properties)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🏘️</div>
            <h3>Bạn chưa có bất động sản nào</h3>
            <p>Bắt đầu bằng cách thêm bất động sản đầu tiên của bạn để cho thuê.</p>
            <div class="empty-state-actions">
                <a href="property_add.php" class="btn btn-primary">+ Thêm bất động sản</a>
            </div>
        </div>
    <?php else: ?>
        <div class="properties-grid">
            <?php 
            require_once '../config/image_helper.php';
            foreach ($properties as $property): 
                // Lấy ảnh từ database
                $images = getImagesArray($property['images'] ?? '');
                $first_image = !empty($images) ? getImageUrl($images[0]) : null;
                
                // Lấy loại BĐS
                $property_types = ['apartment' => 'Căn hộ', 'house' => 'Nhà nguyên căn', 'room' => 'Phòng trọ', 'studio' => 'Studio'];
                $type_label = $property_types[$property['property_type']] ?? $property['property_type'];
            ?>
                <div class="property-card">
                    <div class="property-image-wrapper">
                        <div class="property-image" style="height: 220px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                            <?php if ($first_image): ?>
                                <img src="<?php echo htmlspecialchars($first_image); ?>" 
                                     alt="<?php echo htmlspecialchars($property['title']); ?>" 
                                     style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" 
                                     onerror="this.onerror=null; this.style.display='none'; if(!this.parentElement.querySelector('.placeholder')) { this.parentElement.innerHTML='<div class=\'placeholder\' style=\'font-size: 3rem;\'>🏠</div>'; }"
                                     onmouseover="this.style.transform='scale(1.05)'"
                                     onmouseout="this.style.transform='scale(1)'">
                            <?php else: ?>
                                <div style="font-size: 3rem;">🏠</div>
                            <?php endif; ?>
                            <div class="property-type-badge"><?php echo $type_label; ?></div>
                            <?php if (count($images) > 1): ?>
                                <div class="image-count-badge">📷 <?php echo count($images); ?></div>
                            <?php endif; ?>
                            <div style="position: absolute; bottom: 10px; right: 10px; background: <?php 
                                echo $property['status'] === 'available' ? 'rgba(76, 175, 80, 0.9)' : 
                                    ($property['status'] === 'pending' ? 'rgba(33, 150, 243, 0.9)' : 'rgba(255, 152, 0, 0.9)'); 
                            ?>; color: white; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; backdrop-filter: blur(10px);">
                                <?php 
                                $total_rooms = isset($property['total_rooms']) ? (int)$property['total_rooms'] : 1;
                                $rented_rooms = isset($property['rented_rooms']) ? (int)$property['rented_rooms'] : 0;
                                $available_rooms = $total_rooms - $rented_rooms;
                                
                                if ($property['status'] === 'available') {
                                    echo '✓ Còn trống';
                                    if ($total_rooms > 1) {
                                        echo ' (' . $available_rooms . '/' . $total_rooms . ')';
                                    }
                                } elseif ($property['status'] === 'pending') {
                                    echo '⏳ Chờ duyệt';
                                } else {
                                    echo '🔒 Hết phòng';
                                    if ($total_rooms > 1) {
                                        echo ' (' . $rented_rooms . '/' . $total_rooms . ')';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="property-info">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; gap: 1rem;">
                            <h3 class="property-title" style="flex: 1; margin: 0;"><?php echo htmlspecialchars($property['title']); ?></h3>
                            <?php if ($property['status'] === 'pending'): ?>
                                <div style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 2px solid #2196F3; padding: 0.4rem 0.8rem; border-radius: 20px; white-space: nowrap;">
                                    <span style="color: #1976D2; font-weight: 600; font-size: 0.85rem;">⏳ Chờ duyệt</span>
                                </div>
                            <?php elseif ($property['status'] === 'available'): ?>
                                <div style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border: 2px solid #4CAF50; padding: 0.4rem 0.8rem; border-radius: 20px; white-space: nowrap;">
                                    <span style="color: #2e7d32; font-weight: 600; font-size: 0.85rem;">✅ Còn trống</span>
                                </div>
                            <?php else: ?>
                                <div style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); border: 2px solid #FF9800; padding: 0.4rem 0.8rem; border-radius: 20px; white-space: nowrap;">
                                    <span style="color: #e65100; font-weight: 600; font-size: 0.85rem;">🔒 Hết phòng</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <p class="property-location">
                            <span class="location-icon">📍</span>
                            <?php echo htmlspecialchars($property['address'] . ', ' . $property['district']); ?>
                        </p>
                        <?php if ($property['status'] === 'pending'): ?>
                            <div style="background: #fff3cd; border-left: 3px solid #ffc107; padding: 0.75rem; border-radius: 5px; margin: 0.75rem 0; font-size: 0.9rem; color: #856404;">
                                <strong>⏳ Đang chờ admin xác minh và duyệt</strong><br>
                                <small>Bài đăng sẽ hiển thị trên website sau khi được duyệt.</small>
                            </div>
                        <?php endif; ?>
                        <div class="property-details">
                            <span class="detail-item">
                                <span class="detail-icon">📐</span>
                                <span><?php echo number_format($property['area'], 1); ?> m²</span>
                            </span>
                            <span class="detail-item">
                                <span class="detail-icon">🛏️</span>
                                <span><?php echo $property['bedrooms']; ?> PN</span>
                            </span>
                            <span class="detail-item">
                                <span class="detail-icon">🚿</span>
                                <span><?php echo $property['bathrooms']; ?> WC</span>
                            </span>
                            <?php
                            $total_rooms = isset($property['total_rooms']) ? (int)$property['total_rooms'] : 1;
                            $rented_rooms = isset($property['rented_rooms']) ? (int)$property['rented_rooms'] : 0;
                            $available_rooms = $total_rooms - $rented_rooms;
                            if ($total_rooms > 1):
                            ?>
                            <span class="detail-item" style="background: <?php echo $available_rooms > 0 ? 'rgba(76, 175, 80, 0.1)' : 'rgba(255, 152, 0, 0.1)'; ?>; padding: 0.4rem 0.8rem; border-radius: 20px;">
                                <span class="detail-icon">🏠</span>
                                <span style="font-weight: 600; color: <?php echo $available_rooms > 0 ? '#2e7d32' : '#e65100'; ?>;">
                                    Còn: <?php echo $available_rooms; ?>/<?php echo $total_rooms; ?>
                                </span>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="property-price-wrapper">
                            <span class="property-price"><?php echo number_format($property['price']); ?> đ</span>
                            <span class="price-unit">/tháng</span>
                        </div>
                        <?php if ($property['status'] === 'available' || $property['status'] === 'rented'): ?>
                            <div style="margin-bottom: 0.75rem;">
                                <a href="?toggle_status=1&id=<?php echo $property['id']; ?>" 
                                   class="btn" 
                                   style="display: block; width: 100%; text-align: center; background: <?php echo $property['status'] === 'available' ? '#FF9800' : '#4CAF50'; ?>; color: white; padding: 0.6rem; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s;"
                                   onclick="return confirm('Bạn có chắc muốn đổi trạng thái thành <?php echo $property['status'] === 'available' ? 'Hết phòng' : 'Còn trống'; ?>?')">
                                    <?php echo $property['status'] === 'available' ? '🔒 Đánh dấu Hết phòng' : '✅ Đánh dấu Còn trống'; ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                            <a href="property_edit.php?id=<?php echo $property['id']; ?>" class="btn btn-primary" style="flex: 1; text-align: center;">✏️ Sửa</a>
                            <a href="../property_detail.php?id=<?php echo $property['id']; ?>" class="btn btn-secondary" style="flex: 1; text-align: center;" target="_blank">👁️ Xem</a>
                            <a href="?delete=1&id=<?php echo $property['id']; ?>" 
                               onclick="return confirm('Bạn có chắc muốn xóa bất động sản này?')"
                               class="btn btn-danger" style="flex: 1; text-align: center; background: #f44336;">🗑️ Xóa</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

