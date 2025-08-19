-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql203.infinityfree.com
-- Generation Time: Dec 11, 2024 at 01:07 AM
-- Server version: 10.6.19-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_37868453_halarnati`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'murikhaw', 'c076984d4157c626bf990fdd55a2d6f8e334173fc97f430156f1e448d544d110', '2024-12-08 13:37:33');

-- --------------------------------------------------------

--
-- Table structure for table `entries`
--

CREATE TABLE `entries` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lock_key` varchar(255) DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `is_visible` tinyint(1) DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `entries`
--

INSERT INTO `entries` (`id`, `title`, `text`, `file_path`, `created_at`, `lock_key`, `download_count`, `view_count`, `is_visible`) VALUES
(47, 'Okkk', 'Lo', NULL, '2024-12-07 12:24:22', NULL, 0, 0, 1),
(48, 'f', 'f', NULL, '2024-12-07 12:30:29', NULL, 0, 0, 1),
(49, 'f', 'f', NULL, '2024-12-07 12:33:07', NULL, 0, 0, 1),
(50, 'f', 'f', NULL, '2024-12-07 12:33:20', NULL, 0, 0, 1),
(51, 'f', 'f', NULL, '2024-12-07 12:35:26', NULL, 0, 0, 1),
(52, 'f', 'f', NULL, '2024-12-07 12:37:37', NULL, 0, 0, 1),
(53, 'f', 'f', NULL, '2024-12-07 12:39:20', NULL, 0, 0, 1),
(54, 'f', 'f', NULL, '2024-12-07 12:39:34', NULL, 0, 0, 1),
(55, 'w', 'w', NULL, '2024-12-07 12:39:45', NULL, 0, 0, 1),
(56, 'w', 'w', NULL, '2024-12-07 12:42:38', NULL, 0, 0, 1),
(57, 'css', 'f\\css', 'uploads/style.css', '2024-12-07 12:50:53', NULL, 2, 0, 1),
(58, 'okkkk', 'fine', NULL, '2024-12-07 12:55:15', '1234', 0, 0, 1),
(59, 'ok', 'ok', NULL, '2024-12-07 13:04:31', '', 0, 0, 1),
(69, 'Ai chatgpt', 'summaery', 'uploads/Detailed_Notes_ANN_Uncertainty_Final.pdf', '2024-12-08 16:44:56', '', 0, 0, 1),
(61, 'huuuuuuuu', 'haaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', NULL, '2024-12-07 13:05:26', 'aa', 0, 0, 1),
(62, 'f', 'f', 'uploads/avro vai.c', '2024-12-07 13:53:39', '1234', 0, 0, 1),
(63, 'iikikk', 'f', NULL, '2024-12-07 14:28:15', '', 0, 0, 1),
(64, 'dd', 'dd', NULL, '2024-12-07 14:28:53', '1111', 0, 0, 1),
(65, 'MealMap pptx', 'a', 'uploads/mealmap.final.pptx', '2024-12-07 14:29:32', 'nn12', 0, 0, 1),
(66, 'dc', 'cc', 'uploads/download.php', '2024-12-07 14:29:38', '', 0, 0, 1),
(67, 'offenseorbit pptx', 'b', 'uploads/OffenseOrbit.pptx', '2024-12-07 14:30:23', 'nn12', 0, 0, 1),
(68, 'offense orbit report', 'lo', 'uploads/OFFENSE ORBIT report.docx', '2024-12-07 18:54:18', '', 0, 0, 1),
(70, 'mealmap', 'a', 'uploads/mealmap.final.pptx', '2024-12-10 10:30:59', '', 0, 0, 1),
(71, 'last pptx', 'bkjhlijh', 'uploads/mealmap.final last.pptx', '2024-12-10 10:38:01', '', 0, 0, 1),
(72, 'lost and found', 'java code', 'uploads/lost and found.zip', '2024-12-10 18:02:49', '1111', 0, 0, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `entries`
--
ALTER TABLE `entries`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `entries`
--
ALTER TABLE `entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
