-- Migration: Thêm trường số lượng phòng vào bảng properties
-- Chạy file này trong phpMyAdmin hoặc MySQL client

ALTER TABLE `properties` 
ADD COLUMN `total_rooms` INT(11) DEFAULT 1 COMMENT 'Tổng số phòng' AFTER `bathrooms`,
ADD COLUMN `rented_rooms` INT(11) DEFAULT 0 COMMENT 'Số phòng đã cho thuê' AFTER `total_rooms`;

-- Cập nhật dữ liệu hiện có: mặc định total_rooms = 1, rented_rooms = 0 cho các property đang available
UPDATE `properties` SET `total_rooms` = 1, `rented_rooms` = 0 WHERE `total_rooms` IS NULL;

-- Cập nhật dữ liệu: nếu status = 'rented' thì rented_rooms = total_rooms
UPDATE `properties` SET `rented_rooms` = `total_rooms` WHERE `status` = 'rented' AND `rented_rooms` < `total_rooms`;

