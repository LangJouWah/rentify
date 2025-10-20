-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 20, 2025 at 02:23 PM
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
-- Database: `rentify_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `privileges` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `user_id`, `privileges`) VALUES
(1, 7, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('confirmed','completed','cancelled') DEFAULT 'confirmed',
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `car_id`, `start_date`, `end_date`, `total_amount`, `status`, `payment_status`, `booking_date`) VALUES
(1, 10, 10, '2025-10-20', '2025-10-22', 99999999.99, 'cancelled', 'pending', '2025-10-20 08:44:30'),
(2, 10, 10, '2025-10-29', '2025-10-30', 99999999.00, 'cancelled', 'completed', '2025-10-20 08:54:41'),
(3, 10, 10, '2025-10-20', '2025-10-21', 99999999.00, 'cancelled', 'completed', '2025-10-20 09:08:50'),
(4, 10, 10, '2025-10-20', '2025-10-23', 3000.00, 'confirmed', 'completed', '2025-10-20 09:10:53'),
(5, 12, 11, '2025-10-20', '2025-10-23', 1200.00, 'confirmed', 'completed', '2025-10-20 12:16:33');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `car_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `type` enum('sedan','SUV','convertible','other') DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `fuel_type` enum('petrol','diesel','electric') DEFAULT NULL,
  `transmission` enum('manual','automatic') DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `status` enum('available','rented','under maintenance') DEFAULT 'available',
  `image` varchar(255) DEFAULT NULL,
  `location` varchar(100) NOT NULL DEFAULT 'Manila'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`car_id`, `owner_id`, `brand`, `model`, `year`, `type`, `capacity`, `fuel_type`, `transmission`, `price`, `status`, `image`, `location`) VALUES
(6, 1, 'Honda', 'City', 2022, 'sedan', 4, 'petrol', '', 2000.00, 'available', 'Uploads/cars/car_68ceaf6d4afcc.jpg', 'Manila'),
(8, 2, 'Ford', 'Everest', 2025, 'SUV', 7, 'petrol', '', 4000.00, 'available', 'Uploads/cars/car_68d23498d9a82.jpg', 'Manila'),
(9, 4, 'Toyota', 'Vios', 2023, 'sedan', 4, 'petrol', '', 2000.00, 'available', 'Uploads/cars/placeholder.jpg', 'Manila'),
(10, 5, 'TUYUTA', 'Honda Trueno AE86', 1998, '', 4, '', 'manual', 1000.00, 'available', 'Uploads/cars/car_68f5f1d2461de.png', 'Manila'),
(11, 5, 'toshiba', 'race car', 1997, 'sedan', 4, 'petrol', '', 400.00, 'rented', 'Uploads/cars/car_68f6091194997.png', 'Manila');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `conversation_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `car_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`conversation_id`, `customer_id`, `owner_id`, `car_id`, `created_at`) VALUES
(1, 6, 2, 8, '2025-10-17 18:37:42'),
(2, 10, 11, 10, '2025-10-20 08:04:13'),
(3, 10, 9, 9, '2025-10-20 10:53:34'),
(4, 12, 11, 10, '2025-10-20 10:55:29');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `car_id` int(11) DEFAULT NULL,
  `last_service_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `mileage` int(11) DEFAULT NULL,
  `service_type` enum('oil_change','tire_rotation','other') DEFAULT 'other'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `car_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `type` enum('text','file') DEFAULT 'text',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`car_id`, `message_id`, `conversation_id`, `sender_id`, `receiver_id`, `message`, `file_path`, `type`, `timestamp`, `is_read`, `created_at`) VALUES
