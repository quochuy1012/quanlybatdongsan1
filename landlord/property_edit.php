<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('landlord');

$currentUser = getCurrentUser();
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

// Hàm xử lý upload ảnh
function handleImageUpload($file, $property_id) {
    $upload_dir = dirname(__DIR__) . '/uploads/properties/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        return null;
    }
    
    if ($file['size'] > $max_size) {
        return null;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'property_' . $property_id . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'uploads/properties/' . $filename;
    }
    
    return null;
}

if ($property_id <= 0) {
    header("Location: properties.php");
    exit();
}

// Lấy thông tin property
$property = dbSelectOne(
    "SELECT * FROM properties WHERE id = :id AND landlord_id = :landlord_id",
    [':id' => $property_id, ':landlord_id' => (int)$currentUser['id']]
);

if (!$property) {
    header("Location: properties.php");
    exit();
}

// Cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $area = isset($_POST['area']) ? (float)$_POST['area'] : 0;
    $bedrooms = isset($_POST['bedrooms']) ? (int)$_POST['bedrooms'] : 0;
    $bathrooms = isset($_POST['bathrooms']) ? (int)$_POST['bathrooms'] : 0;
    $property_type = $_POST['property_type'] ?? 'apartment';
    $total_rooms = isset($_POST['total_rooms']) ? max(1, (int)$_POST['total_rooms']) : (isset($property['total_rooms']) ? (int)$property['total_rooms'] : 1);
    $rented_rooms = isset($_POST['rented_rooms']) ? max(0, (int)$_POST['rented_rooms']) : (isset($property['rented_rooms']) ? (int)$property['rented_rooms'] : 0);
    $new_status = isset($_POST['status']) ? $_POST['status'] : $property['status'];
    
    // Đảm bảo rented_rooms không vượt quá total_rooms
    if ($rented_rooms > $total_rooms) {
        $rented_rooms = $total_rooms;
    }
    
    // Tính số phòng còn trống
    $available_rooms = $total_rooms - $rented_rooms;
    
    $current_status = $property['status'];
    
    // Kiểm tra xem có thay đổi thông tin khác ngoài status không
    $info_changed = (
        $title !== $property['title'] ||
        $description !== ($property['description'] ?? '') ||
        $address !== $property['address'] ||
        $district !== $property['district'] ||
        $price != $property['price'] ||
        $area != $property['area'] ||
        $bedrooms != $property['bedrooms'] ||
        $bathrooms != $property['bathrooms'] ||
        $property_type !== $property['property_type'] ||
        $total_rooms != (isset($property['total_rooms']) ? (int)$property['total_rooms'] : 1) ||
        $rented_rooms != (isset($property['rented_rooms']) ? (int)$property['rented_rooms'] : 0)
    );
    
    // Xử lý ảnh
    $images = [];
    $current_images = !empty($property['images']) ? json_decode($property['images'], true) : [];
    if (!is_array($current_images)) { 
        $current_images = []; 
    }

    // Xử lý upload ảnh mới
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
            if (!empty($name)) {
                $file = [
                    'name' => $_FILES['images']['name'][$key],
                    'type' => $_FILES['images']['type'][$key],
                    'tmp_name' => $_FILES['images']['tmp_name'][$key],
                    'error' => $_FILES['images']['error'][$key],
                    'size' => $_FILES['images']['size'][$key]
                ];
                $uploaded_path = handleImageUpload($file, $property_id);
                if ($uploaded_path) {
                    $images[] = $uploaded_path;
                }
            }
        }
    }
    
    // Xử lý URL ảnh mới
    if (!empty($_POST['image_urls'])) {
        $urls = explode("\n", trim($_POST['image_urls']));
        foreach ($urls as $url) {
            $url = trim($url);
            if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                $images[] = $url;
            }
        }
    }
    
    // Giữ lại ảnh cũ nếu được chọn
    if (isset($_POST['keep_images']) && is_array($_POST['keep_images'])) {
        foreach ($_POST['keep_images'] as $keep_index) {
            if (isset($current_images[$keep_index])) {
                $images[] = $current_images[$keep_index];
            }
        }
    } elseif (empty($images)) {
        // Nếu không có ảnh mới và không chọn giữ ảnh cũ, giữ tất cả ảnh cũ
        $images = $current_images;
    }
    
    $images_json = !empty($images) ? json_encode($images, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $current_images_json = !empty($property['images']) ? $property['images'] : null;
    
    // Kiểm tra xem có thay đổi ảnh không
    $images_changed = ($images_json !== $current_images_json);
    
    // Xử lý logic status (sau khi đã xử lý ảnh để có thể kiểm tra images_changed)
    // Tự động set status dựa trên số phòng còn trống (chỉ khi đã được duyệt và chỉ thay đổi số phòng)
    if ($current_status === 'pending') {
        // Nếu đang pending, giữ nguyên
        $status = $current_status;
    } elseif ($current_status === 'available' || $current_status === 'rented') {
        // Nếu đã được duyệt
        if ($info_changed || $images_changed) {
            // Có thay đổi thông tin hoặc ảnh -> chuyển về pending để admin duyệt lại
            $status = 'pending';
        } else {
            // Chỉ thay đổi status hoặc số phòng -> tự động set status dựa trên số phòng còn trống
            if ($available_rooms <= 0) {
                // Hết phòng -> tự động set thành 'rented'
                $status = 'rented';
            } else {
                // Còn phòng -> tự động set thành 'available' hoặc giữ status hiện tại nếu user chọn
                if ($new_status === 'available' || $new_status === 'rented') {
                    // Nếu user chọn status thủ công, ưu tiên lựa chọn của user
                    // Nhưng nếu available_rooms > 0 mà user chọn 'rented' thì vẫn cho phép (có thể họ muốn tạm dừng cho thuê)
                    $status = $new_status;
                } else {
                    // Tự động set dựa trên số phòng
                    $status = 'available';
                }
            }
        }
    } else {
        $status = $current_status;
    }
    
    if (empty($title) || empty($address) || empty($district) || $price <= 0 || $area <= 0) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        $rows = dbExecute(
            "UPDATE properties
             SET title = :title,
                 description = :description,
                 address = :address,
                 district = :district,
                 price = :price,
                 area = :area,
                 bedrooms = :bedrooms,
                 bathrooms = :bathrooms,
                 property_type = :property_type,
                 total_rooms = :total_rooms,
                 rented_rooms = :rented_rooms,
                 status = :status,
                 images = :images
             WHERE id = :id AND landlord_id = :landlord_id",
            [
                ':title' => $title,
                ':description' => $description,
                ':address' => $address,
                ':district' => $district,
                ':price' => $price,
                ':area' => $area,
                ':bedrooms' => $bedrooms,
                ':bathrooms' => $bathrooms,
                ':property_type' => $property_type,
                ':total_rooms' => $total_rooms,
                ':rented_rooms' => $rented_rooms,
                ':status' => $status,
                ':images' => $images_json,
                ':id' => $property_id,
                ':landlord_id' => (int)$currentUser['id'],
            ]
        );

        if ($rows >= 0) {
            if (($info_changed || $images_changed) && ($current_status === 'available' || $current_status === 'rented')) {
                $success = 'Cập nhật thành công! ⏳ Bài đăng đã được chuyển về trạng thái chờ duyệt. Admin sẽ xác minh và duyệt lại trong thời gian sớm nhất.';
            } elseif ($current_status !== $status && ($status === 'available' || $status === 'rented')) {
                $status_label = $status === 'available' ? 'Còn trống' : 'Hết phòng';
                $success = "Cập nhật trạng thái thành công! ✅ Trạng thái đã được đổi thành: {$status_label}";
            } else {
                $success = 'Cập nhật thành công!';
            }
            $property = array_merge($property, [
                'title' => $title,
                'description' => $description,
                'address' => $address,
                'district' => $district,
                'price' => $price,
                'area' => $area,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'property_type' => $property_type,
                'status' => $status,
                'images' => $images_json
            ]);
        } else {
            $error = 'Cập nhật thất bại!';
        }
    }
}

