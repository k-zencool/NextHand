-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Dec 13, 2025 at 11:56 AM
-- Server version: 8.0.44
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nexthand`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'เจ้าของสินค้า',
  `title` varchar(255) NOT NULL COMMENT 'ชื่อสินค้า',
  `price` decimal(10,2) NOT NULL COMMENT 'ราคาขาย',
  `description` text COMMENT 'รายละเอียดสินค้า',
  `image` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์รูปภาพ',
  `status` enum('active','sold','banned') DEFAULT 'active' COMMENT 'สถานะสินค้า',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `first_name` varchar(100) DEFAULT NULL COMMENT 'ชื่อจริง',
  `last_name` varchar(100) DEFAULT NULL COMMENT 'นามสกุล',
  `profile_image` varchar(255) DEFAULT 'default.png' COMMENT 'รูปโปรไฟล์',
  `bio` text COMMENT 'แนะนำร้านค้า',
  `phone` varchar(20) DEFAULT NULL,
  `line_id` varchar(50) DEFAULT NULL,
  `facebook_link` varchar(255) DEFAULT NULL,
  `address` text COMMENT 'ที่อยู่จัดส่ง',
  `province` varchar(100) DEFAULT NULL COMMENT 'จังหวัด',
  `zipcode` varchar(10) DEFAULT NULL,
  `item_quota` int DEFAULT '10' COMMENT 'จำนวนของที่ลงขายได้',
  `is_verified` tinyint(1) DEFAULT '0' COMMENT '0=ยังไม่ยืนยัน, 1=ยืนยันแล้ว',
  `status` enum('active','banned','suspended') DEFAULT 'active' COMMENT 'สถานะบัญชี',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `first_name`, `last_name`, `profile_image`, `bio`, `phone`, `line_id`, `facebook_link`, `address`, `province`, `zipcode`, `item_quota`, `is_verified`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'admin@nexthand.com', 'admin', 'Super', 'Admin', 'default.png', 'ผู้ดูแลระบบสูงสุด แจ้งปัญหาการใช้งานได้ตลอด 24 ชม.', '0811111111', 'admin_next', 'fb.com/admin', 'สำนักงานใหญ่ NextHand', 'Bangkok', '10900', 9999, 1, 'active', NULL, '2025-12-12 04:23:42', '2025-12-12 04:23:42'),
(2, 'zencool', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'zen@gmail.com', 'user', 'Zen', 'Cool', 'default.png', 'ขายของ IT มือสอง สภาพนางฟ้า นัดรับได้ในตัวเมืองเชียงใหม่', '0998887777', 'zen_line', 'fb.com/zencool', '123/45 ถ.นิมมานเหมินท์ ต.สุเทพ', 'Chiang Mai', '50200', 50, 1, 'active', NULL, '2025-12-12 04:23:42', '2025-12-12 04:23:42'),
(3, 'somchai', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'somchai@hotmail.com', 'user', 'Somchai', 'Rakdee', 'default.png', 'เน้นซื้อไม่เน้นขาย หาของสะสมหายากครับ', '0876543210', 'somchai99', NULL, '44/2 หมู่ 5 ต.ราไวย์', 'Phuket', '83130', 10, 0, 'active', NULL, '2025-12-12 04:23:42', '2025-12-12 04:23:42'),
(4, 'test', '$2y$10$g0LIennjA8Hi7hSN5t9Jp.UI0cGPOuO.sohbCtn/3DwU3W7MFJuj2', 'zencool.xxx@gmail.com', 'user', 'Test', 'User', 'user_4_1765626155.png', 'ฟหกหฟปแหำพดแกปอดเปแอดเปแอดเ', '0612955236', '2745552356', '', 'หหกฟหก', 'หฟกหฟก', '53110', 10, 0, 'active', '2025-12-13 11:54:40', '2025-12-12 04:59:29', '2025-12-13 11:54:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
