-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 19, 2025 lúc 09:31 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `quanlybatdongsan1`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `appointments`
--

INSERT INTO `appointments` (`id`, `tenant_id`, `property_id`, `landlord_id`, `appointment_date`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, 6, 7, '2025-11-19 13:53:00', 'dasd', 'confirmed', '2025-11-19 06:52:38', '2025-11-19 06:54:13'),
(2, 5, 6, 7, '2025-11-19 09:00:00', 'sdasd', 'pending', '2025-11-19 06:53:06', '2025-11-19 06:53:06'),
(3, 5, 7, 8, '2025-11-20 11:00:00', 'dasdsadsa', 'confirmed', '2025-11-19 07:03:50', '2025-11-19 07:04:05'),
(4, 5, 8, 9, '2025-11-19 13:00:00', 'lẹ lên', 'pending', '2025-11-19 08:27:58', '2025-11-19 08:27:58');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `response` text DEFAULT NULL,
  `status` enum('pending','replied') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `admin_id`, `message`, `response`, `status`, `created_at`) VALUES
(1, 1, 1, 'gh', 'aaa', 'replied', '2025-11-19 06:36:13'),
(2, 5, NULL, 'dsa', NULL, 'pending', '2025-11-19 06:44:22'),
(3, 5, 1, 'aaaaaa', 'dsadsa', 'replied', '2025-11-19 07:00:12');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `landlord_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `district` varchar(100) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `area` decimal(8,2) NOT NULL,
  `bedrooms` int(11) DEFAULT 0,
  `bathrooms` int(11) DEFAULT 0,
  `property_type` enum('apartment','house','room','studio') DEFAULT 'apartment',
  `status` enum('available','rented','pending') DEFAULT 'available',
  `images` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `properties`
--

INSERT INTO `properties` (`id`, `landlord_id`, `title`, `description`, `address`, `district`, `price`, `area`, `bedrooms`, `bathrooms`, `property_type`, `status`, `images`, `created_at`, `updated_at`) VALUES
(6, 7, 'dsadsa', 'dsad', 'dsadsa', 'Bình Thạnh', 5000000.00, 1000.00, 2, 2, 'house', 'available', '[\"https:\\/\\/noithatnhatminh.vn\\/wp-content\\/uploads\\/2022\\/03\\/z2705365994589_4c31781f22a5d8dacf47f4d91f572566.jpg\"]', '2025-11-19 05:41:27', '2025-11-19 06:20:07'),
(7, 8, 'hghghghg', 'dsadsadsa', 'dsadsad', 'Thủ Đức', 1000000000.00, 100.00, 3, 3, 'house', 'available', '[\"https:\\/\\/encrypted-tbn0.gstatic.com\\/images?q=tbn:ANd9GcQkQKv-OD4gRTARNaGz8tAp0IwzR-VhzvgKcg&s\"]', '2025-11-19 07:03:06', '2025-11-19 07:03:06'),
(8, 9, 'dasdas', 'dasd', '221 ô 1 khu phố hải bình', 'Gò Vấp', 10000000.00, 70.00, 2, 3, 'apartment', 'available', '[\"https:\\/\\/media.vneconomy.vn\\/images\\/upload\\/2022\\/11\\/16\\/56c99f14-2861-4dc2-ae06-3c04c8b22a63.jpg\"]', '2025-11-19 08:18:29', '2025-11-19 08:27:31');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `search_statistics`
--

CREATE TABLE `search_statistics` (
  `id` int(11) NOT NULL,
  `district` varchar(100) NOT NULL,
  `search_count` int(11) DEFAULT 1,
  `last_searched` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `search_statistics`
--

INSERT INTO `search_statistics` (`id`, `district`, `search_count`, `last_searched`) VALUES
(1, 'Thủ Đức', 154, '2025-11-19 06:59:13'),
(2, 'Bình Thạnh', 128, '2025-11-19 06:59:15'),
(3, 'Gò Vấp', 102, '2025-11-19 06:33:08'),
(4, 'Quận 1', 80, '2025-11-19 03:27:52'),
(5, 'Quận 7', 60, '2025-11-19 03:27:52');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('tenant','landlord','admin') DEFAULT 'tenant',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `full_name`, `phone`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Admin', NULL, 'admin@quanlybatdongsan1.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-11-19 03:27:52', '2025-11-19 03:27:52'),
(5, 'Cương 5169_Nguyễn', NULL, 'cuonghotran17022004@gmail.com', '$2y$10$kQ4czXHv69eYCa6DJ2z/O.Fg9KhjWrQleH7huUKQP/f0F8wz7KOBK', 'tenant', '2025-11-19 03:52:08', '2025-11-19 03:52:08'),
(6, 'Nguyễn Cương', '0356012250', 'cuonghotran1233@gmail.com', '$2y$10$4fQ4PTkFFeo/uxmjeQZa4O4ImvNSKD6xJ5yFAgCl4tGKHXD.yQTwS', 'tenant', '2025-11-19 03:52:56', '2025-11-19 03:52:56'),
(7, 'NGUYỄN HOA', '0356012255', 'dsadas@gmail.com', '$2y$10$YeiL0.OjzilX2k1DRAwI2OcfmXjTR0XMwPRib5V20MB8mT1VpI89q', 'landlord', '2025-11-19 05:39:50', '2025-11-19 05:39:50'),
(8, 'Nguyễn Ngọc', '0999999999', 'ngoc123@gmail.com', '$2y$10$5jh528HsZrNayS0u11YyE.MY/BN7uzk5Ty.bKD7.jGjDzbs53pGIO', 'landlord', '2025-11-19 07:01:52', '2025-11-19 07:01:52'),
(9, 'Người cho thuê', '0988888888', 'khanhtrinh9999999@gmail.com', '$2y$10$MQ6PZ7Z95haHHXfqmkloTeqIbrVcxXO2qA6NlZpXsNBmZ50Bd1GNe', 'landlord', '2025-11-19 07:58:58', '2025-11-19 07:58:58');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_id` (`tenant_id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `landlord_id` (`landlord_id`);

--
-- Chỉ mục cho bảng `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Chỉ mục cho bảng `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `landlord_id` (`landlord_id`);

--
-- Chỉ mục cho bảng `search_statistics`
--
ALTER TABLE `search_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `district` (`district`),
  ADD KEY `idx_district` (`district`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `search_statistics`
--
ALTER TABLE `search_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`landlord_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`landlord_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
