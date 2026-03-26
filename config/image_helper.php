<?php
/**
 * Helper functions for image handling
 */

/**
 * Lấy đường dẫn ảnh đầy đủ để hiển thị
 * @param string $image_path Đường dẫn ảnh từ database (có thể là local path hoặc URL)
 * @return string Đường dẫn ảnh đầy đủ
 */
function getImageUrl($image_path) {
    if (empty($image_path)) {
        return null;
    }
    
    // Nếu là URL (bắt đầu bằng http:// hoặc https://)
    if (preg_match('/^https?:\/\//i', $image_path)) {
        return $image_path;
    }
    
    // Chuẩn hóa đường dẫn local theo base URL của project hiện tại.
    $relativePath = ltrim(str_replace('\\', '/', $image_path), '/');
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = rtrim($scriptDir, '/');

    // Nếu đang ở /admin, /landlord, /tenant thì lùi lên 1 cấp để về root project.
    if (preg_match('#/(admin|landlord|tenant)$#', $scriptDir)) {
        $basePath = dirname($scriptDir);
    } else {
        $basePath = $scriptDir;
    }

    if ($basePath === '\\' || $basePath === '.') {
        $basePath = '';
    }

    $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
    return ($basePath !== '' ? $basePath : '') . '/' . $relativePath;
}

/**
 * Kiểm tra ảnh có tồn tại không (chỉ cho đường dẫn local)
 * @param string $image_path Đường dẫn ảnh
 * @return bool
 */
function imageExists($image_path) {
    if (empty($image_path)) {
        return false;
    }
    
    // Nếu là URL, luôn trả về true (không kiểm tra được)
    if (preg_match('/^https?:\/\//i', $image_path)) {
        return true;
    }
    
    // Kiểm tra file local
    $full_path = dirname(__DIR__) . '/' . ltrim($image_path, '/');
    return file_exists($full_path);
}

/**
 * Lấy danh sách ảnh từ JSON string
 * @param string $images_json JSON string từ database
 * @return array Mảng các đường dẫn ảnh
 */
function getImagesArray($images_json) {
    if (empty($images_json)) {
        return [];
    }
    
    $images = json_decode($images_json, true);
    if (!is_array($images)) {
        return [];
    }
    
    // Lọc bỏ các giá trị rỗng
    return array_filter($images, function($img) {
        return !empty($img);
    });
}

