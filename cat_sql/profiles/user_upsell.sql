-- phpMyAdmin SQL Dump
-- version 4.4.11
-- http://www.phpmyadmin.net
--
-- Host: 192.168.101.6
-- Generation Time: Sep 19, 2015 at 12:17 PM
-- Server version: 10.0.19-MariaDB-log
-- PHP Version: 5.6.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fridayad_migration_0409`
--

--
-- Dumping data for table `user_upsell`
--

INSERT INTO `user_upsell` (`user_id`, `package_id`, `upsell_id`, `created_at`, `updated_at`, `status`) VALUES
(501138, 27, 25, 1439542861, NULL, NULL),
(501138, 27, 26, 1439542861, NULL, NULL),
(501138, 27, 29, 1439542861, NULL, NULL),
(501138, 27, 33, 1439542861, NULL, NULL),
(501138, 27, 36, 1439542861, NULL, NULL),
(-3275034, 26, 25, 1442564291, NULL, NULL),
(-3275034, 26, 26, 1442564291, NULL, NULL),
(-3275034, 26, 27, 1442564291, NULL, NULL),
(-1108109, 27, 25, 1440494463, NULL, NULL),
(-1108109, 27, 26, 1440494463, NULL, NULL),
(-1108109, 27, 29, 1440494463, NULL, NULL),
(-1108109, 27, 33, 1440494463, NULL, NULL),
(-1108109, 27, 36, 1440494463, NULL, NULL),
(501211, 27, 25, 1440062893, NULL, NULL),
(501211, 27, 26, 1440062893, NULL, NULL),
(501211, 27, 29, 1440062893, NULL, NULL),
(501211, 27, 33, 1440062893, NULL, NULL),
(501211, 27, 36, 1440062893, NULL, NULL),
(501219, 24, 25, 1440075379, NULL, NULL),
(501219, 24, 26, 1440075379, NULL, NULL),
(501219, 24, 30, 1440075379, NULL, NULL),
(501219, 24, 34, 1440075379, NULL, NULL),
(501219, 24, 35, 1440075379, NULL, NULL),
(501219, 24, 36, 1440075379, NULL, NULL),
(501181, 27, 25, 1439887443, NULL, NULL),
(501181, 27, 26, 1439887443, NULL, NULL),
(501181, 27, 29, 1439887443, NULL, NULL),
(501181, 27, 33, 1439887443, NULL, NULL),
(501181, 27, 36, 1439887443, NULL, NULL),
(501117, 27, 25, 1439456569, NULL, NULL),
(501117, 27, 26, 1439456570, NULL, NULL),
(501117, 27, 29, 1439456570, NULL, NULL),
(501117, 27, 33, 1439456570, NULL, NULL),
(501117, 27, 36, 1439456570, NULL, NULL),
(501213, 27, 25, 1440063794, NULL, NULL),
(501213, 27, 26, 1440063794, NULL, NULL),
(501213, 27, 29, 1440063794, NULL, NULL),
(501213, 27, 33, 1440063794, NULL, NULL),
(501213, 27, 36, 1440063794, NULL, NULL),
(501133, 27, 25, 1439541237, NULL, NULL),
(501133, 27, 26, 1439541237, NULL, NULL),
(501133, 27, 29, 1439541237, NULL, NULL),
(501133, 27, 33, 1439541237, NULL, NULL),
(501133, 27, 36, 1439541237, NULL, NULL),
(501152, 27, 25, 1439799676, NULL, NULL),
(501152, 27, 26, 1439799676, NULL, NULL),
(501152, 27, 29, 1439799676, NULL, NULL),
(501152, 27, 33, 1439799676, NULL, NULL),
(501152, 27, 36, 1439799676, NULL, NULL),
(501095, 27, 25, 1440147514, NULL, NULL),
(501095, 27, 26, 1440147514, NULL, NULL),
(501095, 27, 29, 1440147514, NULL, NULL),
(501095, 27, 33, 1440147514, NULL, NULL),
(501095, 27, 36, 1440147514, NULL, NULL),
(501159, 27, 25, 1439801117, NULL, NULL),
(501159, 27, 26, 1439801117, NULL, NULL),
(501159, 27, 29, 1439801117, NULL, NULL),
(501159, 27, 33, 1439801117, NULL, NULL),
(501159, 27, 36, 1439801117, NULL, NULL),
(501163, 27, 25, 1439802492, NULL, NULL),
(501163, 27, 26, 1439802492, NULL, NULL),
(501163, 27, 29, 1439802492, NULL, NULL),
(501163, 27, 33, 1439802492, NULL, NULL),
(501163, 27, 36, 1439802492, NULL, NULL),
(501214, 27, 25, 1440064027, NULL, NULL),
(501214, 27, 26, 1440064027, NULL, NULL),
(501214, 27, 29, 1440064027, NULL, NULL),
(501214, 27, 33, 1440064027, NULL, NULL),
(501214, 27, 36, 1440064027, NULL, NULL),
(501102, 27, 25, 1439368882, NULL, NULL),
(501102, 27, 26, 1439368882, NULL, NULL),
(501102, 27, 29, 1439368882, NULL, NULL),
(501102, 27, 33, 1439368882, NULL, NULL),
(501102, 27, 36, 1439368882, NULL, NULL),
(501158, 27, 25, 1440663561, NULL, NULL),
(501158, 27, 26, 1440663561, NULL, NULL),
(501158, 27, 29, 1440663561, NULL, NULL),
(501158, 27, 33, 1440663561, NULL, NULL),
(501158, 27, 36, 1440663561, NULL, NULL),
(501067, 27, 25, 1440063465, NULL, NULL),
(501067, 27, 26, 1440063465, NULL, NULL),
(501067, 27, 29, 1440063465, NULL, NULL),
(501067, 27, 33, 1440063465, NULL, NULL),
(501067, 27, 36, 1440063465, NULL, NULL),
(502423, 24, 25, 1440594144, NULL, NULL),
(502423, 24, 26, 1440594144, NULL, NULL),
(502423, 24, 30, 1440594144, NULL, NULL),
(502423, 24, 34, 1440594144, NULL, NULL),
(502423, 24, 35, 1440594144, NULL, NULL),
(502423, 24, 36, 1440594144, NULL, NULL),
(501156, 27, 25, 1439800249, NULL, NULL),
(501156, 27, 26, 1439800249, NULL, NULL),
(501156, 27, 29, 1439800249, NULL, NULL),
(501156, 27, 33, 1439800249, NULL, NULL),
(501156, 27, 36, 1439800249, NULL, NULL),
(-3250906, 23, 25, 1440407115, NULL, NULL),
(-3250906, 23, 26, 1440407115, NULL, NULL),
(-3250906, 23, 27, 1440407115, NULL, NULL),
(-2216440, 23, 25, 1440407149, NULL, NULL),
(-2216440, 23, 26, 1440407149, NULL, NULL),
(-2216440, 23, 27, 1440407149, NULL, NULL),
(501193, 24, 25, 1439976149, NULL, NULL),
(501193, 24, 26, 1439976149, NULL, NULL),
(501193, 24, 30, 1439976149, NULL, NULL),
(501193, 24, 34, 1439976149, NULL, NULL),
(501193, 24, 35, 1439976149, NULL, NULL),
(501193, 24, 36, 1439976149, NULL, NULL),
(501202, 27, 25, 1440059556, NULL, NULL),
(501202, 27, 26, 1440059556, NULL, NULL),
(501202, 27, 29, 1440059556, NULL, NULL),
(501202, 27, 33, 1440059556, NULL, NULL),
(501202, 27, 36, 1440059556, NULL, NULL),
(502430, 24, 25, 1440687937, NULL, NULL),
(502430, 24, 26, 1440687938, NULL, NULL),
(502430, 24, 30, 1440687938, NULL, NULL),
(502430, 24, 34, 1440687938, NULL, NULL),
(502430, 24, 35, 1440687938, NULL, NULL),
(502430, 24, 36, 1440687938, NULL, NULL),
(501161, 27, 25, 1441614532, NULL, NULL),
(501161, 27, 26, 1441614532, NULL, NULL),
(501161, 27, 29, 1441614532, NULL, NULL),
(501161, 27, 33, 1441614532, NULL, NULL),
(501161, 27, 36, 1441614532, NULL, NULL),
(501204, 27, 25, 1440060396, NULL, NULL),
(501204, 27, 26, 1440060396, NULL, NULL),
(501204, 27, 29, 1440060396, NULL, NULL),
(501204, 27, 33, 1440060396, NULL, NULL),
(501204, 27, 36, 1440060396, NULL, NULL),
(501166, 27, 25, 1441269401, NULL, NULL),
(501166, 27, 26, 1441269401, NULL, NULL),
(501166, 27, 29, 1441269401, NULL, NULL),
(501166, 27, 33, 1441269401, NULL, NULL),
(501166, 27, 36, 1441269401, NULL, NULL),
(-881648, 23, 25, 1440407205, NULL, NULL),
(-881648, 23, 26, 1440407205, NULL, NULL),
(-881648, 23, 27, 1440407205, NULL, NULL),
(501100, 27, 25, 1439368465, NULL, NULL),
(501100, 27, 26, 1439368465, NULL, NULL),
(501100, 27, 29, 1439368465, NULL, NULL),
(501100, 27, 33, 1439368465, NULL, NULL),
(501100, 27, 36, 1439368465, NULL, NULL),
(503427, 24, 25, 1441100899, NULL, NULL),
(503427, 24, 26, 1441100899, NULL, NULL),
(503427, 24, 30, 1441100899, NULL, NULL),
(503427, 24, 34, 1441100899, NULL, NULL),
(503427, 24, 35, 1441100899, NULL, NULL),
(503427, 24, 36, 1441100899, NULL, NULL),
(501155, 27, 25, 1441269449, NULL, NULL),
(501155, 27, 26, 1441269450, NULL, NULL),
(501155, 27, 29, 1441269450, NULL, NULL),
(501155, 27, 33, 1441269450, NULL, NULL),
(501155, 27, 36, 1441269450, NULL, NULL),
(501099, 27, 25, 1439369514, NULL, NULL),
(501099, 27, 26, 1439369514, NULL, NULL),
(501099, 27, 29, 1439369514, NULL, NULL),
(501099, 27, 33, 1439369514, NULL, NULL),
(501099, 27, 36, 1439369514, NULL, NULL),
(501177, 27, 25, 1439886072, NULL, NULL),
(501177, 27, 26, 1439886072, NULL, NULL),
(501177, 27, 29, 1439886072, NULL, NULL),
(501177, 27, 33, 1439886072, NULL, NULL),
(501177, 27, 36, 1439886072, NULL, NULL),
(-565743, 29, 25, 1440407218, NULL, NULL),
(-565743, 29, 26, 1440407218, NULL, NULL),
(-565743, 29, 27, 1440407218, NULL, NULL),
(502265, 29, 25, 1440407485, NULL, NULL),
(502265, 29, 26, 1440407485, NULL, NULL),
(502265, 29, 27, 1440407486, NULL, NULL),
(502083, 29, 25, 1440407293, NULL, NULL),
(502083, 29, 26, 1440407293, NULL, NULL),
(502083, 29, 27, 1440407293, NULL, NULL),
(501118, 27, 25, 1439456662, NULL, NULL),
(501118, 27, 26, 1439456662, NULL, NULL),
(501118, 27, 29, 1439456662, NULL, NULL),
(501118, 27, 33, 1439456662, NULL, NULL),
(501118, 27, 36, 1439456662, NULL, NULL),
(504490, 24, 25, 1442480069, NULL, NULL),
(504490, 24, 26, 1442480069, NULL, NULL),
(504490, 24, 30, 1442480069, NULL, NULL),
(504490, 24, 34, 1442480069, NULL, NULL),
(504490, 24, 35, 1442480069, NULL, NULL),
(504490, 24, 36, 1442480069, NULL, NULL),
(501238, 24, 25, 1440575567, NULL, NULL),
(501238, 24, 26, 1440575567, NULL, NULL),
(501238, 24, 30, 1440575567, NULL, NULL),
(501238, 24, 34, 1440575567, NULL, NULL),
(501238, 24, 35, 1440575567, NULL, NULL),
(501238, 24, 36, 1440575567, NULL, NULL),
(501137, 27, 25, 1439542474, NULL, NULL),
(501137, 27, 26, 1439542474, NULL, NULL),
(501137, 27, 29, 1439542474, NULL, NULL),
(501137, 27, 33, 1439542474, NULL, NULL),
(501137, 27, 36, 1439542474, NULL, NULL),
(501201, 27, 25, 1440059303, NULL, NULL),
(501201, 27, 26, 1440059303, NULL, NULL),
(501201, 27, 29, 1440059303, NULL, NULL),
(501201, 27, 33, 1440059303, NULL, NULL),
(501201, 27, 36, 1440059303, NULL, NULL),
(501182, 27, 25, 1439887876, NULL, NULL),
(501182, 27, 26, 1439887876, NULL, NULL),
(501182, 27, 29, 1439887876, NULL, NULL),
(501182, 27, 33, 1439887876, NULL, NULL),
(501182, 27, 36, 1439887876, NULL, NULL),
(-2926734, 29, 25, 1440407129, NULL, NULL),
(-2926734, 29, 26, 1440407130, NULL, NULL),
(-2926734, 29, 27, 1440407130, NULL, NULL),
(501134, 27, 25, 1439541393, NULL, NULL),
(501134, 27, 26, 1439541393, NULL, NULL),
(501134, 27, 29, 1439541393, NULL, NULL),
(501134, 27, 33, 1439541393, NULL, NULL),
(501134, 27, 36, 1439541393, NULL, NULL),
(501186, 27, 25, 1439889595, NULL, NULL),
(501186, 27, 26, 1439889595, NULL, NULL),
(501186, 27, 29, 1439889595, NULL, NULL),
(501186, 27, 33, 1439889595, NULL, NULL),
(501186, 27, 36, 1439889595, NULL, NULL),
(502082, 26, 25, 1440407292, NULL, NULL),
(502082, 26, 26, 1440407292, NULL, NULL),
(502082, 26, 27, 1440407292, NULL, NULL),
(-1705731, 26, 25, 1440407184, NULL, NULL),
(-1705731, 26, 26, 1440407184, NULL, NULL),
(-1705731, 26, 27, 1440407184, NULL, NULL),
(501206, 27, 25, 1440061231, NULL, NULL),
(501206, 27, 26, 1440061231, NULL, NULL),
(501206, 27, 29, 1440061231, NULL, NULL),
(501206, 27, 33, 1440061231, NULL, NULL),
(501206, 27, 36, 1440061231, NULL, NULL),
(501069, 27, 25, 1440063159, NULL, NULL),
(501069, 27, 26, 1440063159, NULL, NULL),
(501069, 27, 29, 1440063159, NULL, NULL),
(501069, 27, 33, 1440063159, NULL, NULL),
(501069, 27, 36, 1440063159, NULL, NULL),
(501180, 27, 25, 1439887324, NULL, NULL),
(501180, 27, 26, 1439887324, NULL, NULL),
(501180, 27, 29, 1439887324, NULL, NULL),
(501180, 27, 33, 1439887324, NULL, NULL),
(501180, 27, 36, 1439887324, NULL, NULL),
(501070, 27, 25, 1440063110, NULL, NULL),
(501070, 27, 26, 1440063110, NULL, NULL),
(501070, 27, 29, 1440063110, NULL, NULL),
(501070, 27, 33, 1440063110, NULL, NULL),
(501070, 27, 36, 1440063110, NULL, NULL),
(504112, 29, 25, 1441693293, NULL, NULL),
(504112, 29, 26, 1441693293, NULL, NULL),
(504112, 29, 27, 1441693294, NULL, NULL),
(502244, 38, 25, 1440407464, NULL, NULL),
(502244, 38, 26, 1440407464, NULL, NULL),
(502244, 38, 27, 1440407465, NULL, NULL),
(-969930, 26, 25, 1440407197, NULL, NULL),
(-969930, 26, 26, 1440407197, NULL, NULL),
(-969930, 26, 27, 1440407198, NULL, NULL),
(501109, 27, 25, 1440069745, NULL, NULL),
(501109, 27, 26, 1440069745, NULL, NULL),
(501109, 27, 29, 1440069745, NULL, NULL),
(501109, 27, 33, 1440069745, NULL, NULL),
(501109, 27, 36, 1440069745, NULL, NULL),
(501131, 27, 25, 1439540281, NULL, NULL),
(501131, 27, 26, 1439540281, NULL, NULL),
(501131, 27, 29, 1439540281, NULL, NULL),
(501131, 27, 33, 1439540281, NULL, NULL),
(501131, 27, 36, 1439540281, NULL, NULL),
(502048, 32, 25, 1440407260, NULL, NULL),
(502048, 32, 26, 1440407260, NULL, NULL),
(502048, 32, 27, 1440407260, NULL, NULL),
(502438, 24, 25, 1440752490, NULL, NULL),
(502438, 24, 26, 1440752490, NULL, NULL),
(502438, 24, 30, 1440752490, NULL, NULL),
(502438, 24, 34, 1440752490, NULL, NULL),
(502438, 24, 35, 1440752490, NULL, NULL),
(502438, 24, 36, 1440752490, NULL, NULL),
(-3301735, 29, 25, 1440407106, NULL, NULL),
(-3301735, 29, 26, 1440407106, NULL, NULL),
(-3301735, 29, 27, 1440407106, NULL, NULL),
(501179, 27, 25, 1439886899, NULL, NULL),
(501179, 27, 26, 1439886899, NULL, NULL),
(501179, 27, 29, 1439886899, NULL, NULL),
(501179, 27, 33, 1439886899, NULL, NULL),
(501179, 27, 36, 1439886899, NULL, NULL),
(501185, 27, 25, 1439889041, NULL, NULL),
(501185, 27, 26, 1439889041, NULL, NULL),
(501185, 27, 29, 1439889041, NULL, NULL),
(501185, 27, 33, 1439889041, NULL, NULL),
(501185, 27, 36, 1439889041, NULL, NULL),
(501130, 27, 25, 1439539905, NULL, NULL),
(501130, 27, 26, 1439539905, NULL, NULL),
(501130, 27, 29, 1439539905, NULL, NULL),
(501130, 27, 33, 1439539905, NULL, NULL),
(501130, 27, 36, 1439539905, NULL, NULL),
(501209, 27, 25, 1440062454, NULL, NULL),
(501209, 27, 26, 1440062454, NULL, NULL),
(501209, 27, 29, 1440062454, NULL, NULL),
(501209, 27, 33, 1440062454, NULL, NULL),
(501209, 27, 36, 1440062454, NULL, NULL),
(501068, 27, 25, 1440063441, NULL, NULL),
(501068, 27, 26, 1440063441, NULL, NULL),
(501068, 27, 29, 1440063441, NULL, NULL),
(501068, 27, 33, 1440063441, NULL, NULL),
(501068, 27, 36, 1440063441, NULL, NULL),
(502179, 41, 25, 1440407399, NULL, NULL),
(502179, 41, 26, 1440407399, NULL, NULL),
(502179, 41, 27, 1440407399, NULL, NULL),
(502268, 41, 25, 1440407490, NULL, NULL),
(502268, 41, 26, 1440407490, NULL, NULL),
(502268, 41, 27, 1440407490, NULL, NULL),
(502276, 41, 25, 1440407496, NULL, NULL),
(502276, 41, 26, 1440407496, NULL, NULL),
(502276, 41, 27, 1440407496, NULL, NULL),
(502277, 41, 25, 1440407498, NULL, NULL),
(502277, 41, 26, 1440407498, NULL, NULL),
(502277, 41, 27, 1440407498, NULL, NULL),
(502278, 26, 25, 1440407499, NULL, NULL),
(502278, 26, 26, 1440407499, NULL, NULL),
(502278, 26, 27, 1440407499, NULL, NULL),
(502279, 41, 25, 1440407501, NULL, NULL),
(502279, 41, 26, 1440407501, NULL, NULL),
(502279, 41, 27, 1440407501, NULL, NULL),
(502298, 38, 25, 1440407523, NULL, NULL),
(502298, 38, 26, 1440407523, NULL, NULL),
(502298, 38, 27, 1440407523, NULL, NULL),
(502302, 29, 25, 1440407528, NULL, NULL),
(502302, 29, 26, 1440407528, NULL, NULL),
(502302, 29, 27, 1440407528, NULL, NULL),
(502303, 44, 25, 1440407529, NULL, NULL),
(502303, 44, 26, 1440407530, NULL, NULL),
(502303, 44, 27, 1440407530, NULL, NULL),
(502305, 44, 25, 1440407531, NULL, NULL),
(502305, 44, 26, 1440407531, NULL, NULL),
(502305, 44, 27, 1440407532, NULL, NULL),
(502307, 44, 25, 1440407533, NULL, NULL),
(502307, 44, 26, 1440407533, NULL, NULL),
(502307, 44, 27, 1440407533, NULL, NULL),
(502309, 44, 25, 1440407536, NULL, NULL),
(502309, 44, 26, 1440407536, NULL, NULL),
(502309, 44, 27, 1440407537, NULL, NULL),
(502310, 41, 25, 1440407538, NULL, NULL),
(502310, 41, 26, 1440407538, NULL, NULL),
(502310, 41, 27, 1440407538, NULL, NULL),
(502325, 41, 25, 1440407541, NULL, NULL),
(502325, 41, 26, 1440407541, NULL, NULL),
(502325, 41, 27, 1440407542, NULL, NULL),
(502327, 38, 25, 1440407543, NULL, NULL),
(502327, 38, 26, 1440407543, NULL, NULL),
(502327, 38, 27, 1440407543, NULL, NULL),
(502328, 38, 25, 1440407544, NULL, NULL),
(502328, 38, 26, 1440407545, NULL, NULL),
(502328, 38, 27, 1440407545, NULL, NULL),
(502331, 26, 25, 1440407549, NULL, NULL),
(502331, 26, 26, 1440407549, NULL, NULL),
(502331, 26, 27, 1440407550, NULL, NULL),
(502338, 41, 25, 1440407554, NULL, NULL),
(502338, 41, 26, 1440407554, NULL, NULL),
(502338, 41, 27, 1440407554, NULL, NULL),
(502339, 41, 25, 1440407556, NULL, NULL),
(502339, 41, 26, 1440407556, NULL, NULL),
(502339, 41, 27, 1440407556, NULL, NULL),
(502340, 44, 25, 1440407557, NULL, NULL),
(502340, 44, 26, 1440407558, NULL, NULL),
(502340, 44, 27, 1440407558, NULL, NULL),
(502344, 41, 25, 1440407561, NULL, NULL),
(502344, 41, 26, 1440407561, NULL, NULL),
(502344, 41, 27, 1440407561, NULL, NULL),
(502345, 41, 25, 1440407562, NULL, NULL),
(502345, 41, 26, 1440407562, NULL, NULL),
(502345, 41, 27, 1440407563, NULL, NULL),
(502348, 29, 25, 1440407564, NULL, NULL),
(502348, 29, 26, 1440407564, NULL, NULL),
(502348, 29, 27, 1440407564, NULL, NULL),
(502353, 44, 25, 1440407567, NULL, NULL),
(502353, 44, 26, 1440407567, NULL, NULL),
(502353, 44, 27, 1440407568, NULL, NULL),
(502363, 44, 25, 1440407574, NULL, NULL),
(502363, 44, 26, 1440407574, NULL, NULL),
(502363, 44, 27, 1440407574, NULL, NULL),
(502366, 29, 25, 1440407577, NULL, NULL),
(502366, 29, 26, 1440407577, NULL, NULL),
(502366, 29, 27, 1440407577, NULL, NULL),
(502378, 41, 25, 1440407584, NULL, NULL),
(502378, 41, 26, 1440407584, NULL, NULL),
(502378, 41, 27, 1440407584, NULL, NULL),
(502380, 35, 25, 1440407586, NULL, NULL),
(502380, 35, 26, 1440407586, NULL, NULL),
(502380, 35, 27, 1440407586, NULL, NULL),
(502381, 35, 25, 1440407587, NULL, NULL),
(502381, 35, 26, 1440407588, NULL, NULL),
(502381, 35, 27, 1440407588, NULL, NULL),
(502406, 29, 25, 1440407597, NULL, NULL),
(502406, 29, 26, 1440407597, NULL, NULL),
(502406, 29, 27, 1440407598, NULL, NULL),
(502407, 26, 25, 1440407600, NULL, NULL),
(502407, 26, 26, 1440407600, NULL, NULL),
(502407, 26, 27, 1440407600, NULL, NULL),
(502408, 41, 25, 1440407602, NULL, NULL),
(502408, 41, 26, 1440407602, NULL, NULL),
(502408, 41, 27, 1440407602, NULL, NULL),
(502410, 41, 25, 1440407606, NULL, NULL),
(502410, 41, 26, 1440407606, NULL, NULL),
(502410, 41, 27, 1440407606, NULL, NULL),
(504346, 41, 25, 1441693619, NULL, NULL),
(504346, 41, 26, 1441693619, NULL, NULL),
(504346, 41, 27, 1441693620, NULL, NULL),
(504428, 29, 25, 1441693692, NULL, NULL),
(504428, 29, 26, 1441693692, NULL, NULL),
(504428, 29, 27, 1441693692, NULL, NULL),
(504429, 41, 25, 1441693694, NULL, NULL),
(504429, 41, 26, 1441693694, NULL, NULL),
(504429, 41, 27, 1441693695, NULL, NULL),
(504430, 41, 25, 1441693696, NULL, NULL),
(504430, 41, 26, 1441693696, NULL, NULL),
(504430, 41, 27, 1441693697, NULL, NULL),
(504491, 24, 25, 1442480452, NULL, NULL),
(504491, 24, 26, 1442480452, NULL, NULL),
(504491, 24, 30, 1442480452, NULL, NULL),
(504491, 24, 34, 1442480452, NULL, NULL),
(504491, 24, 35, 1442480452, NULL, NULL),
(504491, 24, 36, 1442480452, NULL, NULL),
(501194, 39, 25, 1439977504, NULL, NULL),
(501194, 39, 26, 1439977504, NULL, NULL),
(501194, 39, 28, 1439977504, NULL, NULL),
(501194, 39, 32, 1439977504, NULL, NULL),
(501194, 39, 36, 1439977504, NULL, NULL),
(501113, 27, 25, 1439455708, NULL, NULL),
(501113, 27, 26, 1439455708, NULL, NULL),
(501113, 27, 29, 1439455708, NULL, NULL),
(501113, 27, 33, 1439455708, NULL, NULL),
(501113, 27, 36, 1439455708, NULL, NULL),
(501073, 27, 25, 1439219193, NULL, NULL),
(501073, 27, 26, 1439219193, NULL, NULL),
(501073, 27, 29, 1439219193, NULL, NULL),
(501073, 27, 33, 1439219193, NULL, NULL),
(501073, 27, 36, 1439219193, NULL, NULL),
(501217, 27, 25, 1440068162, NULL, NULL),
(501217, 27, 26, 1440068162, NULL, NULL),
(501217, 27, 29, 1440068162, NULL, NULL),
(501217, 27, 33, 1440068162, NULL, NULL),
(501217, 27, 36, 1440068162, NULL, NULL),
(501114, 27, 25, 1439455788, NULL, NULL),
(501114, 27, 26, 1439455788, NULL, NULL),
(501114, 27, 29, 1439455788, NULL, NULL),
(501114, 27, 33, 1439455788, NULL, NULL),
(501114, 27, 36, 1439455788, NULL, NULL),
(501043, 27, 25, 1439368096, NULL, NULL),
(501043, 27, 26, 1439368096, NULL, NULL),
(501043, 27, 29, 1439368096, NULL, NULL),
(501043, 27, 33, 1439368096, NULL, NULL),
(501043, 27, 36, 1439368096, NULL, NULL),
(501150, 27, 25, 1439799169, NULL, NULL),
(501150, 27, 26, 1439799169, NULL, NULL),
(501150, 27, 29, 1439799169, NULL, NULL),
(501150, 27, 33, 1439799169, NULL, NULL),
(501150, 27, 36, 1439799169, NULL, NULL),
(501110, 27, 25, 1439454504, NULL, NULL),
(501110, 27, 26, 1439454504, NULL, NULL),
(501110, 27, 29, 1439454504, NULL, NULL),
(501110, 27, 33, 1439454504, NULL, NULL),
(501110, 27, 36, 1439454504, NULL, NULL),
(501207, 27, 25, 1440061417, NULL, NULL),
(501207, 27, 26, 1440061417, NULL, NULL),
(501207, 27, 29, 1440061417, NULL, NULL),
(501207, 27, 33, 1440061417, NULL, NULL),
(501207, 27, 36, 1440061417, NULL, NULL),
(501104, 27, 25, 1439369888, NULL, NULL),
(501104, 27, 26, 1439369888, NULL, NULL),
(501104, 27, 29, 1439369888, NULL, NULL),
(501104, 27, 33, 1439369888, NULL, NULL),
(501104, 27, 36, 1439369888, NULL, NULL),
(501153, 27, 25, 1439799738, NULL, NULL),
(501153, 27, 26, 1439799738, NULL, NULL),
(501153, 27, 29, 1439799738, NULL, NULL),
(501153, 27, 33, 1439799738, NULL, NULL),
(501153, 27, 36, 1439799738, NULL, NULL),
(501136, 27, 25, 1439542436, NULL, NULL),
(501136, 27, 26, 1439542436, NULL, NULL),
(501136, 27, 29, 1439542436, NULL, NULL),
(501136, 27, 33, 1439542436, NULL, NULL),
(501136, 27, 36, 1439542436, NULL, NULL),
(-26877, 27, 25, 1439542211, NULL, NULL),
(-26877, 27, 26, 1439542211, NULL, NULL),
(-26877, 27, 29, 1439542211, NULL, NULL),
(-26877, 27, 33, 1439542211, NULL, NULL),
(-26877, 27, 36, 1439542211, NULL, NULL),
(501168, 27, 25, 1439803081, NULL, NULL),
(501168, 27, 26, 1439803081, NULL, NULL),
(501168, 27, 29, 1439803081, NULL, NULL),
(501168, 27, 33, 1439803081, NULL, NULL),
(501168, 27, 36, 1439803081, NULL, NULL),
(501205, 27, 25, 1440060638, NULL, NULL),
(501205, 27, 26, 1440060638, NULL, NULL),
(501205, 27, 29, 1440060638, NULL, NULL),
(501205, 27, 33, 1440060638, NULL, NULL),
(501205, 27, 36, 1440060638, NULL, NULL),
(501132, 27, 25, 1439540727, NULL, NULL),
(501132, 27, 26, 1439540727, NULL, NULL),
(501132, 27, 29, 1439540727, NULL, NULL),
(501132, 27, 33, 1439540727, NULL, NULL),
(501132, 27, 36, 1439540727, NULL, NULL),
(501210, 27, 25, 1440062821, NULL, NULL),
(501210, 27, 26, 1440062821, NULL, NULL),
(501210, 27, 29, 1440062821, NULL, NULL),
(501210, 27, 33, 1440062821, NULL, NULL),
(501210, 27, 36, 1440062821, NULL, NULL),
(501208, 27, 25, 1440062343, NULL, NULL),
(501208, 27, 26, 1440062343, NULL, NULL),
(501208, 27, 29, 1440062343, NULL, NULL),
(501208, 27, 33, 1440062343, NULL, NULL),
(501208, 27, 36, 1440062343, NULL, NULL),
(502425, 24, 25, 1440603244, NULL, NULL),
(502425, 24, 26, 1440603244, NULL, NULL),
(502425, 24, 30, 1440603244, NULL, NULL),
(502425, 24, 34, 1440603244, NULL, NULL),
(502425, 24, 35, 1440603244, NULL, NULL),
(502425, 24, 36, 1440603244, NULL, NULL),
(501115, 27, 25, 1439456152, NULL, NULL),
(501115, 27, 26, 1439456152, NULL, NULL),
(501115, 27, 29, 1439456152, NULL, NULL),
(501115, 27, 33, 1439456152, NULL, NULL),
(501115, 27, 36, 1439456152, NULL, NULL),
(501167, 27, 25, 1439803071, NULL, NULL),
(501167, 27, 26, 1439803071, NULL, NULL),
(501167, 27, 29, 1439803071, NULL, NULL),
(501167, 27, 33, 1439803071, NULL, NULL),
(501167, 27, 36, 1439803071, NULL, NULL),
(501178, 27, 25, 1439886294, NULL, NULL),
(501178, 27, 26, 1439886294, NULL, NULL),
(501178, 27, 29, 1439886294, NULL, NULL),
(501178, 27, 33, 1439886294, NULL, NULL),
(501178, 27, 36, 1439886294, NULL, NULL),
(501183, 27, 25, 1439887996, NULL, NULL),
(501183, 27, 26, 1439887996, NULL, NULL),
(501183, 27, 29, 1439887996, NULL, NULL),
(501183, 27, 33, 1439887996, NULL, NULL),
(501183, 27, 36, 1439887996, NULL, NULL),
(501216, 27, 25, 1440065985, NULL, NULL),
(501216, 27, 26, 1440065985, NULL, NULL),
(501216, 27, 29, 1440065985, NULL, NULL),
(501216, 27, 33, 1440065985, NULL, NULL),
(501216, 27, 36, 1440065985, NULL, NULL),
(501171, 27, 25, 1439804539, NULL, NULL),
(501171, 27, 26, 1439804539, NULL, NULL),
(501171, 27, 29, 1439804539, NULL, NULL),
(501171, 27, 33, 1439804539, NULL, NULL),
(501171, 27, 36, 1439804539, NULL, NULL),
(501170, 27, 25, 1439803938, NULL, NULL),
(501170, 27, 26, 1439803939, NULL, NULL),
(501170, 27, 29, 1439803939, NULL, NULL),
(501170, 27, 33, 1439803939, NULL, NULL),
(501170, 27, 36, 1439803939, NULL, NULL),
(501200, 27, 25, 1440059016, NULL, NULL),
(501200, 27, 26, 1440059016, NULL, NULL),
(501200, 27, 29, 1440059016, NULL, NULL),
(501200, 27, 33, 1440059016, NULL, NULL),
(501200, 27, 36, 1440059016, NULL, NULL),
(501197, 24, 25, 1439992110, NULL, NULL),
(501197, 24, 26, 1439992110, NULL, NULL),
(501197, 24, 30, 1439992110, NULL, NULL),
(501197, 24, 34, 1439992110, NULL, NULL),
(501197, 24, 35, 1439992110, NULL, NULL),
(501197, 24, 36, 1439992110, NULL, NULL),
(501112, 27, 25, 1439455169, NULL, NULL),
(501112, 27, 26, 1439455169, NULL, NULL),
(501112, 27, 29, 1439455169, NULL, NULL),
(501112, 27, 33, 1439455169, NULL, NULL),
(501112, 27, 36, 1439455169, NULL, NULL),
(502431, 24, 25, 1440688926, NULL, NULL),
(502431, 24, 26, 1440688926, NULL, NULL),
(502431, 24, 30, 1440688926, NULL, NULL),
(502431, 24, 34, 1440688926, NULL, NULL),
(502431, 24, 35, 1440688926, NULL, NULL),
(502431, 24, 36, 1440688926, NULL, NULL),
(-3280667, 29, 25, 1440407110, NULL, NULL),
(-3280667, 29, 26, 1440407110, NULL, NULL),
(-3280667, 29, 27, 1440407110, NULL, NULL),
(501098, 27, 25, 1439368241, NULL, NULL),
(501098, 27, 26, 1439368241, NULL, NULL),
(501098, 27, 29, 1439368241, NULL, NULL),
(501098, 27, 33, 1439368241, NULL, NULL),
(501098, 27, 36, 1439368241, NULL, NULL),
(504492, 24, 25, 1442480898, NULL, NULL),
(504492, 24, 26, 1442480898, NULL, NULL),
(504492, 24, 30, 1442480898, NULL, NULL),
(504492, 24, 34, 1442480898, NULL, NULL),
(504492, 24, 35, 1442480898, NULL, NULL),
(504492, 24, 36, 1442480898, NULL, NULL),
(501184, 27, 25, 1439888512, NULL, NULL),
(501184, 27, 26, 1439888512, NULL, NULL),
(501184, 27, 29, 1439888512, NULL, NULL),
(501184, 27, 33, 1439888512, NULL, NULL),
(501184, 27, 36, 1439888512, NULL, NULL),
(501162, 27, 25, 1439802069, NULL, NULL),
(501162, 27, 26, 1439802069, NULL, NULL),
(501162, 27, 29, 1439802069, NULL, NULL),
(501162, 27, 33, 1439802069, NULL, NULL),
(501162, 27, 36, 1439802069, NULL, NULL),
(501212, 27, 25, 1440063554, NULL, NULL),
(501212, 27, 26, 1440063554, NULL, NULL),
(501212, 27, 29, 1440063554, NULL, NULL),
(501212, 27, 33, 1440063554, NULL, NULL),
(501212, 27, 36, 1440063554, NULL, NULL),
(501105, 27, 25, 1439370153, NULL, NULL),
(501105, 27, 26, 1439370153, NULL, NULL),
(501105, 27, 29, 1439370153, NULL, NULL),
(501105, 27, 33, 1439370153, NULL, NULL),
(501105, 27, 36, 1439370153, NULL, NULL),
(501157, 27, 25, 1439800368, NULL, NULL),
(501157, 27, 26, 1439800368, NULL, NULL),
(501157, 27, 29, 1439800368, NULL, NULL),
(501157, 27, 33, 1439800368, NULL, NULL),
(501157, 27, 36, 1439800368, NULL, NULL),
(501164, 27, 25, 1439802551, NULL, NULL),
(501164, 27, 26, 1439802551, NULL, NULL),
(501164, 27, 29, 1439802551, NULL, NULL),
(501164, 27, 33, 1439802551, NULL, NULL),
(501164, 27, 36, 1439802551, NULL, NULL),
(501215, 27, 25, 1440065439, NULL, NULL),
(501215, 27, 26, 1440065439, NULL, NULL),
(501215, 27, 29, 1440065439, NULL, NULL),
(501215, 27, 33, 1440065439, NULL, NULL),
(501215, 27, 36, 1440065439, NULL, NULL),
(501222, 24, 25, 1440082847, NULL, NULL),
(501222, 24, 26, 1440082847, NULL, NULL),
(501222, 24, 30, 1440082847, NULL, NULL),
(501222, 24, 34, 1440082847, NULL, NULL),
(501222, 24, 35, 1440082847, NULL, NULL),
(501222, 24, 36, 1440082847, NULL, NULL),
(501160, 27, 25, 1439801471, NULL, NULL),
(501160, 27, 26, 1439801471, NULL, NULL),
(501160, 27, 29, 1439801471, NULL, NULL),
(501160, 27, 33, 1439801471, NULL, NULL),
(501160, 27, 36, 1439801471, NULL, NULL),
(501111, 27, 25, 1439455132, NULL, NULL),
(501111, 27, 26, 1439455132, NULL, NULL),
(501111, 27, 29, 1439455132, NULL, NULL),
(501111, 27, 33, 1439455132, NULL, NULL),
(501111, 27, 36, 1439455132, NULL, NULL),
(501203, 27, 25, 1440060085, NULL, NULL),
(501203, 27, 26, 1440060085, NULL, NULL),
(501203, 27, 29, 1440060085, NULL, NULL),
(501203, 27, 33, 1440060085, NULL, NULL),
(501203, 27, 36, 1440060086, NULL, NULL);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
