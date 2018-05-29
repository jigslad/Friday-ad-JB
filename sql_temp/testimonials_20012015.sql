-- phpMyAdmin SQL Dump
-- version 3.5.2
-- http://www.phpmyadmin.net
--
-- Host: 192.168.100.216
-- Generation Time: Jan 20, 2015 at 12:22 PM
-- Server version: 5.5.39-MariaDB
-- PHP Version: 5.3.3

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE testimonials;

SET FOREIGN_KEY_CHECKS = 1;

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `fridayad_all`
--

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `user_id`, `user_name`, `user_email`, `status`, `created_at`, `updated_at`, `comment`) VALUES
(1, 87820, 'chris lucas', 'lucaschris1@live.com', 0, 1421736706, NULL, 'My first testimonials, My first testimonials, My first testimonials, My first testimonials My first testimonials'),
(2, 87821, 'Michael Palframan', 'michaelpalframan@btinternet.com', 0, 1421736706, NULL, 'My second testimonials, My second testimonials, My second testimonials, My second testimonials My second testimonials'),
(3, 87822, 'Denise Norton', 'Merged_DeniseNrtn@aol.com', 0, 1421736706, NULL, 'My testimonials, My testimonials, My testimonials, My testimonials My testimonials'),
(4, 87823, ' ', 'MarkCoulterZZW@hotmail.co.uk', 0, 1421736706, NULL, 'My testimonials, My testimonials, My testimonials, My testimonials My testimonials');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
