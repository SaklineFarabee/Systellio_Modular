-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2026 at 10:35 PM
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
-- Database: `demo_crm`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'CREATE, UPDATE, DELETE, LOGIN, LOGOUT, VIEW, etc.',
  `description` text NOT NULL,
  `entity_type` varchar(50) NOT NULL COMMENT 'User, Task, Company, Deal, Contact, etc.',
  `entity_id` int(11) DEFAULT NULL,
  `old_value` longtext DEFAULT NULL COMMENT 'Previous value before update',
  `new_value` longtext DEFAULT NULL COMMENT 'New value after update',
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL,
  `campaign_name` varchar(255) NOT NULL,
  `campaign_type` varchar(50) NOT NULL COMMENT 'Email, Social Media, Content Marketing, Paid Ads, Event',
  `description` text DEFAULT NULL,
  `target_audience` varchar(255) DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `assigned_to` varchar(100) DEFAULT 'Unassigned',
  `deal_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Planning' COMMENT 'Planning, Active, Completed, On Hold',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campaigns`
--

INSERT INTO `campaigns` (`id`, `campaign_name`, `campaign_type`, `description`, `target_audience`, `budget`, `currency`, `start_date`, `end_date`, `assigned_to`, `deal_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'mh', 'Email', 'fasdfsafsa', 'dfdfd', 12.00, 'EUR', '2026-05-14', '2026-05-20', 'superadmin', NULL, 'Active', '2026-05-11 17:43:38', '2026-05-11 17:43:38'),
(2, 'mh', 'Email', 'fdhfdhdfh', 'ghdghdgh', 0.00, 'BDT', '2026-05-16', '2026-05-16', 'agent', NULL, 'On Hold', '2026-05-11 17:49:45', '2026-05-11 17:49:45');

-- --------------------------------------------------------

--
-- Table structure for table `client_notes`
--

CREATE TABLE `client_notes` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `author` varchar(100) DEFAULT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `client_notes`
--

INSERT INTO `client_notes` (`id`, `client_id`, `author`, `note`, `created_at`) VALUES
(1, 3, 'Super Admin', 'ksudgf', '2026-05-11 11:33:08'),
(2, 3, 'Super Admin', 'New Demo requested', '2026-05-11 11:33:42');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `assigned_agent` varchar(255) DEFAULT 'Unassigned',
  `company_email` varchar(255) DEFAULT NULL,
  `company_number` varchar(50) DEFAULT NULL,
  `company_website` varchar(255) DEFAULT NULL,
  `fb_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `insta_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `total_contacts` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `company_name`, `assigned_agent`, `company_email`, `company_number`, `company_website`, `fb_url`, `linkedin_url`, `insta_url`, `twitter_url`, `total_contacts`, `created_at`) VALUES
(4, 'Peersolution', 'demo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-17 15:51:49'),
(5, 'courseplus', 'Unassigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-17 16:02:22'),
(6, 'bluepoint', 'Unassigned', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, '2026-04-20 07:48:19'),
(18, 'mhtech zone', 'admin', '', '+880', '', '', '', '', '', 0, '2026-05-11 08:03:35');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_agents` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `phone`, `designation`, `company_id`, `created_at`, `assigned_agents`) VALUES
(3, 'mh', 'sdsad@gmail.com', '01886208226', 'safdsaf', 5, '2026-04-17 16:02:43', NULL),
(4, 'mh', 'mh@gmail.com', '01886208226', 'mto', 5, '2026-05-11 18:46:57', 'admin,agent');

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
(18, 'email campaing', 1000.00, 'Proposal', 'courseplus', 'email', 'EUR', '2026-05-14', '2026-05-22', 'Referral', 'mahee', 'mhhmfhm', '2026-05-11 19:14:57');

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
(6, 'demo5'),
(7, 'Testing Designation'),
(8, 'mto');

-- --------------------------------------------------------

--
-- Table structure for table `subtasks`
--

CREATE TABLE `subtasks` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `is_done` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subtasks`
--

INSERT INTO `subtasks` (`id`, `task_id`, `title`, `is_done`, `created_at`) VALUES
(1, 17, 'fghfdhdfhfdh', 0, '2026-05-11 18:26:07'),
(2, 18, 'fbfbfb', 0, '2026-05-11 18:27:44'),
(3, 18, 'fbfbfbf', 0, '2026-05-11 18:27:44'),
(4, 18, 'fbfbfbfbfbfb', 0, '2026-05-11 18:27:44'),
(5, 19, 'gncvnc', 0, '2026-05-11 18:37:37'),
(6, 19, 'vcnvcnvc', 0, '2026-05-11 18:37:37'),
(7, 19, 'cvncvnv', 0, '2026-05-11 18:37:37');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_by` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `assigned_to`, `priority`, `status`, `due_date`, `created_at`, `assigned_by`, `updated_at`) VALUES
(1, 'mh', 'mh', 'manager', 'High', 'In-Progress', '2026-05-11', '2026-05-11 18:27:44', 'Super Admin', '2026-05-13 20:21:24'),
(19, 'mh', '', 'admin', 'High', 'To-Do', '2026-05-12', '2026-05-11 18:37:37', 'Super Admin', '2026-05-13 20:12:27');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `designation`, `status`, `created_at`, `phone`) VALUES
(1, 'Super Admin', 'superadmin', 'peersolution.bpo.mahee02@gmail.com', '$2y$10$dUb0INpo4wxqj86sWtmIMu3ApmG75kUkHrUbINxiGR31aeTrgdVHq', 'super_admin', NULL, 'active', '2026-04-07 05:06:03', NULL),
(7, 'admin', 'admin', 'admin@gmail.com', '$2y$10$20uc85sylRwvRoQ8SeHmyeWnZZ8enmEYm.CICFlMx2BtimsKL0uoG', 'admin', '', 'active', '2026-05-05 10:21:08', NULL),
(8, 'agent', 'agent', 'agent@gmail.com', '$2y$10$m9UGSFMhzm/y85TpW/En1eRyx1uX3G8kw5FY4eCEo04PLs7jhUH/q', 'agent', 'mto', 'active', '2026-05-05 10:24:13', NULL),
(9, 'manager', 'manager', 'manager@gmail.com', '$2y$10$A2I1fiXhKlAExceg5BJTpOYyRDXVU7F187O48ekOBu9m0wzbyb0FO', 'manager', 'demo5', 'active', '2026-05-05 10:25:02', '+8801886208226');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `client_notes`
--
ALTER TABLE `client_notes`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `subtasks`
--
ALTER TABLE `subtasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`);

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
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `client_notes`
--
ALTER TABLE `client_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `deals`
--
ALTER TABLE `deals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `designations`
--
ALTER TABLE `designations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `subtasks`
--
ALTER TABLE `subtasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
