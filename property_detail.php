<?php
require_once 'config/database.php';
require_once 'config/session.php';

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEmbedded = isset($_GET['embedded']) && $_GET['embedded'] === '1';
$backUrl = trim($_GET['back'] ?? 'index.php');
$backUrl = preg_match('/^(https?:|\/\/)/i', $backUrl) ? 'index.php' : ltrim($backUrl, '/');
if ($backUrl === '') {
    $backUrl = 'index.php';
}

if ($property_id <= 0) {
    header("Location: index.php");
    exit();
}

$property = dbSelectOne(
    "SELECT p.*, u.full_name as landlord_name, u.phone as landlord_phone, u.email as landlord_email
     FROM properties p
     JOIN users u ON p.landlord_id = u.id
     WHERE p.id = :id",
    [':id' => $property_id]
);

if (!$property) {
    header("Location: index.php");
    exit();
}

$pageTitle = $property['title'];
if (!$isEmbedded) {
    include 'includes/header.php';
}
?>

<?php if ($isEmbedded): ?>
<style>
    body { margin: 0; background: #fff; }
    .container { max-width: 100%; padding: 1rem 1.25rem; }
</style>
<?php endif; ?>

<div class="container">
    <div class="property-card" style="max-width: 900px; margin: 0 auto;">
        <?php
        require_once 'config/image_helper.php';
        // Lấy ảnh từ database
        $images = getImagesArray($property['images'] ?? '');
        // Chuyển đổi tất cả ảnh sang URL đầy đủ
        $images = array_map('getImageUrl', $images);
        ?>
        
        <!-- Hiển thị ảnh -->
        <?php if (!empty($images)): ?>
            <div style="margin-bottom: 2rem;">
                <!-- Ảnh chính -->
                <div style="margin-bottom: 1rem;">
                    <img id="mainImage" 
                         src="<?php echo htmlspecialchars($images[0]); ?>" 
                         alt="<?php echo htmlspecialchars($property['title']); ?>" 
                         style="width: 100%; height: 400px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);" 
                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'50\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3E🏠%3C/text%3E%3C/svg%3E';">
                </div>
                
                <!-- Thumbnail ảnh -->
                <?php if (count($images) > 1): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.5rem;">
                        <?php foreach ($images as $index => $img): ?>
                            <img src="<?php echo htmlspecialchars($img); ?>" 
                                 alt="Ảnh <?php echo $index + 1; ?>" 
                                 onclick="document.getElementById('mainImage').src = this.src;"
                                 style="width: 100%; height: 80px; object-fit: cover; border-radius: 5px; cursor: pointer; border: 2px solid transparent; transition: all 0.3s;"
                                 onmouseover="this.style.borderColor='#4CAF50';"
                                 onmouseout="this.style.borderColor='transparent';"
                                 onerror="this.style.display='none';">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="property-image" style="height: 300px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; border-radius: 8px; margin-bottom: 2rem; font-size: 4rem;">🏠</div>
        <?php endif; ?>
        
        <div class="property-info">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                <div style="flex: 1;">
                    <h1 class="property-title" style="font-size: 2rem; margin-bottom: 0.5rem; color: #2c3e50;"><?php echo htmlspecialchars($property['title']); ?></h1>
                    <p class="property-location" style="font-size: 1.1rem; color: #666; display: flex; align-items: center; gap: 0.5rem;">
                        <span>📍</span>
                        <span><?php echo htmlspecialchars($property['address'] . ', ' . $property['district']); ?></span>
                    </p>
                </div>
                <div class="property-price" style="text-align: right;">
                    <div style="font-size: 2.5rem; font-weight: bold; color: #4CAF50; line-height: 1.2;">
                        <?php echo number_format($property['price']); ?> đ
                    </div>
                    <div style="color: #999; font-size: 1rem;">/tháng</div>
                </div>
            </div>
            
            <div class="property-details" style="margin: 2rem 0; padding: 1.5rem; background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: space-between;">
                    <div style="flex: 1; min-width: 150px; text-align: center; padding: 1.5rem 1rem; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">📐</div>
                        <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; font-weight: 500;">Diện tích</div>
                        <div style="font-size: 1.4rem; font-weight: bold; color: #2c3e50;"><?php echo number_format($property['area'], 1); ?> m²</div>
                    </div>
                    <div style="flex: 1; min-width: 150px; text-align: center; padding: 1.5rem 1rem; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🛏️</div>
                        <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; font-weight: 500;">Phòng ngủ</div>
                        <div style="font-size: 1.4rem; font-weight: bold; color: #2c3e50;"><?php echo $property['bedrooms']; ?> PN</div>
                    </div>
                    <div style="flex: 1; min-width: 150px; text-align: center; padding: 1.5rem 1rem; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🚿</div>
                        <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; font-weight: 500;">Phòng tắm</div>
                        <div style="font-size: 1.4rem; font-weight: bold; color: #2c3e50;"><?php echo $property['bathrooms']; ?> WC</div>
                    </div>
                    <div style="flex: 1; min-width: 150px; text-align: center; padding: 1.5rem 1rem; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🏘️</div>
                        <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; font-weight: 500;">Loại BĐS</div>
                        <div style="font-size: 1.4rem; font-weight: bold; color: #2c3e50;">
                            <?php 
                            $types = ['apartment' => 'Căn hộ', 'house' => 'Nhà nguyên căn', 'room' => 'Phòng trọ', 'studio' => 'Studio'];
                            echo $types[$property['property_type']] ?? $property['property_type'];
                            ?>
                        </div>
                    </div>
                    <?php
                    $total_rooms = isset($property['total_rooms']) ? (int)$property['total_rooms'] : 1;
                    $rented_rooms = isset($property['rented_rooms']) ? (int)$property['rented_rooms'] : 0;
                    $available_rooms = $total_rooms - $rented_rooms;
                    if ($total_rooms > 1):
                    ?>
                    <div style="flex: 1; min-width: 150px; text-align: center; padding: 1.5rem 1rem; background: <?php echo $available_rooms > 0 ? 'linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%)' : 'linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%)'; ?>; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 2px solid <?php echo $available_rooms > 0 ? '#4CAF50' : '#FF9800'; ?>;">
                        <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🏠</div>
                        <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; font-weight: 500;">Số phòng</div>
                        <div style="font-size: 1.4rem; font-weight: bold; color: <?php echo $available_rooms > 0 ? '#2e7d32' : '#e65100'; ?>;">
                            Còn: <?php echo $available_rooms; ?>/<?php echo $total_rooms; ?>
                        </div>
                        <div style="font-size: 0.85rem; color: #666; margin-top: 0.5rem;">
                            <?php echo $available_rooms > 0 ? '✅ Còn trống' : '🔒 Hết phòng'; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($property['description'])): ?>
                <div style="margin: 2rem 0; padding: 1.5rem; background: #f8f9fa; border-radius: 12px; border-left: 4px solid #4CAF50;">
                    <h3 style="font-size: 1.5rem; margin-bottom: 1rem; color: #2c3e50; display: flex; align-items: center; gap: 0.5rem;">
                        <span>📝</span>
                        <span>Mô tả</span>
                    </h3>
                    <p style="line-height: 1.8; color: #555; font-size: 1.05rem; white-space: pre-wrap;">
                        <?php echo nl2br(htmlspecialchars($property['description'])); ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <div style="margin: 2rem 0; padding: 1.5rem; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <h3 style="font-size: 1.5rem; margin-bottom: 1.5rem; color: #2c3e50; display: flex; align-items: center; gap: 0.5rem;">
                    <span>👤</span>
                    <span>Thông tin người cho thuê</span>
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div style="padding: 1rem; background: white; border-radius: 8px;">
                        <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">Tên</div>
                        <div style="font-size: 1.1rem; font-weight: 600; color: #2c3e50;"><?php echo htmlspecialchars($property['landlord_name']); ?></div>
                    </div>
                    <?php if ($property['landlord_phone']): ?>
                        <div style="padding: 1rem; background: white; border-radius: 8px;">
                            <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">Điện thoại</div>
                            <div style="font-size: 1.1rem; font-weight: 600; color: #2c3e50;">
                                <a href="tel:<?php echo htmlspecialchars($property['landlord_phone']); ?>" style="color: #4CAF50; text-decoration: none;">
                                    <?php echo htmlspecialchars($property['landlord_phone']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($property['landlord_email']): ?>
                        <div style="padding: 1rem; background: white; border-radius: 8px;">
                            <div style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">Email</div>
                            <div style="font-size: 1.1rem; font-weight: 600; color: #2c3e50;">
                                <a href="mailto:<?php echo htmlspecialchars($property['landlord_email']); ?>" style="color: #4CAF50; text-decoration: none;">
                                    <?php echo htmlspecialchars($property['landlord_email']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!$isEmbedded): ?>
                <div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
                    <?php if (isLoggedIn() && hasRole('tenant')): ?>
                        <a href="appointment_create.php?property_id=<?php echo $property['id']; ?>" class="btn btn-primary" style="flex: 1; min-width: 200px; padding: 1rem; font-size: 1.1rem; font-weight: 600; text-align: center; border-radius: 10px; box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);">
                            📅 Đặt lịch hẹn xem nhà
                        </a>
                    <?php elseif (!isLoggedIn()): ?>
                        <div style="flex: 1; padding: 1rem; background: #fff3cd; border: 2px solid #ffc107; border-radius: 10px; text-align: center;">
                            <p style="margin: 0; color: #856404; font-size: 1rem;">
                                <a href="login.php" style="color: #4CAF50; font-weight: 600; text-decoration: none;">Đăng nhập</a> để đặt lịch hẹn xem nhà
                            </p>
                        </div>
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars($backUrl); ?>" class="btn btn-secondary" style="padding: 1rem 2rem; font-size: 1rem; border-radius: 10px;">Quay lại</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$isEmbedded): ?>
    <?php include 'includes/footer.php'; ?>
<?php endif; ?>