(10, 1, 2, 10, 11, 'asd', NULL, 'text', '2025-10-20 08:04:13', 1, '2025-10-20 16:04:13'),
(10, 2, 2, 11, 10, 'hello', NULL, 'text', '2025-10-20 08:05:31', 1, '2025-10-20 16:05:31'),
(10, 3, 2, 10, 11, 'how much is this car?', NULL, 'text', '2025-10-20 08:05:50', 1, '2025-10-20 16:05:50'),
(10, 4, 2, 11, 10, '9 milyon', NULL, 'text', '2025-10-20 08:05:57', 1, '2025-10-20 16:05:57'),
(10, 5, 2, 11, 10, '🤗', NULL, 'text', '2025-10-20 08:06:01', 1, '2025-10-20 16:06:01'),
(10, 6, 2, 11, 10, 'hello', NULL, 'text', '2025-10-20 08:13:52', 1, '2025-10-20 16:13:52'),
(10, 7, 2, 11, 10, 'bibilhin mo ba?', NULL, 'text', '2025-10-20 08:13:56', 1, '2025-10-20 16:13:56'),
(10, 8, 2, 10, 11, 'ayw ko kol', NULL, 'text', '2025-10-20 08:14:13', 1, '2025-10-20 16:14:13'),
(10, 9, 2, 11, 10, NULL, 'Uploads/chats/chat_68f5f7b83434f_logo (1).png', 'file', '2025-10-20 08:50:00', 1, '2025-10-20 16:50:00'),
(10, 10, 2, 10, 11, 'yoo available', NULL, 'text', '2025-10-20 09:10:17', 1, '2025-10-20 17:10:17'),
(10, 11, 2, 10, 11, '?', NULL, 'text', '2025-10-20 09:10:19', 1, '2025-10-20 17:10:19'),
(10, 12, 2, 11, 10, 'yes migga', NULL, 'text', '2025-10-20 09:10:32', 1, '2025-10-20 17:10:32'),
(10, 13, 2, 11, 10, 'just book ahead', NULL, 'text', '2025-10-20 09:10:36', 1, '2025-10-20 17:10:36'),
(10, 14, 2, 10, 11, 'hi', NULL, 'text', '2025-10-20 10:07:19', 1, '2025-10-20 18:07:19'),
(10, 15, 2, 11, 10, 'testing', NULL, 'text', '2025-10-20 10:15:00', 1, '2025-10-20 18:15:00'),
(10, 16, 2, 11, 10, 'asd', NULL, 'text', '2025-10-20 10:15:22', 1, '2025-10-20 18:15:22'),
(10, 17, 2, 10, 11, 'asd', NULL, 'text', '2025-10-20 10:15:33', 1, '2025-10-20 18:15:33'),
(10, 18, 2, 11, 10, 'asd', NULL, 'text', '2025-10-20 10:17:00', 1, '2025-10-20 18:17:00'),
(10, 19, 2, 10, 11, 'asd123', NULL, 'text', '2025-10-20 10:17:07', 1, '2025-10-20 18:17:07'),
(10, 20, 2, 11, 10, 'asd', NULL, 'text', '2025-10-20 10:20:28', 1, '2025-10-20 18:20:28'),
(10, 21, 2, 11, 10, 'testing nga kung realtime', NULL, 'text', '2025-10-20 10:20:39', 1, '2025-10-20 18:20:39'),
(10, 22, 2, 11, 10, 'testing ulit', NULL, 'text', '2025-10-20 10:20:56', 1, '2025-10-20 18:20:56'),
(10, 23, 2, 11, 10, 'asdasd', NULL, 'text', '2025-10-20 10:35:33', 1, '2025-10-20 18:35:33'),
(10, 24, 2, 11, 10, 'asd', NULL, 'text', '2025-10-20 10:43:26', 1, '2025-10-20 18:43:26'),
(10, 25, 2, 11, 10, '12312', NULL, 'text', '2025-10-20 10:44:31', 1, '2025-10-20 18:44:31'),
(10, 26, 2, 10, 11, '12312awd', NULL, 'text', '2025-10-20 10:49:55', 1, '2025-10-20 18:49:55'),
(10, 27, 2, 11, 10, 'asd', NULL, 'text', '2025-10-20 10:50:09', 1, '2025-10-20 18:50:09'),
(9, 28, 3, 10, 9, 'hey', NULL, 'text', '2025-10-20 10:53:34', 0, '2025-10-20 18:53:34'),
(10, 29, 4, 12, 11, 'hey', NULL, 'text', '2025-10-20 10:55:29', 1, '2025-10-20 18:55:29'),
(10, 30, 4, 12, 11, 'hey', NULL, 'text', '2025-10-20 11:04:45', 1, '2025-10-20 19:04:45'),
(10, 31, 4, 11, 12, 'asd', NULL, 'text', '2025-10-20 11:07:22', 1, '2025-10-20 19:07:22'),
(10, 32, 4, 12, 11, 'asd', NULL, 'text', '2025-10-20 11:07:44', 1, '2025-10-20 19:07:44'),
(10, 33, 4, 12, 11, 'asd', NULL, 'text', '2025-10-20 11:08:23', 1, '2025-10-20 19:08:23'),
(10, 34, 4, 11, 12, 'asd', NULL, 'text', '2025-10-20 11:08:25', 1, '2025-10-20 19:08:25'),
(10, 35, 4, 12, 11, 'asd', NULL, 'text', '2025-10-20 11:08:28', 1, '2025-10-20 19:08:28'),
(10, 36, 4, 11, 12, 'asd', NULL, 'text', '2025-10-20 11:08:31', 1, '2025-10-20 19:08:31'),
(10, 37, 4, 12, 11, 'asd', NULL, 'text', '2025-10-20 11:10:43', 1, '2025-10-20 19:10:43'),
(10, 38, 4, 11, 12, 'asd', NULL, 'text', '2025-10-20 11:17:54', 1, '2025-10-20 19:17:54'),
(10, 39, 4, 11, 12, 'testing vip', NULL, 'text', '2025-10-20 11:18:00', 1, '2025-10-20 19:18:00'),
(10, 40, 4, 12, 11, 'yes?', NULL, 'text', '2025-10-20 11:19:11', 1, '2025-10-20 19:19:11'),
(10, 41, 4, 11, 12, 'testing nga', NULL, 'text', '2025-10-20 11:23:03', 1, '2025-10-20 19:23:03'),
(10, 42, 2, 11, 10, 'hmmm', NULL, 'text', '2025-10-20 11:23:18', 0, '2025-10-20 19:23:18'),
(10, 43, 4, 12, 11, 'asd', NULL, 'text', '2025-10-20 11:30:50', 1, '2025-10-20 19:30:50'),
(10, 44, 2, 11, 10, 'testing nga', NULL, 'text', '2025-10-20 11:35:42', 0, '2025-10-20 19:35:42'),
(10, 45, 4, 11, 12, 'testing nga', NULL, 'text', '2025-10-20 11:35:55', 1, '2025-10-20 19:35:55'),
(10, 46, 4, 11, 12, 'lupit', NULL, 'text', '2025-10-20 11:36:02', 1, '2025-10-20 19:36:02'),
(10, 47, 4, 12, 11, 'gumagana nga', NULL, 'text', '2025-10-20 11:36:10', 1, '2025-10-20 19:36:10'),
(10, 48, 4, 11, 12, 'ayos ah', NULL, 'text', '2025-10-20 11:36:14', 1, '2025-10-20 19:36:14'),
(10, 49, 4, 12, 11, 'asdasd', NULL, 'text', '2025-10-20 11:36:37', 1, '2025-10-20 19:36:37'),
(10, 50, 4, 12, 11, 'asdasd', NULL, 'text', '2025-10-20 11:36:44', 1, '2025-10-20 19:36:44'),
(10, 51, 4, 12, 11, 'lkj', NULL, 'text', '2025-10-20 11:38:16', 1, '2025-10-20 19:38:16'),
(10, 52, 4, 12, 11, 'asdasd', NULL, 'text', '2025-10-20 11:56:40', 1, '2025-10-20 19:56:40'),
(10, 53, 4, 12, 11, 'asdasdasd', NULL, 'text', '2025-10-20 11:56:48', 1, '2025-10-20 19:56:48'),
(10, 54, 4, 12, 11, 'asdasd', NULL, 'text', '2025-10-20 11:57:07', 1, '2025-10-20 19:57:07'),
(10, 55, 4, 12, 11, 'asdasd', NULL, 'text', '2025-10-20 12:00:09', 1, '2025-10-20 20:00:09'),
(10, 56, 4, 12, 11, '123', NULL, 'text', '2025-10-20 12:00:10', 1, '2025-10-20 20:00:10'),
(10, 57, 4, 12, 11, 'test', NULL, 'text', '2025-10-20 12:03:43', 1, '2025-10-20 20:03:43'),
(10, 58, 4, 11, 12, 'asd', NULL, 'text', '2025-10-20 12:05:23', 1, '2025-10-20 20:05:23');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` enum('booking_request','return_due','overdue','maintenance_due','system') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `owners`
--

CREATE TABLE `owners` (
  `owner_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `drivers_license` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `owners`
