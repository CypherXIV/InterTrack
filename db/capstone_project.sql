-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2024 at 01:54 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `capstone_project`
--
CREATE DATABASE IF NOT EXISTS `capstone_project` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `capstone_project`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `faculty_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `faculty_id` (`faculty_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `admins`:
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_registration_codes`
--

DROP TABLE IF EXISTS `admin_registration_codes`;
CREATE TABLE IF NOT EXISTS `admin_registration_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `secret_code` varchar(255) NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `secret_code` (`secret_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `admin_registration_codes`:
--

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

DROP TABLE IF EXISTS `chats`;
CREATE TABLE IF NOT EXISTS `chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `chats`:
--   `user_id`
--       `users` -> `id`
--   `admin_id`
--       `admins` -> `id`
--   `admin_id`
--       `admins` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `log_comments`
--

DROP TABLE IF EXISTS `log_comments`;
CREATE TABLE IF NOT EXISTS `log_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log_entry_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `parent_comment_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_parent_comment` (`parent_comment_id`),
  KEY `idx_log_entry_id` (`log_entry_id`),
  KEY `fk_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `log_comments`:
--   `admin_id`
--       `admins` -> `id`
--   `log_entry_id`
--       `log_entries` -> `id`
--   `parent_comment_id`
--       `log_comments` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `log_entries`
--

DROP TABLE IF EXISTS `log_entries`;
CREATE TABLE IF NOT EXISTS `log_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `hours` decimal(5,2) NOT NULL,
  `description` text DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `log_entries`:
--   `user_id`
--       `users` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `sender_type` enum('user','admin') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_id` (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `messages`:
--   `chat_id`
--       `chats` -> `id`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin') NOT NULL,
  `field` varchar(255) DEFAULT NULL,
  `total_hours` decimal(10,2) DEFAULT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `setup_completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `username_2` (`username`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `student_id_2` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `users`:
--

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chats`
--
ALTER TABLE `chats`
  ADD CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `chats_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`),
  ADD CONSTRAINT `fk_chats_admin_id` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`);

--
-- Constraints for table `log_comments`
--
ALTER TABLE `log_comments`
  ADD CONSTRAINT `fk_admin_id` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`),
  ADD CONSTRAINT `fk_log_comments_log_entry_id` FOREIGN KEY (`log_entry_id`) REFERENCES `log_entries` (`id`),
  ADD CONSTRAINT `fk_parent_comment` FOREIGN KEY (`parent_comment_id`) REFERENCES `log_comments` (`id`);

--
-- Constraints for table `log_entries`
--
ALTER TABLE `log_entries`
  ADD CONSTRAINT `log_entries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `chats` (`id`);
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
