<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('landlord');

$currentUser = getCurrentUser();
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
    $total_rooms = isset($_POST['total_rooms']) ? max(1, (int)$_POST['total_rooms']) : 1;
    $rented_rooms = isset($_POST['rented_rooms']) ? max(0, (int)$_POST['rented_rooms']) : 0;
    
    // Đảm bảo rented_rooms không vượt quá total_rooms
    if ($rented_rooms > $total_rooms) {
        $rented_rooms = $total_rooms;
    }
    
    if (empty($title) || empty($address) || empty($district) || $price <= 0 || $area <= 0) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        // Properties mới sẽ ở trạng thái 'pending' chờ admin duyệt
        $status = 'pending';
        $insertParams = [
            ':landlord_id' => (int)$currentUser['id'],
            ':title' => $title,
            ':description' => $description !== '' ? $description : null,
            ':address' => $address,
            ':district' => $district,
            ':price' => $price,
            ':area' => $area,
            ':bedrooms' => $bedrooms,
            ':bathrooms' => $bathrooms,
            ':property_type' => $property_type,
            ':status' => $status,
        ];

        try {
            dbExecute(
                "INSERT INTO properties (landlord_id, title, description, address, district, price, area, bedrooms, bathrooms, property_type, total_rooms, rented_rooms, status)
                 VALUES (:landlord_id, :title, :description, :address, :district, :price, :area, :bedrooms, :bathrooms, :property_type, :total_rooms, :rented_rooms, :status)",
                $insertParams + [
                    ':total_rooms' => $total_rooms,
                    ':rented_rooms' => $rented_rooms,
                ]
            );
        } catch (Throwable $e) {
            // Tương thích schema cũ chưa có total_rooms/rented_rooms.
            $msg = $e->getMessage();
            $missingRoomsColumns = stripos($msg, 'total_rooms') !== false || stripos($msg, 'rented_rooms') !== false;
            if (!$missingRoomsColumns) {
                throw $e;
            }

            dbExecute(
                "INSERT INTO properties (landlord_id, title, description, address, district, price, area, bedrooms, bathrooms, property_type, status)
                 VALUES (:landlord_id, :title, :description, :address, :district, :price, :area, :bedrooms, :bathrooms, :property_type, :status)",
                $insertParams
            );
        }
        $new_property_id = dbScopeIdentity();
        if ($new_property_id <= 0) {
            // Fallback cho một số cấu hình SQL Server không trả SCOPE_IDENTITY ổn định.
            $new_property_id = (int)dbScalar(
                "SELECT TOP 1 id FROM properties WHERE landlord_id = :landlord_id ORDER BY id DESC",
                [':landlord_id' => (int)$currentUser['id']]
            );
        }

        if ($new_property_id > 0) {
            $images = [];
            
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
                        $uploaded_path = handleImageUpload($file, $new_property_id);
                        if ($uploaded_path) {
                            $images[] = $uploaded_path;
                        }
                    }
                }
            }
            
            // Xử lý URL ảnh
            if (!empty($_POST['image_urls'])) {
                $urls = explode("\n", trim($_POST['image_urls']));
                foreach ($urls as $url) {
                    $url = trim($url);
                    if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                        $images[] = $url;
                    }
                }
            }
            
            // Cập nhật images nếu có
            if (!empty($images)) {
                $images_json = json_encode($images);
                dbExecute(
                    "UPDATE properties SET images = :images WHERE id = :id",
                    [':images' => $images_json, ':id' => $new_property_id]
                );
            }

            $success = '✅ Thêm bất động sản thành công! ⏳ Bài đăng của bạn đang chờ admin xác minh và duyệt. Bạn có thể tiếp tục thêm bất động sản mới.';
        } else {
            // Đã insert thành công nhưng không lấy được id để gắn ảnh.
            $success = '✅ Đã thêm bất động sản, nhưng chưa thể cập nhật ảnh. Bạn có thể tiếp tục thêm bất động sản mới hoặc vào Sửa để thêm ảnh.';
        }
    }
}

