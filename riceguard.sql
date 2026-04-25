-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 25, 2026 at 11:12 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `riceguard`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `target` varchar(50) DEFAULT 'all',
  `role` varchar(20) DEFAULT 'global',
  `urgent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `target`, `role`, `urgent`, `created_at`, `created_by`, `is_read`) VALUES
(1, 'typon', 'sdghkj', 'all', 'farmer', 0, '2026-04-23 10:05:00', 93, 1),
(2, 'yulanda', 'sdwsd', 'all', 'farmer', 0, '2026-04-23 10:07:54', 93, 1),
(3, 'qwewe', 'wq', 'all', 'technician', 0, '2026-04-25 05:49:34', 93, 0);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `from_user_id` int(10) UNSIGNED NOT NULL,
  `to_user_id` int(10) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `from_user_id`, `to_user_id`, `message`, `created_at`) VALUES
(1, 190, 0, 'sds', '2026-04-17 22:00:08'),
(2, 192, 190, 'dfsdf', '2026-04-17 22:07:27'),
(3, 192, 190, 'dfsdf', '2026-04-17 22:07:27'),
(4, 190, 0, 'awdsajdkjadsj', '2026-04-17 22:14:21'),
(5, 190, 192, 'sd', '2026-04-17 22:16:30'),
(6, 193, 0, 'hid', '2026-04-18 20:19:26'),
(7, 192, 193, 'n', '2026-04-18 20:21:11'),
(9, 193, 0, 'wqeqeqwe', '2026-04-19 10:46:50'),
(10, 193, 0, 'zsds', '2026-04-19 10:56:58'),
(12, 193, 0, 'ljay', '2026-04-19 11:08:08'),
(13, 192, 0, 'sdsdsas', '2026-04-19 11:28:41'),
(14, 192, 0, 'sdsdsassadasda', '2026-04-19 11:28:48'),
(15, 192, 0, 'asdads', '2026-04-19 11:29:02'),
(16, 192, 0, 'asas', '2026-04-19 11:29:15'),
(17, 192, 0, 'dasd', '2026-04-19 11:29:28'),
(18, 192, 0, 'asdsd', '2026-04-19 11:29:33');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proposals`
--

