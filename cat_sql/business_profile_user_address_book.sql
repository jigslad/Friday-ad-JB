-- phpMyAdmin SQL Dump
-- version 4.4.11
-- http://www.phpmyadmin.net
--
-- Host: 192.168.101.6
-- Generation Time: Sep 09, 2015 at 05:57 AM
-- Server version: 10.0.19-MariaDB-log
-- PHP Version: 5.6.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fridayad_migration_0508`
--

--
-- Dumping data for table `user_address_book`
--

INSERT INTO `user_address_book` (`id`, `user_id`, `first_name`, `street_address`, `street_address_2`, `zip`, `phone`, `created_at`, `updated_at`, `is_delivery_address`, `is_invoice_address`, `status`, `town`, `county`, `country`) VALUES
(19, 501094, 'Billy Humphreys', 'London Road', '', 'BN6 9HS', NULL, 1439367573, NULL, NULL, NULL, NULL, 'Hassocks', 'West Sussex', 'United Kingdom'),
(20, -3275034, 'Andrew Brothwell', '201', 'Port Hall Mews', 'BN15PB', NULL, 1439368659, NULL, NULL, NULL, NULL, 'Brighton', 'East Sussex', 'United Kingdom'),
(23, 501128, 'Billy Humphreys', 'London Road', '', 'BN6 9HS', NULL, 1439536257, NULL, NULL, NULL, NULL, 'Hassocks', 'West Sussex', 'United Kingdom'),
(29, 501094, 'Billy Humphreys', '16 Willow Way', '', 'RH20 3BG', NULL, 1441114079, NULL, NULL, NULL, NULL, 'Pulborough', 'West Sussex', 'United Kingdom');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
