-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 10:58 AM
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
-- Database: `dajawonta_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `booking_date_from` date NOT NULL,
  `booking_date_to` date NOT NULL,
  `booking_time_from` time NOT NULL,
  `booking_time_to` time NOT NULL,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_id` varchar(255) DEFAULT NULL,
  `payment_link` text DEFAULT NULL,
  `booking_time` time NOT NULL,
  `booking_status` varchar(50) NOT NULL DEFAULT 'pending',
  `special_request` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'unpaid',
  `paymongo_checkout_id` varchar(100) DEFAULT NULL,
  `paymongo_payment_intent_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `provider_id`, `customer_name`, `customer_email`, `customer_phone`, `booking_date_from`, `booking_date_to`, `booking_time_from`, `booking_time_to`, `total_price`, `payment_id`, `payment_link`, `booking_time`, `booking_status`, `special_request`, `price`, `payment_status`, `paymongo_checkout_id`, `paymongo_payment_intent_id`, `created_at`) VALUES
(1, 36, 'ANTHONIE FENY CATALAN', 'venzonanthonie@gmail.com', '09478984921', '2025-10-06', '2025-10-10', '00:00:00', '00:00:00', 0.00, NULL, NULL, '16:00:00', 'pending', NULL, 0.00, 'unpaid', NULL, NULL, '2025-10-24 06:22:44'),
(2, 37, 'ANTHONIE FENY CATALAN', 'venzonanthonie@gmail.com', '09478984921', '2025-10-08', '2025-10-08', '00:00:00', '00:00:00', 0.00, NULL, NULL, '16:51:00', 'pending', NULL, 0.00, 'unpaid', NULL, NULL, '2025-10-24 06:35:26'),
(3, 37, 'ANTHONIE FENY CATALAN', 'venzonanthonie@gmail.com', '09478984921', '2025-10-08', '2025-10-08', '00:00:00', '00:00:00', 0.00, NULL, NULL, '16:51:00', 'pending', NULL, 0.00, 'unpaid', NULL, NULL, '2025-10-24 06:38:20'),
(4, 36, 'ANTHONIE FENY CATALAN', 'venzonanthonie@gmail.com', '09478984921', '2025-10-06', '2025-10-10', '00:00:00', '00:00:00', 0.00, NULL, NULL, '16:00:00', 'pending', NULL, 0.00, 'unpaid', NULL, NULL, '2025-10-24 06:45:03'),
(5, 36, 'ANTHONIE FENY CATALAN', 'venzonanthonie@gmail.com', '09478984921', '2025-10-06', '2025-10-10', '00:00:00', '00:00:00', 0.00, NULL, NULL, '16:00:00', 'completed', NULL, 0.00, 'unpaid', NULL, NULL, '2025-10-24 07:01:23'),
(6, 36, 'ANTHONIE FENY CATALAN', 'venzonanthonie@gmail.com', '09478984921', '2025-10-06', '2025-10-10', '00:00:00', '00:00:00', 0.00, NULL, NULL, '16:00:00', 'pending', 'Please bring cheed', 0.00, 'unpaid', NULL, NULL, '2025-10-24 08:22:10');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `link`, `is_read`, `created_at`, `role`) VALUES
(28, 3, 'New provider \'LNM CARPENTRY\' has registered.', 'dashboard.php?action=confirmService_providers&notification_id=28', 1, '2025-10-06 08:00:57', 2),
(29, 9, 'Congratulations! Your service \'LNM CARPENTRY\' has been approved.', 'dashboard.php?action=view_listings&provider_id=9', 1, '2025-10-06 08:01:19', 1),
(30, 3, 'New provider \'LNM CARPENTRY\' has registered.', 'dashboard.php?action=confirmService_providers&notification_id=30', 1, '2025-10-08 08:52:02', 2),
(31, 9, 'Congratulations! Your service \'LNM CARPENTRY\' has been approved.', 'dashboard.php?action=view_listings&provider_id=9', 1, '2025-10-08 08:54:23', 1),
(32, 3, 'New provider \'CARAGA CARPENTRY\' has registered.', 'dashboard.php?action=confirmService_providers&notification_id=32', 1, '2025-10-24 04:43:11', 2),
(33, 9, 'Congratulations! Your service \'CARAGA CARPENTRY\' has been approved.', 'dashboard.php?action=view_listings&provider_id=9', 1, '2025-10-24 04:44:07', 1),
(34, 37, 'You have a new booking request from ANTHONIE FENY CATALAN.', 'provider_booking_details.php?booking_id=2', 0, '2025-10-24 06:35:26', 1),
(35, 37, 'You have a new booking request from ANTHONIE FENY CATALAN.', 'provider_booking_details.php?booking_id=3', 0, '2025-10-24 06:38:20', 1),
(36, 9, 'You have a new booking request from ANTHONIE FENY CATALAN.', 'provider_booking_details.php?booking_id=4', 0, '2025-10-24 06:45:03', 1),
(37, 9, 'You have a new booking request from ANTHONIE FENY CATALAN.', 'dashboard.php?action=provider_booking_details&booking_id=5', 0, '2025-10-24 07:01:23', 1),
(38, 0, 'Your booking request has been APPROVED by LNM CARPENTRY.', 'dashboard.php?action=view_booking&booking_id=5', 0, '2025-10-24 08:05:12', 0),
(39, 0, 'Your booking request has been COMPLETED by LNM CARPENTRY.', 'dashboard.php?action=view_booking&booking_id=5', 0, '2025-10-24 08:05:28', 0),
(40, 9, 'You have a new booking request from ANTHONIE FENY CATALAN.', 'dashboard.php?action=provider_booking_details&booking_id=6', 0, '2025-10-24 08:22:10', 1);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_name`, `description`) VALUES
(1, 'Plumbing', 'Leak fixing, pipe installation'),
(2, 'Electrical', 'Wiring, outlet repair, appliance setup'),
(3, 'Carpentry', 'Furniture repair, door/window fixes'),
(4, 'Painting & Renovation', 'House painting, renovation works'),
(5, 'Masonry & Welding', 'Construction, welding jobs'),
(6, 'Shoe Repair', 'Repair and restoration of shoes');

