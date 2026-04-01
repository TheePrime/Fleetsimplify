-- phpMyAdmin SQL Dump
-- version 5.2.0
-- Host: 127.0.0.1
-- Generation Time: Mar 24, 2026

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: `roadside_assistance`
CREATE DATABASE IF NOT EXISTS `roadside_assistance` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `roadside_assistance`;

-- --------------------------------------------------------

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','mechanic','admin') NOT NULL DEFAULT 'user',
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `mechanics`
CREATE TABLE `mechanics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `service_location` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `services_offered` text NOT NULL,
  `license_number` varchar(50) NOT NULL UNIQUE,
  `business_name` varchar(255) DEFAULT NULL,
  `operating_hours` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `approval_status` enum('PENDING APPROVAL','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING APPROVAL',
  `availability` enum('AVAILABLE','UNAVAILABLE') NOT NULL DEFAULT 'AVAILABLE',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `admins`
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `requests`
CREATE TABLE `requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `driver_id` int(11) NOT NULL,
  `mechanic_id` int(11) DEFAULT NULL,
  `vehicle_type` varchar(50) NOT NULL,
  `location_lat` decimal(10,8) NOT NULL,
  `location_lng` decimal(11,8) NOT NULL,
  `location_address` varchar(255) DEFAULT NULL,
  `problem_description` text NOT NULL,
  `status` enum('Pending','Accepted','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`driver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `messages`
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_role` enum('user','mechanic') NOT NULL DEFAULT 'user',
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `timestamp` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `ratings`
CREATE TABLE `ratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `mechanic_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `feedback` text DEFAULT NULL,
  `repair_time_minutes` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`driver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `reports_data`
CREATE TABLE `reports_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_type` varchar(100) NOT NULL,
  `data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data_json`)),
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

