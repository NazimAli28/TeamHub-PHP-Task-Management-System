-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 19, 2026 at 09:20 AM
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
-- Database: `teamhub`
--

-- --------------------------------------------------------

--
-- Table structure for table `auto_points_plan`
--

CREATE TABLE `auto_points_plan` (
  `id` int(11) NOT NULL,
  `work` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `points` decimal(4,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `auto_points_plan`
--

INSERT INTO `auto_points_plan` (`id`, `work`, `type`, `points`) VALUES
(1, 'Logo Static', 'New Easy', 1.00),
(2, 'Logo Static', 'New Hard', 2.00),
(3, 'Logo Static', 'Feedback Easy', 0.50),
(4, 'Logo Static', 'Feedback Hard', 1.50),
(5, 'Logo Animated', 'New Easy', 2.00),
(6, 'Logo Animated', 'New Hard', 3.00),
(7, 'Logo Animated', 'Feedback Easy', 1.00),
(8, 'Logo Animated', 'Feedback Hard', 1.50),
(9, 'Social Post Static', 'New Easy', 1.00),
(10, 'Social Post Static', 'New Hard', 2.00),
(11, 'Social Post Static', 'Feedback Easy', 0.50),
(12, 'Social Post Static', 'Feedback Hard', 1.50),
(13, 'Social Post Animated', 'New Easy', 2.00),
(14, 'Social Post Animated', 'New Hard', 3.00),
(15, 'Social Post Animated', 'Feedback Easy', 1.00),
(16, 'Social Post Animated', 'Feedback Hard', 1.50),
(17, 'Web Banner Set', 'New Easy', 1.00),
(18, 'Web Banner Set', 'New Hard', 2.00),
(19, 'Web Banner Set', 'Feedback Easy', 0.50),
(20, 'Web Banner Set', 'Feedback Hard', 1.00),
(33, 'Layout Design', 'New Easy', 1.00),
(34, 'Layout Design', 'New Hard', 1.00),
(35, 'Layout Design', 'Feedback Easy', 0.50),
(36, 'Layout Design', 'Feedback Hard', 1.00),
(37, 'Screens Static', 'New Easy', 1.00),
(38, 'Screens Static', 'New Hard', 2.00),
(39, 'Screens Static', 'Feedback Easy', 3.00),
(40, 'Screens Static', 'Feedback Hard', 2.00),
(41, 'Screens Animated', 'New Easy', 3.00),
(42, 'Screens Animated', 'New Hard', 4.00),
(43, 'Screens Animated', 'Feedback Easy', 1.50),
(44, 'Screens Animated', 'Feedback Hard', 3.00),
(45, 'Wallpaper Static', 'New Easy', 1.00),
(46, 'Wallpaper Static', 'New Hard', 2.00),
(47, 'Wallpaper Static', 'Feedback Easy', 1.00),
(48, 'Wallpaper Static', 'Feedback Hard', 1.50),
(49, 'Wallpaper Animated', 'New Easy', 2.00),
(50, 'Wallpaper Animated', 'New Hard', 3.00),
(51, 'Wallpaper Animated', 'Feedback Easy', 1.50),
(52, 'Wallpaper Animated', 'Feedback Hard', 3.00),
(53, 'Transition Animated', 'New Easy', 1.00),
(54, 'Transition Animated', 'New Hard', 2.00),
(55, 'Transition Animated', 'Feedback Easy', 1.00),
(56, 'Transition Animated', 'Feedback Hard', 2.00),
(57, 'Intro Animated ', 'New Easy', 2.00),
(58, 'Intro Animated ', 'New Hard', 4.00),
(59, 'Intro Animated ', 'Feedback Easy', 1.50),
(60, 'Intro Animated ', 'Feedback Hard', 2.00),
(61, 'Outro Animated ', 'New Easy', 2.00),
(62, 'Outro Animated ', 'New Hard', 4.00),
(63, 'Outro Animated ', 'Feedback Easy', 1.50),
(64, 'Outro Animated ', 'Feedback Hard', 2.00),
(65, 'Character Sheet', 'New Easy', 1.00),
(66, 'Character Sheet', 'New Hard', 3.00),
(67, 'Character Sheet', 'Feedback Easy', 0.50),
(68, 'Character Sheet', 'Feedback Hard', 2.00),
(69, 'Avatar Static', 'New Easy', 0.50),
(70, 'Avatar Static', 'New Hard', 1.00),
(71, 'Avatar Static', 'Feedback Easy', 0.50),
(72, 'Avatar Static', 'Feedback Hard', 1.00),
(73, 'Avatar Animated', 'New Easy', 1.00),
(74, 'Avatar Animated', 'New Hard', 3.00),
(75, 'Avatar Animated', 'Feedback Easy', 0.50),
(76, 'Avatar Animated', 'Feedback Hard', 1.50),
(77, 'Reaction Graphic Static', 'New Easy', 0.50),
(78, 'Reaction Graphic Static', 'New Hard', 1.00),
(79, 'Reaction Graphic Static', 'Feedback Easy', 0.50),
(80, 'Reaction Graphic Static', 'Feedback Hard', 1.00),
(81, 'Badge Icon', 'New Easy', 0.50),
(82, 'Badge Icon', 'New Hard', 0.50),
(83, 'Badge Icon', 'Feedback Easy', 0.50),
(84, 'Badge Icon', 'Feedback Hard', 0.50),
(85, '2D Character Base', 'New Easy', 1.00),
(86, '2D Character Base', 'New Hard', 3.00),
(87, '2D Character Base', 'Feedback Easy', 0.50),
(88, '2D Character Base', 'Feedback Hard', 2.00),
(89, '2D Character Cutout', 'New Easy', 2.00),
(90, '2D Character Cutout', 'New Hard', 3.00),
(91, '2D Character Cutout', 'Feedback Easy', 1.00),
(92, '2D Character Cutout', 'Feedback Hard', 2.00),
(93, '2D Character Rigging', 'New Easy', 4.00),
(94, '2D Character Rigging', 'New Hard', 8.00),
(95, '2D Character Rigging', 'Feedback Easy', 2.00),
(96, '2D Character Rigging', 'Feedback Hard', 4.00),
(97, 'Video Edit', 'New Easy', 4.00),
(98, 'Video Edit', 'New Hard', 8.00),
(99, 'Video Edit', 'Feedback Easy', 2.00),
(100, 'Video Edit', 'Feedback Hard', 8.00),
(101, 'Brand Pack Static', 'New Easy', 2.00),
(102, 'Brand Pack Static', 'New Hard', 4.00),
(103, 'Brand Pack Static', 'Feedback Easy', 1.00),
(104, 'Brand Pack Static', 'Feedback Hard', 3.00),
(105, 'Brand Pack Animated', 'New Easy', 3.00),
(106, 'Brand Pack Animated', 'New Hard', 5.00),
(107, 'Brand Pack Animated', 'Feedback Easy', 2.00),
(108, 'Brand Pack Animated', 'Feedback Hard', 4.00),
(109, 'Poster Design', 'New Easy', 1.50),
(110, 'Poster Design', 'New Hard', 2.00),
(111, 'Poster Design', 'Feedback Easy', 1.00),
(112, 'Poster Design', 'Feedback Hard', 2.00),
(113, 'Sketch', 'New Easy', 1.50),
(114, 'Sketch', 'New Hard', 2.00),
(115, 'Sketch', 'Feedback Easy', 0.50),
(116, 'Sketch', 'Feedback Hard', 1.50),
(117, 'Sticker', 'New Easy', 1.00),
(118, 'Sticker', 'New Hard', 1.00),
(119, 'Sticker', 'Feedback Easy', 0.50),
(120, 'Sticker', 'Feedback Hard', 1.00),
(121, 'Merch', 'New Easy', 3.00),
(122, 'Merch', 'New Hard', 4.00),
(123, 'Merch', 'Feedback Easy', 1.00),
(124, 'Merch', 'Feedback Hard', 2.00),
(125, 'Mockups', 'New Easy', 1.00),
(126, 'Mockups', 'New Hard', 2.00),
(127, 'Mockups', 'Feedback Easy', 1.00),
(128, 'Mockups', 'Feedback Hard', 1.50);

-- --------------------------------------------------------

--
-- Table structure for table `bonus_logs`
--

CREATE TABLE `bonus_logs` (
  `id` int(11) NOT NULL,
  `designer_name` varchar(100) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `bonus_points` float NOT NULL,
  `reason` text DEFAULT NULL,
  `added_by` varchar(100) NOT NULL,
  `added_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `designer_points`
--

CREATE TABLE `designer_points` (
  `id` int(11) NOT NULL,
  `designer_name` varchar(100) DEFAULT NULL,
  `order_no` tinytext DEFAULT NULL,
  `work` text DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `allotted_points` float DEFAULT NULL,
  `points_gained` float DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `work_type` varchar(50) DEFAULT NULL,
  `allotted_time` int(11) DEFAULT NULL,
  `assigned_time` datetime DEFAULT current_timestamp(),
  `completion_time` datetime DEFAULT NULL,
  `completed_time` int(11) DEFAULT NULL,
  `is_paused` tinyint(1) DEFAULT 0,
  `pause_start_time` datetime DEFAULT NULL,
  `paused_duration` int(11) DEFAULT 0,
  `bonus_points` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pause_logs`
--

CREATE TABLE `pause_logs` (
  `id` int(11) NOT NULL,
  `designer_point_id` int(11) NOT NULL,
  `pause_time` datetime NOT NULL,
  `resume_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee') NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `name`, `password`, `role`, `profile_picture`, `remember_token`) VALUES
(1, 'admin', 'Admin', '$2y$10$SHIEU2tVaUAl0s.re4Exc.L6mOc7EAxPVMmOFXkxtOFjZtcmcheeC', 'admin', NULL, NULL),
(2, 'employee', 'Employee 1', '$2y$10$QP/vqZWNQhqmnN2RKhqOL.X0gepWO2g2jWqVWcg/CjoUyVUKryn4a', 'employee', NULL, NULL),
(3, 'employee2', 'Employee 2', '$2y$10$QP/vqZWNQhqmnN2RKhqOL.X0gepWO2g2jWqVWcg/CjoUyVUKryn4a', 'employee', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auto_points_plan`
--
ALTER TABLE `auto_points_plan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bonus_logs`
--
ALTER TABLE `bonus_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`);

--
-- Indexes for table `designer_points`
--
ALTER TABLE `designer_points`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pause_logs`
--
ALTER TABLE `pause_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `designer_point_id` (`designer_point_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auto_points_plan`
--
ALTER TABLE `auto_points_plan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `bonus_logs`
--
ALTER TABLE `bonus_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `designer_points`
--
ALTER TABLE `designer_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1791;

--
-- AUTO_INCREMENT for table `pause_logs`
--
ALTER TABLE `pause_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bonus_logs`
--
ALTER TABLE `bonus_logs`
  ADD CONSTRAINT `bonus_logs_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `designer_points` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