-- --------------------------------------------------------

--
-- Table structure for table `service_providers`
--

CREATE TABLE `service_providers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `company_address` text NOT NULL,
  `company_email` varchar(255) NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `service_id` int(11) DEFAULT NULL,
  `service_description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `available_date` date NOT NULL DEFAULT '2025-01-01',
  `available_time` time NOT NULL DEFAULT '00:00:00',
  `is_approved` int(11) NOT NULL,
  `available_date_from` date DEFAULT NULL,
  `available_date_to` date DEFAULT NULL,
  `available_time_from` time DEFAULT NULL,
  `available_time_to` time DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_providers`
--

INSERT INTO `service_providers` (`id`, `user_id`, `company_name`, `company_address`, `company_email`, `contact_number`, `service_name`, `description`, `registration_date`, `service_id`, `service_description`, `price`, `available_date`, `available_time`, `is_approved`, `available_date_from`, `available_date_to`, `available_time_from`, `available_time_to`, `is_available`) VALUES
(36, 9, 'LNM CARPENTRY', 'PUROK 1, La Union, Cabadbaran City, Agusan del Norte', 'venzonanthonie@gmail.com', '+639518793041', 'Carpentry', NULL, '2025-10-06 08:00:57', 3, 'Furniture repair, door/window fixes', 500.00, '2025-01-01', '00:00:00', 1, '2025-10-06', '2025-10-11', '16:00:00', '16:00:00', 1),
(37, 9, 'LNM CARPENTRY', 'PUROK 1\r\nNATIONAL HIGHWAY', 'venzonanthoniea@gmail.com', '+639329342620', 'Carpentry', NULL, '2025-10-08 08:52:02', 3, 'Furniture repair, door/window fixes', 500.00, '2025-01-01', '00:00:00', 1, '2025-10-08', '2025-10-08', '16:51:00', '16:51:00', 1),
(38, 9, 'CARAGA CARPENTRY', 'BUTUAN CITY', 'venzonanthonie@gmail.com', '09478984921', 'Masonry & Welding', NULL, '2025-10-24 04:43:11', 5, 'Construction, welding jobs', 500.00, '2025-01-01', '00:00:00', 1, '2025-10-01', '2025-10-31', '12:01:00', '13:12:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(50) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `birthday` date NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `region` varchar(150) NOT NULL,
  `province` varchar(150) NOT NULL,
  `municipality` varchar(150) NOT NULL,
  `barangay` varchar(150) DEFAULT NULL,
  `purok` varchar(150) DEFAULT NULL,
  `role` enum('client','provider') NOT NULL,
  `password` varchar(255) NOT NULL,
  `verification_code` varchar(255) DEFAULT NULL,
  `confirmation_token` varchar(255) DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_rules` int(11) NOT NULL,
  `profile_image` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `suffix`, `gender`, `birthday`, `phone_number`, `email`, `region`, `province`, `municipality`, `barangay`, `purok`, `role`, `password`, `verification_code`, `confirmation_token`, `token_expires_at`, `reset_token`, `reset_token_expiry`, `verification_token`, `is_verified`, `created_at`, `user_rules`, `profile_image`) VALUES
(3, 'ANTHONIE FENY', 'V.', 'CATALAN', '', 'Male', '2025-09-15', '09329342620', 'venzonanthonie@gmail.com', 'Caraga', 'REGIONAL OFFICE', 'Tubay', 'Magosilom', 'NATIONAL HIGHWAY', 'provider', '$2y$10$l5TulsvLhQn7qtv.7hKAxepVOZERT8dw0Hu3KH7fuHwP0LwmjHSCe', NULL, NULL, NULL, NULL, NULL, '', 1, '2025-09-15 03:34:08', 2, 'user_3_68fafb418b8ad.jpg'),
(9, 'FELY GRACE', 'VENZON', 'CATALAN', '', 'Female', '2025-11-08', '09478984921', 'catalanfelygrace823@gmail.com', 'Caraga', 'Surigao del Sur', 'Cantilan', 'Magosilom', 'NATIONAL HIGHWAY', 'provider', '$2y$10$gwJxF0S/FJjSynR1Qtm37uxkcN6c1NInPVp7HDaN0WyLl5xxaondW', NULL, NULL, NULL, '67ff2757c41ac137f103d97b7c5524d245505ce5982213898e17ad43e8c2bc76', '2025-10-24 12:41:07', NULL, 1, '2025-10-04 07:07:39', 1, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `service_providers`
--
ALTER TABLE `service_providers`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `service_providers`
--
ALTER TABLE `service_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `service_providers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
