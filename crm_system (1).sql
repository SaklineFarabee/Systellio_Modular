-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 21, 2026 at 10:21 AM
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
-- Database: `crm_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `assigned_agent` varchar(255) DEFAULT 'Unassigned',
  `total_contacts` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `company_name`, `assigned_agent`, `total_contacts`, `created_at`) VALUES
(4, 'Peersolution', 'demo', 0, '2026-04-17 15:51:49'),
(5, 'courseplus', 'Unassigned', 0, '2026-04-17 16:02:22'),
(6, 'bluepoint', 'Unassigned', 0, '2026-04-20 07:48:19');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `phone`, `designation`, `company_id`, `created_at`) VALUES
(1, 'mh', 'mh@gmail.com', '01886208226', 'mto', 4, '2026-04-17 15:53:10'),
(2, 'mh', 'mh@gmail.com', '01886208226', 'mto', 4, '2026-04-17 15:59:03'),
(3, 'mh', 'sdsad@gmail.com', '01886208226', 'safdsaf', 5, '2026-04-17 16:02:43');

-- --------------------------------------------------------

--
-- Table structure for table `deals`
--

CREATE TABLE `deals` (
  `id` int(11) NOT NULL,
  `deal_name` varchar(255) NOT NULL,
  `deal_value` decimal(10,2) DEFAULT 0.00,
  `stage` varchar(50) DEFAULT 'Lead',
  `link_company` varchar(255) NOT NULL,
  `service_required` varchar(255) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `platform` varchar(100) DEFAULT NULL,
  `sales_officer` varchar(255) DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deals`
--

INSERT INTO `deals` (`id`, `deal_name`, `deal_value`, `stage`, `link_company`, `service_required`, `currency`, `start_date`, `end_date`, `platform`, `sales_officer`, `additional_notes`, `created_at`) VALUES
(8, 'asdsa', 222.00, 'Proposal', 'Acme Corp', 'asd', 'EUR', '2026-04-23', '2026-04-21', 'Referral', 'qwedqwde', '33243', '2026-04-20 11:27:43'),
(9, 'asdsa', 222.00, 'Proposal', 'Acme Corp', 'asd', 'EUR', '2026-04-23', '2026-04-21', 'Referral', 'qwedqwde', '33243', '2026-04-20 11:27:46');

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

CREATE TABLE `designations` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`id`, `title`) VALUES
(6, 'demo');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT 'Unassigned',
  `priority` varchar(50) DEFAULT 'Medium',
  `status` varchar(50) DEFAULT 'To-Do',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `assigned_to`, `priority`, `status`, `due_date`, `created_at`) VALUES
(1, 'asdfsf', 'asfsafsaf', 'Unassigned', 'Medium', 'To-Do', '2026-04-22', '2026-04-15 09:35:07'),
(2, 'asdfsf', 'asfsafsaf', 'Unassigned', 'Medium', 'To-Do', '2026-04-22', '2026-04-15 10:33:19'),
(3, 'demo task', 'fewf', 'Unassigned', 'Medium', 'To-Do', '2026-04-22', '2026-04-15 10:33:50'),
(4, 'demo task', 'fewf', 'Unassigned', 'Medium', 'To-Do', '2026-04-22', '2026-04-15 10:45:30'),
(5, 'demo task', 'fewf', 'Unassigned', 'Medium', 'To-Do', '2026-04-22', '2026-04-15 11:00:53'),
(6, 'yrrhjre', 'rthrtehtrh', 'mahee', 'Medium', 'To-Do', '2026-04-22', '2026-04-15 11:01:30'),
(7, 'yrrhjre', 'rthrtehtrh', 'mahee', 'Medium', 'To-Do', '2026-04-22', '2026-04-15 11:13:34'),
(8, 'yrrhjre', 'rthrtehtrh', 'mahee', 'Medium', 'To-Do', '2026-04-22', '2026-04-15 11:26:47'),
(9, 'yrrhjre', 'rthrtehtrh', 'mahee', 'Medium', 'To-Do', '2026-04-22', '2026-04-15 11:28:00'),
(10, 'yrrhjre', 'rthrtehtrh', 'mahee', 'Medium', 'To-Do', '2026-04-22', '2026-04-15 11:28:13'),
(11, 'yrrhjre', 'rthrtehtrh', 'mahee', 'Medium', 'To-Do', '2026-04-22', '2026-04-15 11:40:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','manager','agent') NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `designation`, `status`, `created_at`) VALUES
(1, 'Super Admin', 'superadmin', NULL, '$2y$10$dUb0INpo4wxqj86sWtmIMu3ApmG75kUkHrUbINxiGR31aeTrgdVHq', 'super_admin', NULL, 'active', '2026-04-07 05:06:03'),
(2, 'mahee', 'mahee', 'mahee@gmail.com', '$2y$10$EIhuMGBuuGdOWA9m0ErA..FTYGhzgw8ctha.jDmUpF.4/qn7E8rdW', 'admin', '', 'inactive', '2026-04-10 11:37:26'),
(3, 'demo', 'man', 'man@gmail.com', '$2y$10$7Ua5A0Opx7F3boXtDSlwiec7jLO84GzG3nUrw5QOKr61bWTPFdR4S', 'manager', '', 'inactive', '2026-04-10 11:48:27'),
(4, 'demo A', 'agent1', 'agent1@gmail.com', '$2y$10$SfOxJK3fACuUZfp6Z8X.ieqQjVluXDlOhB3uTTEQK1fP2t0VRtCMa', 'agent', '', 'active', '2026-04-10 11:52:49'),
(6, 'jinia', 'jinia-05', 'dfgdgf@gmail.com', '$2y$10$gy0kQ8y86S.TDFHaU4Or8udeckDZeAB27sZ5lZp1EZ0dmeBWvrGfC', 'admin', 'demo', 'active', '2026-04-17 09:52:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deals`
--
ALTER TABLE `deals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `designations`
--
ALTER TABLE `designations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`username`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `deals`
--
ALTER TABLE `deals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