CREATE TABLE `proposals` (
  `id` int(11) NOT NULL,
  `technician_id` int(11) NOT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `related` varchar(100) DEFAULT NULL,
  `lead` varchar(100) DEFAULT NULL,
  `to_email` varchar(255) DEFAULT NULL,
  `visibility` enum('Public','Private','Internal') DEFAULT 'Private',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `content` text DEFAULT NULL,
  `allow_comments` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT 'Draft',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposals`
--

INSERT INTO `proposals` (`id`, `technician_id`, `farmer_id`, `subject`, `related`, `lead`, `to_email`, `visibility`, `start_date`, `due_date`, `content`, `allow_comments`, `status`, `created_at`) VALUES
(70, 93, NULL, 'coke', 'Farmer', 'admin@riceguard.ai', '', 'Internal', NULL, NULL, 'wqw', 0, 'Draft', '2026-04-24 14:50:41'),
(71, 93, NULL, 'qwqw', 'Technician', 'admin@riceguard.ai', '', 'Public', NULL, NULL, 'wqw', 0, 'Draft', '2026-04-24 14:51:01'),
(72, 192, NULL, 'qwqw', 'Technician', '2@gmail.com', '', 'Internal', NULL, NULL, 'qwqw', 1, 'Draft', '2026-04-24 14:51:44'),
(73, 192, NULL, 'qwqwq', 'Technician', '2@gmail.com', '', 'Public', NULL, NULL, 'qwqw', 1, 'Draft', '2026-04-24 14:52:24');

-- --------------------------------------------------------

--
-- Table structure for table `proposal_comments`
--

CREATE TABLE `proposal_comments` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposal_comments`
--

INSERT INTO `proposal_comments` (`id`, `proposal_id`, `user_id`, `comment`, `created_at`) VALUES
(3, 31, 93, 'adsdffd', '2026-04-17 19:40:11'),
(5, 43, 93, 'gasva', '2026-04-22 21:33:45'),
(6, 73, 93, 'HI', '2026-04-24 14:52:40'),
(7, 73, 192, 'hello', '2026-04-24 14:53:03');

-- --------------------------------------------------------

--
-- Table structure for table `proposal_notes`
--

CREATE TABLE `proposal_notes` (
  `id` int(11) NOT NULL,
  `proposal_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proposal_notes`
--

INSERT INTO `proposal_notes` (`id`, `proposal_id`, `user_id`, `note`, `created_at`) VALUES
(3, 31, 192, 'wsa', '2026-04-17 19:47:03'),
(4, 31, 192, 'sdads', '2026-04-17 19:47:10'),
(5, 31, 93, 'sdasd', '2026-04-17 19:50:52'),
(6, 31, 93, 'asdasd', '2026-04-17 19:54:44');

-- --------------------------------------------------------

--
-- Table structure for table `rice_plans`
--

CREATE TABLE `rice_plans` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `variety` varchar(100) NOT NULL,
  `size` decimal(10,2) NOT NULL,
  `planting_date` date NOT NULL,
  `growth_stage` varchar(50) DEFAULT NULL,
  `harvest_date` date NOT NULL,
  `total_yield` decimal(10,2) NOT NULL,
  `risk` enum('Low','Medium','High') NOT NULL,
  `yield_per_hectare` decimal(10,2) DEFAULT 4000.00,
  `health` varchar(20) DEFAULT NULL,
  `pest` varchar(20) DEFAULT NULL,
  `water` varchar(20) DEFAULT NULL,
  `weather` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rice_plans`
--

INSERT INTO `rice_plans` (`id`, `user_id`, `variety`, `size`, `planting_date`, `growth_stage`, `harvest_date`, `total_yield`, `risk`, `yield_per_hectare`, `health`, `pest`, `water`, `weather`, `notes`, `email`, `created_at`) VALUES
(37, 193, 'sadasd', 12.00, '2026-04-08', 'Vegetative', '2026-08-06', 14544.00, 'Low', 1212.00, '0', 'Low', 'Enough', 'Normal', 'cocke', NULL, '2026-04-20 12:43:29'),
(38, 193, 'sadasd', 12.00, '2026-04-08', 'Vegetative', '2026-08-06', 14544.00, 'Low', 1212.00, '0', 'Low', 'Enough', 'Normal', '1212', NULL, '2026-04-20 12:43:35'),
(39, 200, 'dcsdfd', 232.00, '2026-04-29', 'Seedling', '2026-08-27', 489984.00, 'Low', 2112.00, '0', 'None', 'Enough', 'Normal', 'dasd', NULL, '2026-04-25 05:40:21'),
(40, 200, 'dcsdfd', 232.00, '2026-04-29', 'Seedling', '2026-08-27', 489984.00, 'Low', 2112.00, '0', 'None', 'Enough', 'Normal', 'dasd', NULL, '2026-04-25 05:40:28');

-- --------------------------------------------------------

--
-- Table structure for table `treatment_records`
--

CREATE TABLE `treatment_records` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `disease` varchar(100) NOT NULL,
  `type` enum('disease','pest') NOT NULL,
  `treatments` text DEFAULT NULL,
  `causes` text DEFAULT NULL,
  `nutrient_deficiency` text DEFAULT NULL,
  `grain_damage` text DEFAULT NULL,
  `prevention` text DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `lang` enum('en','tl') NOT NULL DEFAULT 'tl',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treatment_records`
--

INSERT INTO `treatment_records` (`id`, `user_id`, `disease`, `type`, `treatments`, `causes`, `nutrient_deficiency`, `grain_damage`, `prevention`, `updated_by`, `lang`, `created_at`, `updated_at`) VALUES
(75, NULL, 'tungro_virus', 'disease', 'asas', 'asx as', 'saxs', 'sdad', 'saasd', 93, 'tl', '2026-04-19 14:27:28', '2026-04-25 05:56:16'),
(80, NULL, 'bacterial_leaf_blight', 'disease', 'ssdsd', 'b', 'b', 'bbb', 'b', 93, 'tl', '2026-04-25 05:56:41', '2026-04-25 05:56:41');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('farmer','admin','technician') NOT NULL DEFAULT 'farmer',
  `status` enum('pending','approved','declined') DEFAULT 'pending',
  `lang` enum('en','tl') NOT NULL DEFAULT 'tl',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `detected_classes` text DEFAULT '[]',
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `farm_size` decimal(5,2) DEFAULT NULL,
  `preferred_variety` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `role`, `status`, `lang`, `created_at`, `updated_at`, `detected_classes`, `phone`, `address`, `farm_size`, `preferred_variety`, `bio`, `photo`) VALUES
(93, 'System Administrator', 'admin@riceguard.ai', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved', 'tl', '2026-04-10 05:27:08', '2026-04-23 13:01:08', '[]', '090-29329432', 'adasss', NULL, NULL, NULL, 'uploads/profile/admin_93_1776949268.jpg'),
(192, 'rrr', '2@gmail.com', '$2y$10$ll1IubTNsfo/M/8341RXYuNM3Z7O0YNkM80EPDhJ1DZ8A6oy7LV/y', 'technician', 'approved', 'tl', '2026-04-16 13:04:06', '2026-04-16 13:04:06', '[]', NULL, NULL, NULL, NULL, NULL, NULL),
(193, 'ljay ayco', 'ljayayco@gmail.com', '$2y$10$fOL7sfiRxjbY3ojhzdISAecsZxZja8QU20pt98BpBh17CKHWg30/2', 'farmer', 'approved', 'tl', '2026-04-18 02:00:17', '2026-04-22 00:51:26', '[]', 'aedwe', 'xsdsds', 21.00, 'SS', 'sa', 'uploads/profile/193_1776819086.jpg'),
(194, 'shh', '34@gmail.com', '$2y$10$apCy5woXtPBvKwS3DyxpBOQU4Pm.wV7C8MxwO06VQ/iftdgSK7gJe', 'farmer', 'approved', 'tl', '2026-04-21 07:35:36', '2026-04-21 07:36:11', '[]', NULL, NULL, NULL, NULL, NULL, NULL),
(195, 'Jf Conco', 'jfconco6@gmail.com', '$2y$10$mPBLagUl6ySp/.8ll7uGUuzY7BngTmP7aL.8gmE6Tgvajuj5C2EAm', 'farmer', 'pending', 'tl', '2026-04-25 00:56:27', '2026-04-25 00:56:27', '[]', NULL, NULL, NULL, NULL, NULL, NULL),
(196, 'wewq', 'w@gmail.co', '$2y$10$njFCT0fBq0Ro8DC6m99RAO2Q.U/WkCipDljkk8Yp6CotsGzhHDprm', 'farmer', 'pending', 'tl', '2026-04-25 01:38:25', '2026-04-25 01:38:25', '[]', NULL, NULL, NULL, NULL, NULL, NULL),
(197, 'asas', 'co@gmail.com', '$2y$10$jDFXhIexiH5ym8gT904U1.TQPwkEbUydKIsHM5ddQ6JU53TMOdkeO', 'farmer', 'approved', 'tl', '2026-04-25 01:43:58', '2026-04-25 03:21:23', '[]', NULL, NULL, NULL, NULL, NULL, NULL),
(198, 'fff', 'o@gmail.com', '$2y$10$WdOBr8qtZCuORNIoB6bfBuFfuu00iGkWjlHRnSOTgRvL/RSLWp49K', 'farmer', 'pending', 'tl', '2026-04-25 03:13:27', '2026-04-25 03:13:27', '[]', NULL, NULL, NULL, NULL, NULL, NULL),
(199, 'sasasa', 'ljao@gmail.com', '$2y$10$Va15FGloQMfLlEhHJ/ZwCuUVCgkBR9QH707ziLuBQZUT13Mtg7jH.', 'farmer', 'pending', 'tl', '2026-04-25 03:20:19', '2026-04-25 03:20:19', '[]', NULL, NULL, NULL, NULL, NULL, NULL),
(200, 'coke123', 'ljayay@gmail.com', '$2y$10$4TaXFIBsa7I505CBBCi/reeGhXXCwtOp1qmvqaHU/8.9n2DLvzgv.', 'farmer', 'approved', 'tl', '2026-04-25 03:22:11', '2026-04-25 03:49:53', '[]', '', '', 0.00, '', '', 'uploads/profile/200_1777088993.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `user_detections`
--

CREATE TABLE `user_detections` (
  `id` int(11) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `class_key` varchar(100) NOT NULL,
  `confidence` int(11) DEFAULT 65,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_detections`
--

INSERT INTO `user_detections` (`id`, `user_id`, `class_key`, `confidence`, `image_path`, `created_at`) VALUES
(16, 193, 'tungro_virus', 91, 'uploads/detections/193_tungro_virus_1776688420.jpg', '2026-04-20 12:33:40'),
(17, 193, 'rice_stem_borer', 72, 'uploads/detections/193_rice_stem_borer_1776996036.jpg', '2026-04-24 02:00:36'),
(18, 193, 'rice_stem_borer', 72, 'uploads/detections/193_rice_stem_borer_1776998901.jpg', '2026-04-24 02:48:21'),
(19, 200, 'tungro_virus', 91, 'uploads/detections/200_tungro_virus_1777095578.jpg', '2026-04-25 05:39:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_from` (`from_user_id`),
  ADD KEY `idx_to` (`to_user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_user` (`user_id`);

--
-- Indexes for table `proposals`
--
ALTER TABLE `proposals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `proposal_comments`
--
ALTER TABLE `proposal_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `proposal_notes`
--
ALTER TABLE `proposal_notes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rice_plans`
--
ALTER TABLE `rice_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rice_plans_user` (`user_id`);

--
-- Indexes for table `treatment_records`
--
ALTER TABLE `treatment_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_disease` (`disease`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_disease` (`disease`),
  ADD KEY `treatment_records_ibfk_user` (`user_id`),
  ADD KEY `fk_treatment_updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_detections`
--
ALTER TABLE `user_detections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_id_2` (`user_id`),
  ADD KEY `user_id_3` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `proposal_comments`
--
ALTER TABLE `proposal_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `proposal_notes`
--
ALTER TABLE `proposal_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `rice_plans`
--
ALTER TABLE `rice_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `treatment_records`
--
ALTER TABLE `treatment_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT for table `user_detections`
--
ALTER TABLE `user_detections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rice_plans`
--
ALTER TABLE `rice_plans`
  ADD CONSTRAINT `fk_rice_plans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `treatment_records`
--
ALTER TABLE `treatment_records`
  ADD CONSTRAINT `fk_treatment_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `treatment_records_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_detections`
--
ALTER TABLE `user_detections`
  ADD CONSTRAINT `fk_user_detections_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