-- Insert default Admin
-- password is 'password123' -> $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `phone`) VALUES
('Super Admin', 'admin@roadside.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '1234567890');
SET @admin_user_id = LAST_INSERT_ID();
INSERT INTO `admins` (`user_id`) VALUES (@admin_user_id);

-- Insert 20 Users (Drivers)
-- password is 'password123'
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `phone`) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223330'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223331'),
('Alice Johnson', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223332'),
('Bob Brown', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223333'),
('Charlie Davis', 'charlie@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223334'),
('Diana Evans', 'diana@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223335'),
('Eve Foster', 'eve@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223336'),
('Frank Green', 'frank@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223337'),
('Grace Harris', 'grace@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223338'),
('Henry Ives', 'henry@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223339'),
('Isabella Jones', 'isabella@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223340'),
('Jack King', 'jack@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223341'),
('Karen Lee', 'karen@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223342'),
('Liam Moore', 'liam@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223343'),
('Mia Nelson', 'mia@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223344'),
('Noah Owen', 'noah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223345'),
('Olivia Perez', 'olivia@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223346'),
('Paul Quinn', 'paul@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223347'),
('Quincy Roberts', 'quincy@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223348'),
('Rachel Scott', 'rachel@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', '1112223349');

-- Insert 20 Mechanics (Users)
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `phone`) VALUES
('Mike The Mechanic', 'mike@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112220'),
('Sarah Wrench', 'sarah@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112221'),
('Tom Fixit', 'tom@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112222'),
('Jerry Tow', 'jerry@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112223'),
('Kim Garage', 'kim@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112224'),
('Larry Tires', 'larry@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112225'),
('Moe Batteries', 'moe@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112226'),
('Ned Engines', 'ned@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112227'),
('Oscar Keys', 'oscar@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112228'),
('Peter Pump', 'peter@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112229'),
('Quinn Repair', 'quinn@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112230'),
('Ron Service', 'ron@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112231'),
('Steve Brakes', 'steve@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112232'),
('Tim Exhaust', 'tim@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112233'),
('Ulysses Oil', 'ulysses@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112234'),
('Victor Lube', 'victor@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112235'),
('Will Jump', 'will@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112236'),
('Xavier Tech', 'xavier@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112237'),
('Yuri Gas', 'yuri@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112238'),
('Zane Wheel', 'zane@mech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mechanic', '5551112239');

-- Insert 20 Mechanics details
INSERT INTO `mechanics` (`user_id`, `service_location`, `latitude`, `longitude`, `services_offered`, `license_number`, `approval_status`, `availability`) VALUES
(22, 'Downtown', 40.7128, -74.0060, 'Towing, Battery Jump, Flat Tire', 'LIC001', 'APPROVED', 'AVAILABLE'),
(23, 'Uptown', 40.7300, -73.9900, 'Towing, Engine Repair', 'LIC002', 'APPROVED', 'AVAILABLE'),
(24, 'Westside', 40.7400, -74.0100, 'Flat Tire, Fuel Delivery', 'LIC003', 'APPROVED', 'AVAILABLE'),
(25, 'Eastside', 40.7500, -73.9800, 'Lockout, Battery Jump', 'LIC004', 'PENDING APPROVAL', 'AVAILABLE'),
(26, 'Northside', 40.7600, -74.0000, 'All Services', 'LIC005', 'APPROVED', 'AVAILABLE'),
(27, 'Southside', 40.7100, -74.0200, 'Towing, Engine Repair', 'LIC006', 'REJECTED', 'AVAILABLE'),
(28, 'Midtown', 40.7500, -74.0000, 'Battery Jump, Fuel Delivery', 'LIC007', 'APPROVED', 'UNAVAILABLE'),
(29, 'Bronx', 40.8200, -73.9000, 'Towing', 'LIC008', 'PENDING APPROVAL', 'AVAILABLE'),
(30, 'Brooklyn', 40.6500, -73.9500, 'Flat Tire, Lockout', 'LIC009', 'APPROVED', 'AVAILABLE'),
(31, 'Queens', 40.7000, -73.8000, 'Battery Jump, Flat Tire', 'LIC010', 'APPROVED', 'AVAILABLE'),
(32, 'Staten Island', 40.5800, -74.1500, 'Towing, Engine Repair', 'LIC011', 'APPROVED', 'AVAILABLE'),
(33, 'Jersey City', 40.7200, -74.0500, 'Lockout, Fuel Delivery', 'LIC012', 'PENDING APPROVAL', 'AVAILABLE'),
(34, 'Hoboken', 40.7400, -74.0300, 'All Services', 'LIC013', 'APPROVED', 'AVAILABLE'),
(35, 'Weehawken', 40.7600, -74.0200, 'Towing', 'LIC014', 'REJECTED', 'AVAILABLE'),
(36, 'Union City', 40.7700, -74.0300, 'Battery Jump', 'LIC015', 'APPROVED', 'UNAVAILABLE'),
(37, 'Newark', 40.7300, -74.1700, 'Flat Tire, Fuel Delivery', 'LIC016', 'APPROVED', 'AVAILABLE'),
(38, 'Elizabeth', 40.6600, -74.2100, 'Lockout', 'LIC017', 'APPROVED', 'AVAILABLE'),
(39, 'Bayonne', 40.6600, -74.1100, 'All Services', 'LIC018', 'PENDING APPROVAL', 'AVAILABLE'),
(40, 'Kearny', 40.7600, -74.1400, 'Towing, Engine Repair', 'LIC019', 'APPROVED', 'AVAILABLE'),
(41, 'Harrison', 40.7400, -74.1500, 'Battery Jump, Flat Tire', 'LIC020', 'APPROVED', 'AVAILABLE');

-- Insert som dummy requests
INSERT INTO `requests` (`driver_id`, `mechanic_id`, `vehicle_type`, `location_lat`, `location_lng`, `location_address`, `problem_description`, `status`) VALUES
(2, 1, 'Sedan', 40.7130, -74.0065, 'Main St', 'Flat tire on front left.', 'Completed'),
(3, 2, 'SUV', 40.7310, -73.9910, '2nd Ave', 'Car won’t start, battery dead.', 'Completed'),
(4, NULL, 'Truck', 40.7410, -74.0110, '8th Ave', 'Engine overheating.', 'Pending'),
(5, 3, 'Motorcycle', 40.7420, -74.0120, '10th Ave', 'Out of gas.', 'In Progress');

COMMIT;
