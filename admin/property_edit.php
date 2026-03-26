<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

if ($property_id <= 0) {
    header("Location: properties.php");
    exit();
}

$conn = getDBConnection();

// Lấy thông tin property
$property = dbSelectOne("SELECT * FROM properties WHERE id = :id", [':id' => $property_id]);

if (!$property) {
    header("Location: properties.php");
    exit();
}

// Lấy danh sách landlord
$landlords = dbSelectAll("SELECT id, full_name, email FROM users WHERE role = 'landlord' ORDER BY full_name");

// Hàm xử lý upload ảnh
function handleImageUpload($file, $property_id) {
    // Đường dẫn tuyệt đối từ root của project
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
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'property_' . $property_id . '_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Trả về đường dẫn tương đối từ root của website
        return 'uploads/properties/' . $filename;
    }
    
    return null;
}

// Cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $landlord_id = (int)$_POST['landlord_id'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $area = isset($_POST['area']) ? (float)$_POST['area'] : 0;
    $bedrooms = isset($_POST['bedrooms']) ? (int)$_POST['bedrooms'] : 0;
    $bathrooms = isset($_POST['bathrooms']) ? (int)$_POST['bathrooms'] : 0;
    $property_type = $_POST['property_type'] ?? 'apartment';
    $status = $_POST['status'] ?? 'available';
    
    // Xử lý ảnh
    $images = [];
    
    // Lấy ảnh hiện tại
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
    
    // Giữ lại ảnh cũ nếu không xóa
    if (isset($_POST['keep_images']) && is_array($_POST['keep_images'])) {
        foreach ($_POST['keep_images'] as $keep_image) {
            if (in_array($keep_image, $current_images)) {
                $images[] = $keep_image;
            }
        }
    } elseif (empty($images)) {
        // Nếu không có ảnh mới và không chọn giữ ảnh cũ, giữ tất cả ảnh cũ
        $images = $current_images;
    }
    
    $images_json = !empty($images) ? json_encode($images) : null;
    
    if (empty($title) || empty($address) || empty($district) || $price <= 0 || $area <= 0) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        dbExecute(
            "UPDATE properties
             SET landlord_id = :landlord_id,
                 title = :title,
                 description = :description,
                 address = :address,
                 district = :district,
                 price = :price,
                 area = :area,
                 bedrooms = :bedrooms,
                 bathrooms = :bathrooms,
                 property_type = :property_type,
                 status = :status,
                 images = :images
             WHERE id = :id",
            [
                ':landlord_id' => $landlord_id,
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
                ':images' => $images_json,
                ':id' => $property_id,
            ]
        );
        
        if (true) {
            $success = 'Cập nhật thành công!';
            $property = array_merge($property, [
                'landlord_id' => $landlord_id,
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

// Lấy danh sách ảnh hiện tại
$current_images = !empty($property['images']) ? json_decode($property['images'], true) : [];
if (!is_array($current_images)) {
    $current_images = [];
}

$pageTitle = "Sửa Bất động sản";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1>✏️ Sửa Bất động sản</h1>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="content-card">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Người cho thuê *</label>
                    <select name="landlord_id" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem;">
                        <option value="">Chọn người cho thuê</option>
                        <?php foreach ($landlords as $landlord): ?>
                            <option value="<?php echo $landlord['id']; ?>" <?php echo $property['landlord_id'] == $landlord['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($landlord['full_name']); ?>
                                <?php if ($landlord['email']): ?>
                                    (<?php echo htmlspecialchars($landlord['email']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tiêu đề *</label>
                    <input type="text" name="title" required value="<?php echo htmlspecialchars($property['title']); ?>">
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" rows="4"><?php echo htmlspecialchars($property['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Địa chỉ *</label>
                    <input type="text" name="address" required value="<?php echo htmlspecialchars($property['address']); ?>">
                </div>
                <div class="form-group">
                    <label>Quận/Huyện *</label>
                    <select name="district" required>
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
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Loại BĐS *</label>
                        <select name="property_type" required>
                            <option value="apartment" <?php echo $property['property_type'] === 'apartment' ? 'selected' : ''; ?>>Căn hộ</option>
                            <option value="house" <?php echo $property['property_type'] === 'house' ? 'selected' : ''; ?>>Nhà nguyên căn</option>
                            <option value="room" <?php echo $property['property_type'] === 'room' ? 'selected' : ''; ?>>Phòng trọ</option>
                            <option value="studio" <?php echo $property['property_type'] === 'studio' ? 'selected' : ''; ?>>Studio</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái *</label>
                        <select name="status" required>
                            <option value="available" <?php echo $property['status'] === 'available' ? 'selected' : ''; ?>>Còn trống</option>
                            <option value="rented" <?php echo $property['status'] === 'rented' ? 'selected' : ''; ?>>Đã cho thuê</option>
                            <option value="pending" <?php echo $property['status'] === 'pending' ? 'selected' : ''; ?>>Đang chờ</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Giá thuê (VNĐ/tháng) *</label>
                        <input type="number" name="price" required min="0" step="100000" value="<?php echo $property['price']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Diện tích (m²) *</label>
                        <input type="number" name="area" required min="0" step="0.1" value="<?php echo $property['area']; ?>">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Số phòng ngủ</label>
                        <input type="number" name="bedrooms" min="0" value="<?php echo $property['bedrooms']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Số phòng tắm</label>
                        <input type="number" name="bathrooms" min="0" value="<?php echo $property['bathrooms']; ?>">
                    </div>
                </div>
                
                <!-- Phần quản lý ảnh -->
                <div class="form-group" style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e0e0e0;">
                    <label style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; display: block;">📷 Quản lý ảnh</label>
                    
                    <!-- Hiển thị ảnh hiện tại -->
                    <?php if (!empty($current_images)): ?>
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.5rem; color: #666;">Ảnh hiện tại (chọn để giữ lại):</label>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                                <?php foreach ($current_images as $img): ?>
                                    <div style="position: relative; border: 2px solid #ddd; border-radius: 8px; overflow: hidden;">
                                        <label style="display: block; cursor: pointer;">
                                            <input type="checkbox" name="keep_images[]" value="<?php echo htmlspecialchars($img); ?>" checked style="position: absolute; top: 5px; left: 5px; z-index: 10; width: 20px; height: 20px;">
                                            <img src="<?php echo htmlspecialchars($img); ?>" alt="Property image" style="width: 100%; height: 150px; object-fit: cover; display: block;">
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small style="color: #666; display: block; margin-top: 0.5rem;">Bỏ chọn để xóa ảnh</small>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Upload ảnh mới -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem;">Tải ảnh lên (có thể chọn nhiều):</label>
                        <input type="file" name="images[]" accept="image/*" multiple style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px;">
                        <small style="color: #666; display: block; margin-top: 0.5rem;">Chấp nhận: JPG, PNG, GIF, WEBP (tối đa 5MB mỗi ảnh)</small>
                    </div>
                    
                    <!-- Nhập URL ảnh -->
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem;">Hoặc nhập URL ảnh từ mạng (mỗi URL một dòng):</label>
                        <textarea name="image_urls" rows="4" placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-family: monospace; font-size: 0.9rem;"></textarea>
                        <small style="color: #666; display: block; margin-top: 0.5rem;">Nhập mỗi URL trên một dòng riêng</small>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                    <a href="../index.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

