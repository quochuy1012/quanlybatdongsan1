<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();
requireRole('admin');

$success = '';
$error = '';

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
    
    if (empty($title) || empty($address) || empty($district) || $price <= 0 || $area <= 0 || $landlord_id <= 0) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        // Insert property trước để lấy ID
        dbExecute(
            "INSERT INTO properties (landlord_id, title, description, address, district, price, area, bedrooms, bathrooms, property_type, status)
             VALUES (:landlord_id, :title, :description, :address, :district, :price, :area, :bedrooms, :bathrooms, :property_type, :status)",
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
            ]
        );
        $new_property_id = dbScopeIdentity();
        if ($new_property_id <= 0) {
            // Fallback cho một số cấu hình SQL Server không trả SCOPE_IDENTITY ổn định.
            $new_property_id = (int)dbScalar(
                "SELECT TOP 1 id FROM properties WHERE landlord_id = :landlord_id ORDER BY id DESC",
                [':landlord_id' => $landlord_id]
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
            $success = 'Thêm bất động sản thành công! Bạn có thể tiếp tục thêm bất động sản mới.';
        } else {
            // Đã insert thành công nhưng không lấy được id để gắn ảnh.
            $success = 'Đã thêm bất động sản, nhưng chưa thể cập nhật ảnh. Bạn có thể tiếp tục thêm bất động sản mới hoặc vào Sửa để thêm ảnh.';
        }
    }
}

$pageTitle = "Thêm Bất động sản";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/admin.css">

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1>➕ Thêm Bất động sản</h1>
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
                        <?php if (empty($landlords)): ?>
                            <option value="" disabled>Chưa có người cho thuê. Vui lòng <a href="landlords.php">thêm người cho thuê</a> trước.</option>
                        <?php else: ?>
                            <?php foreach ($landlords as $landlord): ?>
                                <option value="<?php echo $landlord['id']; ?>">
                                    <?php echo htmlspecialchars($landlord['full_name']); ?>
                                    <?php if ($landlord['email']): ?>
                                        (<?php echo htmlspecialchars($landlord['email']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php if (empty($landlords)): ?>
                        <p style="margin-top: 0.5rem; color: #ff9800; font-size: 0.9rem;">
                            ⚠️ Chưa có người cho thuê. <a href="landlords.php">Thêm người cho thuê ngay</a>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Tiêu đề *</label>
                    <input type="text" name="title" required placeholder="VD: Căn hộ 2PN tại Thủ Đức">
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" rows="4" placeholder="Mô tả chi tiết về bất động sản..."></textarea>
                </div>
                <div class="form-group">
                    <label>Địa chỉ *</label>
                    <input type="text" name="address" required placeholder="Số nhà, tên đường">
                </div>
                <div class="form-group">
                    <label>Quận/Huyện *</label>
                    <select name="district" required>
                        <option value="">Chọn quận/huyện</option>
                        <?php
                        $districts = ['Thủ Đức', 'Bình Thạnh', 'Gò Vấp', 'Quận 1', 'Quận 2', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6', 'Quận 7', 'Quận 8', 'Quận 9', 'Quận 10', 'Quận 11', 'Quận 12'];
                        foreach ($districts as $d):
                        ?>
                            <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Loại BĐS *</label>
                        <select name="property_type" required>
                            <option value="apartment">Căn hộ</option>
                            <option value="house">Nhà nguyên căn</option>
                            <option value="room">Phòng trọ</option>
                            <option value="studio">Studio</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái *</label>
                        <select name="status" required>
                            <option value="available">Còn trống</option>
                            <option value="rented">Đã cho thuê</option>
                            <option value="pending">Đang chờ</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Giá thuê (VNĐ/tháng) *</label>
                        <input type="number" name="price" required min="0" step="100000" placeholder="VD: 5000000">
                    </div>
                    <div class="form-group">
                        <label>Diện tích (m²) *</label>
                        <input type="number" name="area" required min="0" step="0.1" placeholder="VD: 50">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Số phòng ngủ</label>
                        <input type="number" name="bedrooms" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label>Số phòng tắm</label>
                        <input type="number" name="bathrooms" min="0" value="0">
                    </div>
                </div>
                
                <!-- Phần quản lý ảnh -->
                <div class="form-group" style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e0e0e0;">
                    <label style="font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; display: block;">📷 Quản lý ảnh</label>
                    
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
                    <button type="submit" class="btn btn-primary">Thêm BĐS</button>
                    <a href="../index.php" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