$pageTitle = "Thêm bất động sản";
include '../includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <h2>➕ Thêm bất động sản</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border-left: 4px solid #4CAF50; padding: 1.5rem; border-radius: 8px; font-size: 1.05rem; line-height: 1.6;">
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                    <span style="font-size: 1.5rem;">✅</span>
                    <strong style="font-size: 1.1rem; color: #2c3e50;">Thêm bất động sản thành công!</strong>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem; color: #555; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(76, 175, 80, 0.2);">
                    <span style="font-size: 1.3rem;">⏳</span>
                    <div>
                        <strong>Bài đăng của bạn đang chờ admin xác minh và duyệt.</strong><br>
                        <small style="color: #666;">Bạn sẽ được thông báo khi bài đăng được duyệt và hiển thị trên website.</small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Tiêu đề *</label>
                <input type="text" id="title" name="title" required placeholder="VD: Căn hộ 2PN tại Thủ Đức">
            </div>
            
            <div class="form-group">
                <label for="description">Mô tả</label>
                <textarea id="description" name="description" placeholder="Mô tả chi tiết về bất động sản..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="address">Địa chỉ *</label>
                <input type="text" id="address" name="address" required placeholder="Số nhà, tên đường">
            </div>
            
            <div class="form-group">
                <label for="district">Quận/Huyện *</label>
                <select id="district" name="district" required>
                    <option value="">Chọn quận/huyện</option>
                    <option value="Thủ Đức">Thủ Đức</option>
                    <option value="Bình Thạnh">Bình Thạnh</option>
                    <option value="Gò Vấp">Gò Vấp</option>
                    <option value="Quận 1">Quận 1</option>
                    <option value="Quận 2">Quận 2</option>
                    <option value="Quận 3">Quận 3</option>
                    <option value="Quận 4">Quận 4</option>
                    <option value="Quận 5">Quận 5</option>
                    <option value="Quận 6">Quận 6</option>
                    <option value="Quận 7">Quận 7</option>
                    <option value="Quận 8">Quận 8</option>
                    <option value="Quận 9">Quận 9</option>
                    <option value="Quận 10">Quận 10</option>
                    <option value="Quận 11">Quận 11</option>
                    <option value="Quận 12">Quận 12</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="property_type">Loại BĐS *</label>
                <select id="property_type" name="property_type" required>
                    <option value="apartment">Căn hộ</option>
                    <option value="house">Nhà nguyên căn</option>
                    <option value="room">Phòng trọ</option>
                    <option value="studio">Studio</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="price">Giá thuê (VNĐ/tháng) *</label>
                <input type="number" id="price" name="price" required min="0" step="100000" placeholder="VD: 5000000">
            </div>
            
            <div class="form-group">
                <label for="area">Diện tích (m²) *</label>
                <input type="number" id="area" name="area" required min="0" step="0.1" placeholder="VD: 50">
            </div>
            
            <div class="form-group">
                <label for="bedrooms">Số phòng ngủ</label>
                <input type="number" id="bedrooms" name="bedrooms" min="0" value="0">
            </div>
            
            <div class="form-group">
                <label for="bathrooms">Số phòng tắm</label>
                <input type="number" id="bathrooms" name="bathrooms" min="0" value="0">
            </div>
            
            <div class="form-group">
                <label for="total_rooms">Tổng số phòng *</label>
                <input type="number" id="total_rooms" name="total_rooms" required min="1" value="1" placeholder="VD: 5">
                <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.5rem;">
                    💡 Tổng số phòng có thể cho thuê (VD: 5 phòng trọ, 3 căn hộ...)
                </small>
            </div>
            
            <div class="form-group">
                <label for="rented_rooms">Số phòng đã cho thuê</label>
                <input type="number" id="rented_rooms" name="rented_rooms" min="0" value="0" placeholder="VD: 2">
                <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.5rem;">
                    💡 Số phòng hiện đã được cho thuê. Số phòng còn trống = Tổng số phòng - Số phòng đã cho thuê
                </small>
            </div>
            
            <div class="form-group" id="rooms_status_display" style="padding: 1rem; background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-left: 4px solid #2196F3; border-radius: 8px; margin-top: 0.5rem;">
                <div style="font-weight: 600; color: #1976D2; margin-bottom: 0.5rem;">📊 Thông tin phòng:</div>
                <div id="rooms_info" style="color: #555;">
                    Số phòng còn trống: <strong id="available_rooms">1</strong> / <strong id="total_rooms_display">1</strong>
                </div>
            </div>
            
            <div class="form-group">
                <label for="images">📷 Tải ảnh từ máy tính</label>
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
            
            <button type="submit" class="btn btn-primary btn-block">Thêm bất động sản</button>
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
    
    document.getElementById('available_rooms').textContent = availableRooms;
    document.getElementById('total_rooms_display').textContent = totalRooms;
    
    // Cập nhật màu sắc dựa trên số phòng còn trống
    const statusDisplay = document.getElementById('rooms_status_display');
    if (availableRooms === 0) {
        statusDisplay.style.background = 'linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%)';
        statusDisplay.style.borderLeftColor = '#FF9800';
        statusDisplay.querySelector('div').style.color = '#e65100';
    } else {
        statusDisplay.style.background = 'linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%)';
        statusDisplay.style.borderLeftColor = '#4CAF50';
        statusDisplay.querySelector('div').style.color = '#2e7d32';
    }
    
    // Đảm bảo rented_rooms không vượt quá total_rooms
    if (rentedRooms > totalRooms) {
        document.getElementById('rented_rooms').value = totalRooms;
        updateRoomsStatus();
    }
}

document.getElementById('total_rooms').addEventListener('input', updateRoomsStatus);
document.getElementById('rented_rooms').addEventListener('input', updateRoomsStatus);
updateRoomsStatus(); // Khởi tạo lần đầu
</script>

<?php include '../includes/footer.php'; ?>

