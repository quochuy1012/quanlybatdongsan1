<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Lấy thống kê khu vực tìm kiếm nhiều nhất
$popularDistricts = dbSelectAll(
    "SELECT TOP 8 district, SUM(search_count) as total_searches
     FROM search_statistics
     GROUP BY district
     ORDER BY total_searches DESC"
);

// Lấy danh sách bất động sản mới nhất
$properties = dbSelectAll(
    "SELECT TOP 6 p.*, u.full_name as landlord_name
     FROM properties p
     JOIN users u ON p.landlord_id = u.id
     WHERE p.status = 'available'
     ORDER BY p.created_at DESC"
);

$pageTitle = "Trang chủ - Bất Động Sản";
include 'includes/header.php';
?>

<div class="hero">
    <h2>🏠 Bất động sản cho thuê tại TP.HCM</h2>
    <p>Hàng ngàn lựa chọn bất động sản cho thuê tại TP.HCM</p>
</div>

<div class="container">
    <!-- Thanh tìm kiếm -->
    <div class="search-section">
        <h2 class="section-title">🔍 Tìm kiếm bất động sản</h2>
        <form id="searchForm" action="search.php" method="GET" class="search-form">
            <div class="form-group">
                <label for="keyword">Từ khóa</label>
                <input type="text" id="keyword" name="keyword" placeholder="Nhập từ khóa tìm kiếm...">
            </div>
            <div class="form-group">
                <label for="district">Quận/Huyện</label>
                <select id="district" name="district">
                    <option value="">Tất cả</option>
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
                <label for="property_type">Loại BĐS</label>
                <select id="property_type" name="property_type">
                    <option value="">Tất cả</option>
                    <option value="apartment">Căn hộ</option>
                    <option value="house">Nhà nguyên căn</option>
                    <option value="room">Phòng trọ</option>
                    <option value="studio">Studio</option>
                </select>
            </div>
            <div class="form-group">
                <label for="min_price">Giá tối thiểu (VNĐ)</label>
                <input type="number" id="min_price" name="min_price" placeholder="0" min="0">
            </div>
            <div class="form-group">
                <label for="max_price">Giá tối đa (VNĐ)</label>
                <input type="number" id="max_price" name="max_price" placeholder="Không giới hạn" min="0">
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-block">Tìm kiếm</button>
            </div>
        </form>
    </div>

    <!-- Khu vực được tìm nhiều nhất -->
    <div class="popular-districts">
        <h2 class="section-title">📍 Khu vực được tìm kiếm nhiều nhất</h2>
        <div class="district-grid">
            <?php foreach ($popularDistricts as $district): ?>
                <div class="district-card">
                    <h3><?php echo htmlspecialchars($district['district']); ?></h3>
                    <div class="count"><?php echo number_format($district['total_searches']); ?> lượt tìm</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Biểu đồ thống kê -->
    <div class="chart-container">
        <h2 class="section-title">📊 Biểu đồ thống kê tìm kiếm theo khu vực</h2>
        <div class="chart-wrapper">
            <canvas id="districtChart"></canvas>
        </div>
    </div>

    <!-- Bất động sản mới nhất -->
    <div class="popular-districts">
        <h2 class="section-title">🏘️ Bất động sản mới nhất</h2>
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
    </div>
</div>

<script>
// Vẽ biểu đồ
const ctx = document.getElementById('districtChart');
if (ctx) {
    const districts = <?php echo json_encode(array_column($popularDistricts, 'district')); ?>;
    const searches = <?php echo json_encode(array_column($popularDistricts, 'total_searches')); ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: districts,
            datasets: [{
                label: 'Số lượt tìm kiếm',
                data: searches,
                backgroundColor: [
                    'rgba(76, 175, 80, 0.8)',
                    'rgba(255, 152, 0, 0.8)',
                    'rgba(33, 150, 243, 0.8)',
                    'rgba(156, 39, 176, 0.8)',
                    'rgba(244, 67, 54, 0.8)'
                ],
                borderColor: [
                    'rgba(76, 175, 80, 1)',
                    'rgba(255, 152, 0, 1)',
                    'rgba(33, 150, 243, 1)',
                    'rgba(156, 39, 176, 1)',
                    'rgba(244, 67, 54, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>

