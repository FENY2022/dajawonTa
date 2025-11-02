-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 02, 2025 at 01:39 PM
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
  `service_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `role` int(11) NOT NULL,
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
  `is_approve` tinyint(1) NOT NULL DEFAULT 0,
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

INSERT INTO `bookings` (`id`, `provider_id`, `service_id`, `customer_id`, `role`, `customer_name`, `customer_email`, `customer_phone`, `booking_date_from`, `booking_date_to`, `booking_time_from`, `booking_time_to`, `total_price`, `payment_id`, `payment_link`, `booking_time`, `booking_status`, `is_approve`, `special_request`, `price`, `payment_status`, `paymongo_checkout_id`, `paymongo_payment_intent_id`, `created_at`) VALUES
(33, 39, 0, 9, 1, 'ANTHONIE FENY V. CATALAN', 'venzonanthonie@gmail.com', '09329342620', '2025-10-26', '2025-10-31', '07:00:00', '17:00:00', 1800.00, 'cs_EVNtGkTp1x4PdUTk8cw6oUiv', 'https://checkout.paymongo.com/cs_EVNtGkTp1x4PdUTk8cw6oUiv_client_BLKvAc7aSBe4BcV7bimCMmFb#cGtfdGVzdF9CSFFkY1ZrdTFINmt3N2FkeG5OUWE3RnI=', '00:00:00', 'approved', 1, '', 0.00, 'paid', NULL, 'pi_5F4urWfHeuRoCiS5MwiQ8x4C', '2025-10-26 03:48:04'),
(34, 39, 0, 9, 1, 'ANTHONIE FENY CATALAN', 'venzonanthonie@gmail.com', '09478984921', '2025-10-27', '2025-10-30', '07:10:00', '17:00:00', 1800.00, 'cs_UY4GbbzJNir6D9TaMXLuctZu', 'https://checkout.paymongo.com/cs_UY4GbbzJNir6D9TaMXLuctZu_client_oCa1U1SQ2TSWiB8pkoJchp6L#cGtfdGVzdF9CSFFkY1ZrdTFINmt3N2FkeG5OUWE3RnI=', '00:00:00', 'approved', 1, '', 0.00, 'paid', NULL, 'pi_6murwFPD8zQY2ZLkLYjr7uZR', '2025-10-26 23:49:40'),
(35, 39, 0, 9, 1, 'ANTHONIE FENY CATALAN', 'venzonanthonie@gmail.com', '09478984921', '2025-10-27', '2025-10-31', '08:00:00', '17:00:00', 1800.00, 'cs_a9cwu8nwtqs7BpgLKMczxpnB', 'https://checkout.paymongo.com/cs_a9cwu8nwtqs7BpgLKMczxpnB_client_o1b7Bq9Zuc9ua8CwjhjoNC1u#cGtfdGVzdF9CSFFkY1ZrdTFINmt3N2FkeG5OUWE3RnI=', '00:00:00', 'approved', 1, '', 0.00, 'paid', NULL, 'pi_H9pVhgNvySQySkqLFxdJ3A7x', '2025-10-27 02:58:02'),
(36, 39, 0, 12, 0, 'ANTHONIE FENY CATALAN', 'venzonanthonie@gmail.com', '09478984921', '2025-10-27', '2025-10-31', '08:00:00', '17:00:00', 1800.00, 'cs_rWU7uJLBctjqPgnJ7sFsqhjf', 'https://checkout.paymongo.com/cs_rWU7uJLBctjqPgnJ7sFsqhjf_client_B35CDC8nDfvHJGS79AvNGYCm#cGtfdGVzdF9CSFFkY1ZrdTFINmt3N2FkeG5OUWE3RnI=', '00:00:00', 'approved', 1, '', 0.00, 'unpaid', NULL, NULL, '2025-10-27 05:36:32'),
(37, 39, 0, 12, 0, 'ANTHONIE FENY CATALAN', 'venzonanthonie@gmail.com', '09478984921', '2025-10-27', '2025-10-31', '08:00:00', '17:00:00', 1800.00, 'cs_EP4QuWZCfgPK85cVHJs3ijME', 'https://checkout.paymongo.com/cs_EP4QuWZCfgPK85cVHJs3ijME_client_bAW7H59P3bWgeRivZwrt3MkL#cGtfdGVzdF9CSFFkY1ZrdTFINmt3N2FkeG5OUWE3RnI=', '00:00:00', 'approved', 1, '', 0.00, 'paid', NULL, 'pi_dnjiV92iLs4o1tAS3bdGNQJz', '2025-10-27 05:45:04'),
(38, 36, 3, 12, 0, 'ANTHONIE FENY CATALAN', 'venzonanthonie@gmail.com', '09478984921', '2025-10-09', '2025-10-09', '16:00:00', '16:00:00', 500.00, 'cs_9L1dFJ8NEqC5hebBi9Z6ddyc', 'https://checkout.paymongo.com/cs_9L1dFJ8NEqC5hebBi9Z6ddyc_client_fPW75gywtw2pqmnpjcttbV6i#cGtfdGVzdF9CSFFkY1ZrdTFINmt3N2FkeG5OUWE3RnI=', '00:00:00', 'approved', 1, 'fsdfsdf', 0.00, 'unpaid', NULL, NULL, '2025-11-02 12:16:28');

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
(120, 9, 'You have a new booking request from ANTHONIE FENY V. CATALAN.', 'dashboard.php?action=provider_booking_details&booking_id=33', 0, '2025-10-26 03:48:04', 1),
(121, 9, 'Your booking request for Carpentry has been received.', 'dashboard.php?action=my_bookings&booking_id=33', 0, '2025-10-26 03:48:04', 2),
(122, 9, 'Your booking (#33) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=33', 0, '2025-10-26 03:52:01', 1),
(123, 9, 'You have a new booking request from ANTHONIE FENY CATALAN.', 'dashboard.php?action=provider_booking_details&booking_id=34', 0, '2025-10-26 23:49:40', 1),
(124, 9, 'Your booking request for Carpentry has been received.', 'dashboard.php?action=my_bookings&booking_id=34', 0, '2025-10-26 23:49:40', 2),
(125, 9, 'Your booking (#34) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=34', 0, '2025-10-26 23:49:54', 1),
(126, 9, 'You have a new booking request from ANTHONIE FENY CATALAN.', 'dashboard.php?action=provider_booking_details&booking_id=35', 0, '2025-10-27 02:58:02', 1),
(127, 9, 'Your booking request for Carpentry has been received.', 'dashboard.php?action=my_bookings&booking_id=35', 0, '2025-10-27 02:58:02', 2),
(128, 9, 'Your booking (#35) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=35', 0, '2025-10-27 02:58:13', 1),
(129, 9, 'You have a new booking request from ANTHONIE FENY CATALAN.', 'dashboard.php?action=provider_booking_details&booking_id=36', 0, '2025-10-27 05:36:32', 1),
(130, 12, 'Your booking request for Carpentry has been received.', 'dashboard.php?action=my_bookings&booking_id=36', 0, '2025-10-27 05:36:32', 2),
(131, 12, 'Your booking (#36) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=36', 0, '2025-10-27 05:37:06', 0),
(132, 12, 'Your booking (#36) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=36', 0, '2025-10-27 05:37:14', 0),
(133, 12, 'Your booking (#36) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=36', 0, '2025-10-27 05:37:59', 0),
(134, 12, 'Your booking (#36) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=36', 0, '2025-10-27 05:39:11', 0),
(135, 12, 'Your booking (#36) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=36', 0, '2025-10-27 05:40:19', 0),
(136, 9, 'You have a new booking request from ANTHONIE FENY CATALAN.', 'dashboard.php?action=provider_booking_details&booking_id=37', 0, '2025-10-27 05:45:04', 1),
(137, 12, 'Your booking request for Carpentry has been received.', 'dashboard.php?action=my_bookings&booking_id=37', 0, '2025-10-27 05:45:04', 2),
(138, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-10-27 05:46:35', 0),
(139, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-10-27 05:47:45', 0),
(140, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-10-27 05:48:06', 0),
(141, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-10-27 05:48:19', 0),
(142, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-10-27 05:53:08', 0),
(143, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-10-27 05:53:31', 0),
(144, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-10-27 05:54:35', 0),
(145, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-10-27 05:54:56', 0),
(146, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-10-27 06:01:10', 0),
(147, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:33:11', 0),
(148, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:34:23', 0),
(149, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:35:02', 0),
(150, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:35:47', 0),
(151, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:36:21', 0),
(152, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:38:32', 0),
(153, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:38:53', 0),
(154, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=provider_booking_details&booking_id=37', 0, '2025-11-01 13:39:10', 0),
(155, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:39:38', 0),
(156, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:40:38', 0),
(157, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:41:18', 0),
(158, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:41:25', 0),
(159, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:41:52', 0),
(160, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:41:57', 0),
(161, 12, 'Your booking (#37) with CANTILAN CARPENTERO has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=37', 0, '2025-11-01 13:42:59', 0),
(162, 9, 'You have a new booking request from ANTHONIE FENY CATALAN.', 'dashboard.php?action=provider_booking_details&booking_id=38', 0, '2025-11-02 12:16:28', 1),
(163, 12, 'Your booking request for Carpentry has been received.', 'dashboard.php?action=my_bookings&booking_id=38', 0, '2025-11-02 12:16:28', 2),
(164, 12, 'Your booking (#38) with LNM CARPENTRY has been approved! Please proceed with payment.', 'dashboard.php?action=customer_booking_details&booking_id=38', 0, '2025-11-02 12:16:49', 0);

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
(37, 9, 'LNM CARPENTRY', 'PUROK 1NATIONAL HIGHWAY', 'venzonanthonie@gmail.com', '+639329342620', 'Carpentry', '', '2025-10-08 08:52:02', 3, 'Furniture repair, door/window fixes', 500.00, '2025-01-01', '00:00:00', 1, '2025-10-06', '2025-10-08', '16:51:00', '16:51:00', 1),
(38, 9, 'CARAGA CARPENTRY', 'BUTUAN CITY', 'venzonanthonie@gmail.com', '09478984921', 'Masonry & Welding', '', '2025-10-24 04:43:11', 5, 'Construction, welding jobs', 500.00, '2025-01-01', '00:00:00', 1, '2025-10-08', '2025-10-31', '12:01:00', '13:12:00', 1),
(39, 9, 'CANTILAN CARPENTERO', 'CANTILAN SURIGAO DEL SUR', 'venzonanthonie@gmail.com', '09478984921', 'Carpentry', '', '2025-10-26 01:29:18', 3, 'Furniture repair, door/window fixes', 1800.00, '2025-01-01', '00:00:00', 1, '0000-00-00', '2025-11-29', '07:00:00', '17:00:00', 1);

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
  `role` enum('client','provider','admin') NOT NULL,
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
(3, 'ANTHONIE FENY', 'V.', 'CATALAN', '', 'Male', '2025-09-15', '09329342620', 'venzonanthonie@gmail.com', 'Caraga', 'REGIONAL OFFICE', 'Tubay', 'Magosilom', 'NATIONAL HIGHWAY', 'admin', '$2y$10$sT2XdAS5E3tcvCbi8M6CkOTFAH4giqMdABrlcBz9AFiOcgmZWteAO', NULL, NULL, NULL, NULL, NULL, '', 1, '2025-09-15 03:34:08', 2, 'user_3_68fafb418b8ad.jpg'),
(9, 'FELY GRACE', 'VENZON', 'CATALAN', '', 'Female', '2025-11-08', '09478984921', 'catalanfelygrace823@gmail.com', 'Caraga', 'Surigao del Sur', 'Cantilan', 'Magosilom', 'NATIONAL HIGHWAY', 'provider', '$2y$10$RWuKyeU2sWGWt3zxtqGlae8gHrzNwwtVV4IvHqluaLdIC.V4qY0Ie', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-04 07:07:39', 1, ''),
(10, 'Christian Paul', '', 'Guillen', '', 'Male', '2006-10-26', '09556214412', 'christianpaulguillen6@gmail.com', 'Caraga', 'Surigao del Sur', 'Cantilan', 'Magosilom', '', 'provider', '$2y$10$kc.rAv4psAdTPzTYC4wFdO2y28fji0ZKQLhjCyTJFHrY.K/h1mKHG', '3c2f9dbff6f8343dc713ae331dfd7b4c77f24b8cd7c3f5f49c841d6adff6a709', NULL, NULL, NULL, NULL, NULL, 0, '2025-10-26 09:56:31', 2, ''),
(12, 'JAKE', 'MUBAS', 'LAGANGA', '', 'Male', '2002-10-07', '09478984921', 'kiwew52534@filipx.com', 'Caraga', 'Surigao del Sur', 'Tapi', 'Magosilom', 'Cantilan - Madrid Bypass Road', 'client', '$2y$10$i2lT8or7BhdpfVfd8uUuBuYW9uSUURYCkEVvsI3yGrA6MKoGRmdl2', NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-27 03:28:31', 0, 'user_12_69074bcfd720d.png');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=165;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `service_providers`
--
ALTER TABLE `service_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