--

INSERT INTO `owners` (`owner_id`, `user_id`, `drivers_license`, `address`) VALUES
(1, 2, NULL, NULL),
(2, 3, NULL, NULL),
(3, 5, NULL, NULL),
(4, 9, NULL, NULL),
(5, 11, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('credit card','PayPal','other') DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `payment_date`, `payment_method`, `payment_status`) VALUES
(1, 2, '2025-10-20 08:57:36', '', 'completed'),
(2, 3, '2025-10-20 09:09:11', 'credit card', 'completed'),
(3, 4, '2025-10-20 09:10:56', 'PayPal', 'completed'),
(4, 5, '2025-10-20 12:21:21', '', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `promo_id` int(11) NOT NULL,
  `car_id` int(11) DEFAULT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `discount_percentage` decimal(5,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`promo_id`, `car_id`, `promo_code`, `discount_percentage`, `start_date`, `end_date`) VALUES
(1, 6, 'Honda22', 10.00, '2025-09-22', '2025-09-30'),
(2, 6, 'Honda22', 10.00, '2025-09-22', '2025-09-30');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `report_type` enum('car availability','financial report','user activity') DEFAULT NULL,
  `date_generated` timestamp NOT NULL DEFAULT current_timestamp(),
  `content` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `car_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `typingindicators`
--

CREATE TABLE `typingindicators` (
  `user_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `is_typing` tinyint(1) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `typingindicators`
--

INSERT INTO `typingindicators` (`user_id`, `conversation_id`, `is_typing`, `last_updated`) VALUES
(10, 2, 0, '2025-10-20 09:10:20'),
(10, 3, 0, '2025-10-20 10:53:35'),
(11, 2, 0, '2025-10-20 11:08:33'),
(12, 4, 0, '2025-10-20 11:10:46');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin','owner') NOT NULL,
  `contact_info` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `contact_info`) VALUES
