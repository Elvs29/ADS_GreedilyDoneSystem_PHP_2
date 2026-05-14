-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2026 at 05:38 AM
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
-- Database: `auth_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `task_title` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `task_title`, `created_at`) VALUES
(1, 1, 'Completed', 'Grocery Day', '2026-05-13 17:52:41'),
(2, 1, 'Added', 'Electricity Bill Date', '2026-05-13 17:53:30'),
(3, 1, 'Completed', 'Exam Review', '2026-05-13 17:54:14'),
(4, 1, 'Deleted', 'Laundry', '2026-05-13 17:54:48'),
(5, 2, 'Added', 'Grocery Day', '2026-05-14 00:54:46'),
(6, 2, 'Completed', 'Grocery Day', '2026-05-14 00:54:51'),
(7, 1, 'Completed', 'Shopping', '2026-05-14 01:39:52'),
(8, 1, 'Reopened', 'Shopping', '2026-05-14 01:39:54'),
(9, 1, 'Completed', 'Shopping', '2026-05-14 03:24:14');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `category` varchar(50) DEFAULT 'Personal',
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_pinned` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `user_id`, `title`, `description`, `due_date`, `priority`, `category`, `status`, `created_at`, `is_pinned`) VALUES
(1, 1, 'Finance Report', NULL, '2026-05-20', 'Medium', 'Office', 'Pending', '2026-05-13 16:45:31', 0),
(3, 1, 'Exam Review', NULL, '2026-05-16', 'Medium', 'Academic', 'Completed', '2026-05-13 17:01:32', 0),
(4, 1, 'Grocery Day', NULL, '2026-05-15', 'Medium', 'Personal', 'Completed', '2026-05-13 17:09:23', 0),
(5, 1, 'Church Service', NULL, '2026-05-20', 'High', 'Personal', 'Pending', '2026-05-13 17:14:30', 1),
(6, 1, 'Baguio Trip', NULL, '2026-06-20', 'Low', 'Personal', 'Pending', '2026-05-13 17:25:43', 0),
(7, 1, 'Shopping', NULL, '2026-05-19', 'Low', 'Personal', 'Completed', '2026-05-13 17:32:42', 1),
(8, 1, 'Electricity Bill Date', NULL, '2026-05-30', 'High', 'Personal', 'Pending', '2026-05-13 17:53:30', 0),
(9, 2, 'Grocery Day', NULL, '2026-05-15', 'Low', 'Personal', 'Completed', '2026-05-14 00:54:46', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'Elvie Gequilan', 'elviegequilan508', 'elviegequilan62@gmail.com', '$2y$10$NeUkbPjwb3krzrlbUdj/A.lIKdK8MLG/8frdQe19AFSgGucuh5ise', '2026-05-12 01:29:07'),
(2, 'Elvie Gequilan', 'Elvs29', 'elvs@gmail.com', '$2y$10$SxmuNes7MSQCcuU/GAyVR.bk1Cf7tDTvAZwzydUp3oLwkqa0OCFHq', '2026-05-12 01:52:47'),
(3, 'Joy', 'Joy23', 'joy@gmail.com', '$2y$10$Hssa2ZXp5g7qQFIsfPeN8O/VW/e5vf.7RVWl.RLmEEsIasbGavPaa', '2026-05-12 03:23:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