$pageTitle = "Sửa bất động sản";
include '../includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <h2>✏️ Sửa bất động sản</h2>
        
        <?php if ($success): ?>
            <?php if (strpos($success, 'chờ duyệt') !== false): ?>
                <div class="alert alert-success" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-left: 4px solid #2196F3; padding: 1.5rem; border-radius: 8px; font-size: 1.05rem; line-height: 1.6;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">✅</span>
                        <strong style="font-size: 1.1rem; color: #2c3e50;">Cập nhật thành công!</strong>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; color: #555; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(33, 150, 243, 0.2);">
                        <span style="font-size: 1.3rem;">⏳</span>
                        <div>
                            <strong>Bài đăng đã được chuyển về trạng thái chờ duyệt.</strong><br>
                            <small style="color: #666;">Admin sẽ xác minh và duyệt lại trong thời gian sớm nhất. Bài đăng sẽ hiển thị trên website sau khi được duyệt.</small>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Tiêu đề *</label>
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($property['title']); ?>">
            </div>
            
            <div class="form-group">
                <label for="description">Mô tả</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($property['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="address">Địa chỉ *</label>
                <input type="text" id="address" name="address" required value="<?php echo htmlspecialchars($property['address']); ?>">
            </div>
            
            <div class="form-group">
                <label for="district">Quận/Huyện *</label>
                <select id="district" name="district" required>
                    <option value="">Chọn quận/huyện</option>
                    <?php
                    $districts = ['Thủ Đức', 'Bình Thạnh', 'Gò Vấp', 'Quận 1', 'Quận 2', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6', 'Quận 7', 'Quận 8', 'Quận 9', 'Quận 10', 'Quận 11', 'Quận 12'];
                    foreach ($districts as $d):
                    ?>
                        <option value="<?php echo $d; ?>" <?php echo $property['district'] === $d ? 'selected' : ''; ?>>
                            <?php echo $d; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="property_type">Loại BĐS *</label>
                <select id="property_type" name="property_type" required>
                    <option value="apartment" <?php echo $property['property_type'] === 'apartment' ? 'selected' : ''; ?>>Căn hộ</option>
                    <option value="house" <?php echo $property['property_type'] === 'house' ? 'selected' : ''; ?>>Nhà nguyên căn</option>
                    <option value="room" <?php echo $property['property_type'] === 'room' ? 'selected' : ''; ?>>Phòng trọ</option>
                    <option value="studio" <?php echo $property['property_type'] === 'studio' ? 'selected' : ''; ?>>Studio</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="price">Giá thuê (VNĐ/tháng) *</label>
                <input type="number" id="price" name="price" required min="0" step="100000" value="<?php echo $property['price']; ?>">
            </div>
            
            <div class="form-group">
                <label for="area">Diện tích (m²) *</label>
                <input type="number" id="area" name="area" required min="0" step="0.1" value="<?php echo $property['area']; ?>">
            </div>
            
            <div class="form-group">
                <label for="bedrooms">Số phòng ngủ</label>
                <input type="number" id="bedrooms" name="bedrooms" min="0" value="<?php echo $property['bedrooms']; ?>">
            </div>
            
            <div class="form-group">
                <label for="bathrooms">Số phòng tắm</label>
                <input type="number" id="bathrooms" name="bathrooms" min="0" value="<?php echo $property['bathrooms']; ?>">
            </div>
            
            <?php
            // Lấy giá trị total_rooms và rented_rooms (nếu có)
            $total_rooms = isset($property['total_rooms']) ? (int)$property['total_rooms'] : 1;
            $rented_rooms = isset($property['rented_rooms']) ? (int)$property['rented_rooms'] : 0;
            $available_rooms = $total_rooms - $rented_rooms;
            ?>
            
            <div class="form-group">
                <label for="total_rooms">Tổng số phòng *</label>
                <input type="number" id="total_rooms" name="total_rooms" required min="1" value="<?php echo $total_rooms; ?>" placeholder="VD: 5">
                <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.5rem;">
                    💡 Tổng số phòng có thể cho thuê (VD: 5 phòng trọ, 3 căn hộ...)
                </small>
            </div>
            
            <div class="form-group">
                <label for="rented_rooms">Số phòng đã cho thuê</label>
                <input type="number" id="rented_rooms" name="rented_rooms" min="0" value="<?php echo $rented_rooms; ?>" placeholder="VD: 2">
                <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.5rem;">
                    💡 Số phòng hiện đã được cho thuê. Số phòng còn trống = Tổng số phòng - Số phòng đã cho thuê
                </small>
            </div>
            
            <div class="form-group" id="rooms_status_display" style="padding: 1rem; background: linear-gradient(135deg, <?php echo $available_rooms > 0 ? '#e8f5e9 0%, #c8e6c9 100%' : '#fff3e0 0%, #ffe0b2 100%'; ?>); border-left: 4px solid <?php echo $available_rooms > 0 ? '#4CAF50' : '#FF9800'; ?>; border-radius: 8px; margin-top: 0.5rem;">
                <div style="font-weight: 600; color: <?php echo $available_rooms > 0 ? '#2e7d32' : '#e65100'; ?>; margin-bottom: 0.5rem;">📊 Thông tin phòng:</div>
                <div id="rooms_info" style="color: #555;">
                    Số phòng còn trống: <strong id="available_rooms"><?php echo $available_rooms; ?></strong> / <strong id="total_rooms_display"><?php echo $total_rooms; ?></strong>
                    <?php if ($available_rooms <= 0): ?>
                        <span style="color: #e65100; font-weight: 600; margin-left: 0.5rem;">(Hết phòng)</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="status">Trạng thái</label>
                <?php if ($property['status'] === 'pending'): ?>
                    <select id="status" name="status" disabled style="background: #f5f5f5; cursor: not-allowed;">
                        <option value="pending" selected>⏳ Đang chờ duyệt</option>
                    </select>
                    <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.5rem;">
                        ⚠️ Bài đăng đang chờ admin duyệt. Bạn không thể thay đổi trạng thái lúc này.
                    </small>
                <?php elseif ($property['status'] === 'available' || $property['status'] === 'rented'): ?>
                    <select id="status" name="status">
                        <option value="available" <?php echo $property['status'] === 'available' ? 'selected' : ''; ?>>✅ Còn trống</option>
                        <option value="rented" <?php echo $property['status'] === 'rented' ? 'selected' : ''; ?>>🔒 Hết phòng</option>
                    </select>
                    <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.5rem;">
                        💡 <strong>Lưu ý:</strong> Nếu bạn chỉ thay đổi trạng thái (Còn trống ↔ Hết phòng), thay đổi sẽ được áp dụng ngay lập tức. Nếu bạn sửa thông tin khác (tiêu đề, địa chỉ, giá, v.v.), bài đăng sẽ tự động chuyển về trạng thái "Chờ duyệt" và cần admin xác minh lại.
                    </small>
                <?php else: ?>
                    <select id="status" name="status" disabled style="background: #f5f5f5; cursor: not-allowed;">
                        <option value="<?php echo htmlspecialchars($property['status']); ?>" selected><?php echo htmlspecialchars($property['status']); ?></option>
                    </select>
                <?php endif; ?>
            </div>
            
            <?php
            require_once '../config/image_helper.php';
            $current_images = getImagesArray($property['images'] ?? '');
            ?>
            
            <?php if (!empty($current_images)): ?>
                <div class="form-group">
                    <label>🖼️ Ảnh hiện tại</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; margin-top: 0.5rem;">
                        <?php foreach ($current_images as $index => $img): ?>
                            <div style="position: relative; border: 2px solid #ddd; border-radius: 8px; overflow: hidden;">
                                <img src="<?php echo htmlspecialchars(getImageUrl($img)); ?>" 
                                     alt="Ảnh <?php echo $index + 1; ?>" 
                                     style="width: 100%; height: 100px; object-fit: cover; display: block;"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'120\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'120\' height=\'100\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'30\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3E🖼️%3C/text%3E%3C/svg%3E';">
                                <label style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: white; padding: 0.5rem; text-align: center; cursor: pointer; font-size: 0.85rem;">
                                    <input type="checkbox" name="keep_images[]" value="<?php echo $index; ?>" checked style="margin-right: 0.25rem;">
                                    Giữ lại
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.5rem;">
                        Bỏ chọn "Giữ lại" để xóa ảnh không cần thiết.
                    </small>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="images">📷 Tải ảnh mới từ máy tính</label>
                <input type="file" id="images" name="images[]" accept="image/*" multiple>
                <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.5rem;">
                    Có thể chọn nhiều ảnh (JPG, PNG, GIF, WebP). Tối đa 5MB mỗi ảnh.
                </small>
            </div>
            
            <div class="form-group">
                <label for="image_urls">🌐 Hoặc nhập đường dẫn ảnh từ mạng</label>
                <textarea id="image_urls" name="image_urls" rows="4" placeholder="Nhập URL ảnh, mỗi URL một dòng&#10;VD:&#10;https://example.com/image1.jpg&#10;https://example.com/image2.jpg"></textarea>
                <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.5rem;">
                    Nhập URL ảnh từ internet, mỗi URL một dòng.
                </small>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Cập nhật</button>
            <a href="properties.php" class="btn btn-secondary btn-block" style="margin-top: 0.5rem;">Hủy</a>
        </form>
    </div>
</div>

<script>
// Tự động tính số phòng còn trống
function updateRoomsStatus() {
    const totalRooms = parseInt(document.getElementById('total_rooms').value) || 1;
    const rentedRooms = parseInt(document.getElementById('rented_rooms').value) || 0;
    const availableRooms = Math.max(0, totalRooms - rentedRooms);
    
    if (document.getElementById('available_rooms')) {
        document.getElementById('available_rooms').textContent = availableRooms;
    }
    if (document.getElementById('total_rooms_display')) {
        document.getElementById('total_rooms_display').textContent = totalRooms;
    }
    
    // Cập nhật màu sắc dựa trên số phòng còn trống
    const statusDisplay = document.getElementById('rooms_status_display');
    const roomsInfo = document.getElementById('rooms_info');
    
    if (statusDisplay && roomsInfo) {
        if (availableRooms === 0) {
            statusDisplay.style.background = 'linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%)';
            statusDisplay.style.borderLeftColor = '#FF9800';
            statusDisplay.querySelector('div').style.color = '#e65100';
            
            // Thêm hoặc cập nhật text "Hết phòng"
            if (!roomsInfo.querySelector('.hết-phòng-text')) {
                const hếtPhòngText = document.createElement('span');
                hếtPhòngText.className = 'hết-phòng-text';
                hếtPhòngText.style.cssText = 'color: #e65100; font-weight: 600; margin-left: 0.5rem;';
                hếtPhòngText.textContent = '(Hết phòng)';
                roomsInfo.appendChild(hếtPhòngText);
            }
        } else {
            statusDisplay.style.background = 'linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%)';
            statusDisplay.style.borderLeftColor = '#4CAF50';
            statusDisplay.querySelector('div').style.color = '#2e7d32';
            
            // Xóa text "Hết phòng" nếu có
            const hếtPhòngText = roomsInfo.querySelector('.hết-phòng-text');
            if (hếtPhòngText) {
                hếtPhòngText.remove();
            }
        }
    }
    
    // Đảm bảo rented_rooms không vượt quá total_rooms
    if (rentedRooms > totalRooms) {
        document.getElementById('rented_rooms').value = totalRooms;
        updateRoomsStatus();
    }
}

if (document.getElementById('total_rooms') && document.getElementById('rented_rooms')) {
    document.getElementById('total_rooms').addEventListener('input', updateRoomsStatus);
    document.getElementById('rented_rooms').addEventListener('input', updateRoomsStatus);
    updateRoomsStatus(); // Khởi tạo lần đầu
}
</script>

<?php include '../includes/footer.php'; ?>

