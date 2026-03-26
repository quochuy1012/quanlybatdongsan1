<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Lấy tham số tìm kiếm
$keyword = trim($_GET['keyword'] ?? '');
$district = trim($_GET['district'] ?? '');
$property_type = trim($_GET['property_type'] ?? '');
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;

// Cập nhật thống kê tìm kiếm nếu có district
if (!empty($district)) {
    // Tăng lượt tìm theo từng request: ưu tiên UPDATE, nếu chưa có thì INSERT.
    $affected = dbExecute(
        "UPDATE search_statistics
         SET search_count = search_count + 1,
             last_searched = SYSUTCDATETIME()
         WHERE district = :district",
        [':district' => $district]
    );

    if ($affected === 0) {
        dbExecute(
            "INSERT INTO search_statistics (district, search_count, last_searched)
             VALUES (:district, 1, SYSUTCDATETIME())",
            [':district' => $district]
        );
    }
}

// Xây dựng query tìm kiếm
$query = "SELECT p.*, u.full_name as landlord_name 
          FROM properties p 
          JOIN users u ON p.landlord_id = u.id 
          WHERE p.status = 'available'";
$params = [];

if (!empty($keyword)) {
    $keyword_param = "%$keyword%";
    $query .= " AND (p.title LIKE :kw_title OR p.description LIKE :kw_desc OR p.address LIKE :kw_address)";
    $params[':kw_title'] = $keyword_param;
    $params[':kw_desc'] = $keyword_param;
    $params[':kw_address'] = $keyword_param;
}

if (!empty($district)) {
    $params[':district'] = $district;
    $query .= " AND p.district = :district";
}

if (!empty($property_type)) {
    $params[':ptype'] = $property_type;
    $query .= " AND p.property_type = :ptype";
}

if ($min_price !== null) {
    $params[':min_price'] = $min_price;
    $query .= " AND p.price >= :min_price";
}

if ($max_price !== null) {
    $params[':max_price'] = $max_price;
    $query .= " AND p.price <= :max_price";
}

$query .= " ORDER BY p.created_at DESC";
$properties = dbSelectAll($query, $params);

$pageTitle = "Kết quả tìm kiếm";
include 'includes/header.php';
?>

<div class="container">
    <!-- Header với thông tin tìm kiếm -->
    <div class="search-results-header">
        <div>
            <h2 class="section-title">🔍 Kết quả tìm kiếm</h2>
            <?php if (!empty($keyword) || !empty($district) || !empty($property_type)): ?>
                <div class="search-filters-applied">
                    <?php if (!empty($keyword)): ?>
                        <span class="filter-tag">Từ khóa: <strong><?php echo htmlspecialchars($keyword); ?></strong></span>
                    <?php endif; ?>
                    <?php if (!empty($district)): ?>
                        <span class="filter-tag">Khu vực: <strong><?php echo htmlspecialchars($district); ?></strong></span>
                    <?php endif; ?>
                    <?php if (!empty($property_type)): ?>
                        <span class="filter-tag">Loại: <strong>
                            <?php 
                            $types = ['apartment' => 'Căn hộ', 'house' => 'Nhà nguyên căn', 'room' => 'Phòng trọ', 'studio' => 'Studio'];
                            echo $types[$property_type] ?? $property_type;
                            ?>
                        </strong></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if (count($properties) > 0): ?>
            <div class="results-count">
                <span class="count-badge"><?php echo count($properties); ?></span>
                <span>kết quả</span>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (count($properties) > 0): ?>
        <div class="properties-grid">
            <?php 
            require_once 'config/image_helper.php';
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
                        </div>
                    </div>
                    <div class="property-info">
                        <h3 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h3>
                        <p class="property-location">
                            <span class="location-icon">📍</span>
                            <?php echo htmlspecialchars($property['address'] . ', ' . $property['district']); ?>
                        </p>
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
                        </div>
                        <div class="property-price-wrapper">
                            <span class="property-price"><?php echo number_format($property['price']); ?> đ</span>
                            <span class="price-unit">/tháng</span>
                        </div>
                        <a href="property_detail.php?id=<?php echo $property['id']; ?>" class="btn btn-primary btn-view-details">
                            Xem chi tiết →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="empty-state-actions" style="margin-top: 1.5rem;">
            <a href="index.php" class="btn btn-secondary">Quay lại</a>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">🔍</div>
            <h3>Không tìm thấy bất động sản nào</h3>
            <p>Không có bất động sản nào phù hợp với tiêu chí tìm kiếm của bạn. Vui lòng thử lại với các tiêu chí khác.</p>
            <div class="empty-state-actions">
                <a href="index.php" class="btn btn-primary">Quay lại</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

