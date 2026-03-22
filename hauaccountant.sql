-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 22, 2026 at 01:40 AM
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
-- Database: `hauaccountant`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `affected_record` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `affected_record`, `details`, `created_at`) VALUES
(1, 2, 'DELETE_EXPENSE', 'expenses', 'Deleted expense ID: 2', '2026-02-15 06:41:51'),
(2, 2, 'ADD_PRODUCT', 'inventory', 'Added product: ABY', '2026-02-15 06:43:44'),
(3, 2, 'SET_BUDGET', 'budget', 'Set budget for April 2026', '2026-02-15 06:43:57'),
(4, 2, 'ADD_PRODUCT', 'inventory', 'Added product: ABY', '2026-02-17 13:28:47'),
(5, 2, 'ADD_SALE', 'sales', 'Added sale: 1 x Laptop', '2026-02-17 13:29:05'),
(6, 5, 'DELETE_SALE', 'sales', 'Deleted sale ID: 3 - Receipt: ', '2026-03-14 09:35:40'),
(7, 5, 'DELETE_SALE', 'sales', 'Deleted sale ID: 1 - Receipt: ', '2026-03-14 09:35:43'),
(8, 5, 'DELETE_EXPENSE', 'expenses', 'Deleted expense: Rent - ₱15,000.00', '2026-03-14 11:07:33'),
(9, 5, 'DELETE_SALE', 'sales', 'Deleted sale ID: 2 - Receipt: ', '2026-03-14 12:06:32'),
(10, 5, 'ADD_SALE', 'sales', 'Added sale: 50 x Notebook - Receipt: INV-20260314-7692', '2026-03-14 12:07:01'),
(11, 5, 'ADD_EXPENSE', 'expenses', 'Added expense: Rent - ₱12,000.00', '2026-03-14 12:35:39'),
(12, 5, 'ADD_EXPENSE', 'expenses', 'Added expense: Supplies - ₱120.00', '2026-03-14 12:35:56');

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `month_year` varchar(20) NOT NULL,
  `sales_target` decimal(10,2) NOT NULL,
  `expense_limit` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `admin_reply` text DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `user_id`, `name`, `email`, `subject`, `message`, `status`, `admin_reply`, `replied_by`, `replied_at`, `created_at`) VALUES
(4, 6, 'BENEDICK', 'lalicalmer@gmail.com', 'Technical Issue', 'There\'s an error on reports', 'replied', 'I\'ll fix that right away.', 6, '2026-03-21 15:11:23', '2026-03-21 15:11:02'),
(5, 5, 'BAO', 'lalicalmer@gmail.com', 'Technical Issue', 'Error again', 'replied', 'i\'ll fix it right away', 6, '2026-03-21 15:12:16', '2026-03-21 15:11:53');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'cash',
  `vendor` varchar(255) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `expense_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `category`, `amount`, `payment_method`, `vendor`, `reference_no`, `description`, `expense_date`, `created_by`, `created_at`) VALUES
(6, 'Rent', 500.03, 'cash', 'aawd', '123', 'rent', '2026-03-15', 5, '2026-03-15 06:25:10'),
(8, 'Supplies', 200.00, 'cash', 'wad', '123', 'YEs', '2026-03-20', 5, '2026-03-20 12:20:38');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_snapshots`
--