(2, 'owner', 'owner@ow.com', '$2y$10$FxlScxHkcIThGtJHdnIZpOabimmaKvnw4vZoaWLvi2XpfqrGYtW2C', 'owner', '123456'),
(3, 'Claire Manjares', 'claire@gmail.com', '$2y$10$WPOLTMJWP00QDhsrzoX65uAgt.qUSZ3dIbWTqv8sP/kpsYKddU4pa', 'owner', '1234567'),
(4, 'Marlon De Torres', 'gids.marlon22@gmail.com', '$2y$10$RuLh473eP5gJOfU0zJsXH.mzQGbXKZ/C2aJWyJxFlTcERXli8Bxl6', 'customer', '123456789'),
(5, 'new', 'nowner@ow.com', '$2y$10$/X2FfEHU7f8hj8W9t8sHn.lLUGykADRli.BCrkEk4l5lFc5lSHuIq', 'owner', '12345'),
(6, 'demo user', 'user@customer.com', '$2y$10$thXflW.xvMeP4uj6xPR3YuBVhfNZYUeoSFIrvuTJH.MIPBXM0tP7y', 'customer', '123454321'),
(7, 'admin', 'admin@admin.com', '$2y$10$UblA9kgHDaxFBRx9efcrDeywhVEWrDxRhIiZyMuBbtyD3epmHERj6', 'admin', '123456789'),
(9, 'demo owner', 'demo@owner.com', '$2y$10$qgLEMiF.jHSfMhFJVuiKfe0a7AApDc32GL43oVwmLPNTqERDmExEC', 'owner', '1234567'),
(10, 'John Russel Pangilinan', 'johnrussel.pangilinan@gmail.com', '$2y$10$1DBeCeQwIdUjS0wRiWU7z.bCBPTGWz3lXgaiMP3rnvd4zUMt8L0S.', 'customer', '09565804855'),
(11, 'Rentify', 'rentifynoreply@gmail.com', '$2y$10$sHEBGTzLodlRbI98ht/lEeimg5yChRv.Uq/TVXjmcyNAH2s3equY2', 'owner', '123213'),
(12, 'VIP', 'john_russel_pangilinan@bec.edu.ph', '$2y$10$/4K.5LYKcnEUi.stlC0Tcu4bWLTnqrel3BBWSF2TR5ZvXlbzUELHm', 'customer', '09565804855');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`car_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`conversation_id`),
  ADD UNIQUE KEY `unique_convo` (`customer_id`,`owner_id`,`car_id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `fk_car_id` (`car_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `owners`
--
ALTER TABLE `owners`
  ADD PRIMARY KEY (`owner_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`promo_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `car_id` (`car_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `typingindicators`
--
ALTER TABLE `typingindicators`
  ADD PRIMARY KEY (`user_id`,`conversation_id`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `car_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `owners`
--
ALTER TABLE `owners`
  MODIFY `owner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`) ON DELETE CASCADE;

--
-- Constraints for table `cars`
--
ALTER TABLE `cars`
  ADD CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`) ON DELETE CASCADE;

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `conversations_ibfk_3` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`);

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_car_id` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`),
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`);

--
-- Constraints for table `owners`
--
ALTER TABLE `owners`
  ADD CONSTRAINT `owners_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `promotions`
--
ALTER TABLE `promotions`
  ADD CONSTRAINT `promotions_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`car_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `typingindicators`
--
ALTER TABLE `typingindicators`
  ADD CONSTRAINT `typingindicators_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `typingindicators_ibfk_2` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
