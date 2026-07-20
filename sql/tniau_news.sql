-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 20, 2026 at 02:24 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tniau_news`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `news_id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` enum('comment','correction') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'comment',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','pending_b','revision_b','pending_c','revision_c','published') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `published_at` timestamp NULL DEFAULT NULL,
  `sentiment` enum('Positif','Negatif','Netral') COLLATE utf8mb4_unicode_ci DEFAULT 'Netral',
  `media` enum('Wilayah','Media Online','Media Sosial') COLLATE utf8mb4_unicode_ci DEFAULT 'Media Online',
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Medium',
  `classification` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '9. Tni au',
  `wilayah` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tempat` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'PEN ATS'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title`, `slug`, `content`, `image_path`, `status`, `created_by`, `created_at`, `updated_at`, `published_at`, `sentiment`, `media`, `priority`, `classification`, `wilayah`, `tempat`, `author_label`) VALUES
(1, 'safafsaf', 'safafsaf-4d262', 'asdafa', 'news_6a5d85224cfdb_1784513826.png', 'published', 1, '2026-07-20 02:17:06', '2026-07-20 02:20:16', '2026-07-20 02:20:16', 'Positif', 'Wilayah', 'Medium', '9. Tni au', 'Lanud Atang Sendjaja', 'Lanud Atang Sendjaja', 'PEN ATS');

-- --------------------------------------------------------

--
-- Table structure for table `news_history`
--

CREATE TABLE `news_history` (
  `id` int NOT NULL,
  `news_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status_from` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_to` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `news_history`
--

INSERT INTO `news_history` (`id`, `news_id`, `user_id`, `status_from`, `status_to`, `note`, `created_at`) VALUES
(1, 1, 1, 'pending_b', 'pending_b', 'Berita baru dibuat', '2026-07-20 02:17:06'),
(2, 1, 2, 'pending_b', 'pending_c', 'Disetujui Editor (B), diteruskan ke Petinggi (C)', '2026-07-20 02:19:32'),
(3, 1, 3, 'pending_c', 'published', 'Disetujui Petinggi (C), berita dipublikasikan', '2026-07-20 02:20:16');

-- --------------------------------------------------------

--
-- Table structure for table `news_images`
--

CREATE TABLE `news_images` (
  `id` int NOT NULL,
  `news_id` int NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `news_id` int NOT NULL,
  `message` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `news_id`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 1, 'Berita baru: \"safafsaf\" menunggu review Anda.', 0, '2026-07-20 02:17:06'),
(2, 3, 1, 'Berita \"safafsaf\" telah lolos Editor dan menunggu persetujuan Anda.', 0, '2026-07-20 02:19:32'),
(3, 1, 1, 'Selamat! Berita \"safafsaf\" telah disetujui dan dipublikasikan.', 0, '2026-07-20 02:20:16');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('A','B','C') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `created_at`) VALUES
(1, 'reporter1', '$2y$10$zgObRS.NaEBxjr1cIL407eDsVEyBk9F5tr1ny54t8v9xe1k4ballS', 'Andi (Reporter)', 'A', '2026-07-20 00:25:36'),
(2, 'editor1', '$2y$10$zgObRS.NaEBxjr1cIL407eDsVEyBk9F5tr1ny54t8v9xe1k4ballS', 'Budi (Editor)', 'B', '2026-07-20 00:25:36'),
(3, 'approver1', '$2y$10$zgObRS.NaEBxjr1cIL407eDsVEyBk9F5tr1ny54t8v9xe1k4ballS', 'Chandra (Petinggi)', 'C', '2026-07-20 00:25:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_comment_news` (`news_id`),
  ADD KEY `fk_comment_user` (`user_id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_news_creator` (`created_by`);
ALTER TABLE `news` ADD FULLTEXT KEY `ft_news_search` (`title`,`content`);

--
-- Indexes for table `news_history`
--
ALTER TABLE `news_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_hist_news` (`news_id`),
  ADD KEY `fk_hist_user` (`user_id`);

--
-- Indexes for table `news_images`
--
ALTER TABLE `news_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `news_id` (`news_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notif_user` (`user_id`),
  ADD KEY `fk_notif_news` (`news_id`);

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
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `news_history`
--
ALTER TABLE `news_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `news_images`
--
ALTER TABLE `news_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comment_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `fk_news_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `news_history`
--
ALTER TABLE `news_history`
  ADD CONSTRAINT `fk_hist_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `news_images`
--
ALTER TABLE `news_images`
  ADD CONSTRAINT `news_images_ibfk_1` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