CREATE TABLE `inventory_snapshots` (
  `id` int(11) NOT NULL,
  `snapshot_date` date NOT NULL,
  `total_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_products` int(11) NOT NULL DEFAULT 0,
  `total_stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_snapshots`
--

INSERT INTO `inventory_snapshots` (`id`, `snapshot_date`, `total_value`, `total_cost`, `total_products`, `total_stock`, `created_at`) VALUES
(1, '2026-03-15', 3750.00, 3000.00, 1, 25, '2026-03-15 10:41:24'),
(2, '2026-03-14', 3750.00, 3000.00, 1, 25, '2026-03-15 10:41:24'),
(3, '2026-03-13', 3750.00, 3000.00, 1, 25, '2026-03-15 10:41:24'),
(4, '2026-03-12', 3750.00, 3000.00, 1, 25, '2026-03-15 10:41:24'),
(5, '2026-03-11', 3750.00, 3000.00, 1, 25, '2026-03-15 10:41:24'),
(6, '2026-03-10', 3750.00, 3000.00, 1, 25, '2026-03-15 10:41:24'),
(7, '2026-03-09', 3750.00, 3000.00, 1, 25, '2026-03-15 10:41:24'),
(8, '2026-03-16', 11940.00, 8700.00, 3, 85, '2026-03-16 13:03:26'),
(9, '2026-03-20', 11940.00, 8700.00, 3, 85, '2026-03-20 09:44:36'),
(10, '2026-03-19', 11940.00, 8700.00, 3, 85, '2026-03-20 09:44:36'),
(11, '2026-03-18', 11940.00, 8700.00, 3, 85, '2026-03-20 09:44:36'),
(12, '2026-03-17', 11940.00, 8700.00, 3, 85, '2026-03-20 09:44:36'),
(13, '2026-03-21', 11940.00, 8700.00, 3, 85, '2026-03-21 12:38:29'),
(14, '2026-03-22', 11940.00, 8700.00, 3, 85, '2026-03-21 16:05:12');

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--

CREATE TABLE `login_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('success','failed') DEFAULT 'success'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_history`
--

INSERT INTO `login_history` (`id`, `user_id`, `login_time`, `ip_address`, `user_agent`, `status`) VALUES
(1, 5, '2026-03-20 12:00:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(2, 6, '2026-03-20 12:49:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(3, 5, '2026-03-20 12:50:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(5, 6, '2026-03-20 12:50:59', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(6, 5, '2026-03-20 12:51:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(7, 5, '2026-03-20 12:59:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(8, 6, '2026-03-20 13:01:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(9, 5, '2026-03-20 13:17:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(10, 5, '2026-03-20 13:18:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(11, 5, '2026-03-20 13:19:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(12, 5, '2026-03-20 13:23:20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(13, 6, '2026-03-20 13:41:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(14, 5, '2026-03-21 12:38:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(15, 6, '2026-03-21 12:42:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(16, 5, '2026-03-21 12:43:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(17, 6, '2026-03-21 12:43:46', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(18, 5, '2026-03-21 12:50:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(19, 5, '2026-03-21 12:54:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(20, 6, '2026-03-21 12:54:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(22, 6, '2026-03-21 13:14:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(23, 6, '2026-03-21 14:00:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(24, 6, '2026-03-21 14:03:34', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(25, 5, '2026-03-21 14:17:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(26, 5, '2026-03-21 14:18:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(27, 6, '2026-03-21 14:19:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(28, 5, '2026-03-21 14:26:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(29, 6, '2026-03-21 14:30:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(30, 6, '2026-03-21 14:47:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(31, 5, '2026-03-21 15:11:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(32, 6, '2026-03-21 15:12:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success'),
(33, 6, '2026-03-21 16:05:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'success');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT 5,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `category`, `stock_quantity`, `cost_price`, `selling_price`, `sku`, `barcode`, `location`, `reorder_level`, `description`, `created_by`, `created_at`) VALUES
(7, 'ABY', 'Electronics', 25, 120.00, 150.00, '2', '', 'Mabalacat, Pampanga', 5, 'awdad', 5, '2026-03-15 02:55:24'),
(9, 'Pipe 3', 'Equipment', 30, 130.00, 185.00, 'EQU-0098', '', 'likod', 0, '', 5, '2026-03-15 12:06:47'),
(10, 'Pipe 2', 'Equipment', 30, 60.00, 88.00, 'EQU-9029', '', 'likod', 1, '', 5, '2026-03-15 12:06:47');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `order_group_id` varchar(50) DEFAULT NULL,
  `order_date` datetime DEFAULT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `tax` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT 'Walk-in Customer',
  `payment_method` varchar(50) DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `sale_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `order_group_id`, `order_date`, `receipt_no`, `product_id`, `quantity`, `unit_price`, `tax`, `total_amount`, `customer_name`, `payment_method`, `notes`, `sale_date`, `created_by`, `created_at`) VALUES
(5, NULL, NULL, 'INV-20260315-4646', 7, 2, 150.00, 36.00, 336.00, 'Lalic', 'cash', '', '2026-03-15', 5, '2026-03-15 02:56:05'),
(6, NULL, NULL, 'INV-20260315-2068', 7, 3, 150.00, 54.00, 504.00, 'Lalic', 'cash', '', '2026-03-15', 5, '2026-03-15 06:23:11'),
(7, NULL, NULL, 'INV-20260315-9329', 9, 20, 185.00, 444.00, 4144.00, 'ayesa', 'cash', '', '2026-03-15', 5, '2026-03-15 12:07:19');

-- --------------------------------------------------------

--
-- Table structure for table `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `adjustment` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_history`
--

INSERT INTO `stock_history` (`id`, `product_id`, `previous_stock`, `new_stock`, `adjustment`, `reason`, `created_by`, `created_at`) VALUES
(1, 7, 0, 25, 25, 'Stock received', 5, '2026-03-15 06:49:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `business_name` varchar(255) DEFAULT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `business_name`, `owner_name`, `email`, `password`, `role`, `status`, `created_at`) VALUES
(2, 'ABY', 'ABY', 'almerlalic@yahoo.com', '$2y$10$ZGJwuKYxyHgyCPwbIdNOguCsjjoWaK1NlI.PhCZwDcy9qLLmr6s0e', 'admin', 'active', '2026-02-15 06:41:11'),
(5, 'ABY', 'BAO', 'otsuki546@gmail.com', '$2y$10$.0uDonhFksnVwRZG/IWGZuHgZ3nkYgzwOEDjftn80Wwa75RWZHPoC', 'staff', 'active', '2026-03-14 07:12:17'),
(6, 'BEN', 'BENEDICK', 'lalicalmer@gmail.com', '$2y$10$GBYONs2m4m2KqvE4YnaTQ.Ub/Iy/PtAGsCR.9US9rV/hMlSiMRcd2', 'admin', 'active', '2026-03-20 12:49:27'),
(7, 'HAUccountant Demo', 'Admin User', 'admin@hauccountant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', '2026-03-20 14:48:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_month_year` (`month_year`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `replied_by` (`replied_by`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `inventory_snapshots`
--
ALTER TABLE `inventory_snapshots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `snapshot_date` (`snapshot_date`),
  ADD KEY `snapshot_date_2` (`snapshot_date`);

--
-- Indexes for table `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sku` (`sku`),
  ADD KEY `barcode` (`barcode`),
  ADD KEY `fk_product_created_by` (`created_by`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_order_group` (`order_group_id`);

--
-- Indexes for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inventory_snapshots`
--
ALTER TABLE `inventory_snapshots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contact_messages_ibfk_2` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `login_history`
--
ALTER TABLE `login_history`
  ADD CONSTRAINT `login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD CONSTRAINT `stock_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
