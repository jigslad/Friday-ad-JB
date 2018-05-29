-- MySQL dump 10.15  Distrib 10.0.13-MariaDB, for Linux (x86_64)
--
-- Host: 192.168.100.31    Database: fridayad_jnj
-- ------------------------------------------------------
-- Server version	5.5.34-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `upsell`
--

DROP TABLE IF EXISTS `upsell`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `upsell` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8_unicode_ci,
  `price` double DEFAULT NULL,
  `value` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `value1` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `duration` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `updated_at` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `upsell_for` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `upsell`
--

LOCK TABLES `upsell` WRITE;
/*!40000 ALTER TABLE `upsell` DISABLE KEYS */;
INSERT INTO `upsell` VALUES (1,1,'1 photo',NULL,NULL,'1',NULL,NULL,1426053248,NULL,1,'ad'),(2,1,'2 photos',NULL,NULL,'2',NULL,NULL,1426053248,NULL,1,'ad'),(3,1,'8 photos',NULL,NULL,'8',NULL,NULL,1426053248,NULL,1,'ad'),(4,1,'20 photos',NULL,NULL,'20',NULL,NULL,1426053248,NULL,1,'ad'),(5,16,'Top Ad',NULL,NULL,NULL,NULL,NULL,1426053248,NULL,1,'ad'),(6,15,'Highlighted Ad',NULL,NULL,NULL,NULL,NULL,1426053248,NULL,1,'ad'),(7,19,'1 Edition Print Publication',NULL,NULL,'1',NULL,'1w',1426053248,NULL,1,'ad'),(8,19,'3 Editions Print Publication',NULL,NULL,'3',NULL,'1w',1426053248,NULL,1,'ad'),(9,19,'5 Editions Print Publication',NULL,NULL,'5',NULL,'1w',1426053248,NULL,1,'ad'),(10,20,'Photo in Print',NULL,NULL,NULL,NULL,NULL,1426053248,NULL,1,'ad'),(11,2,'Weekly Ad Refresh',NULL,NULL,'7',NULL,NULL,1426053248,NULL,1,'ad'),(12,2,'Monthly Ad Refresh',NULL,NULL,'30',NULL,NULL,1426053248,NULL,1,'ad'),(13,4,'Branding',NULL,NULL,NULL,NULL,NULL,1426053248,NULL,1,'ad'),(14,11,'Video',NULL,NULL,NULL,NULL,NULL,1426053248,NULL,1,'ad'),(15,3,'Targeted Emails',NULL,NULL,NULL,NULL,NULL,1426053248,NULL,1,'ad'),(16,10,'Location Lookup',NULL,NULL,NULL,NULL,NULL,1426053248,NULL,1,'ad'),(17,8,'1 Document Upload',NULL,NULL,'1',NULL,NULL,1426053248,NULL,1,'ad'),(18,8,'3 Document Upload',NULL,NULL,'3',NULL,NULL,1426053248,NULL,1,'ad'),(19,6,'1 Screening Question',NULL,NULL,'1',NULL,NULL,1426053248,NULL,1,'ad'),(20,6,'5 Screening Question',NULL,NULL,'5',NULL,NULL,1426053248,NULL,1,'ad'),(21,18,'FMG Sites Listed',NULL,NULL,NULL,NULL,NULL,1426053248,NULL,1,'ad'),(22,5,'Accurate Valuation',NULL,NULL,NULL,NULL,NULL,1426053248,NULL,1,'ad'),(23,23,'Local Listing',NULL,NULL,'30',NULL,NULL,1426053248,NULL,1,'ad'),(24,23,'National Listing',NULL,NULL,'National',NULL,NULL,1426053248,NULL,1,'ad'),(25,24,'Enhanced profile',NULL,NULL,NULL,NULL,NULL,1426053248,NULL,1,'shop'),(26,25,'Verified business badge',NULL,NULL,NULL,NULL,NULL,1426053248,NULL,1,'shop'),(27,26,'Profile exposure (0 miles)',NULL,NULL,'0',NULL,NULL,1426053248,1426060280,1,'shop'),(28,26,'Profile exposure (30 miles)',NULL,NULL,'30',NULL,NULL,1426053248,NULL,1,'shop'),(29,26,'Profile exposure (60 miles)',NULL,NULL,'60',NULL,NULL,1426053248,NULL,1,'shop'),(30,26,'Profile exposure (national)',NULL,NULL,'national',NULL,NULL,1426053248,NULL,1,'shop'),(31,27,'Advert exposure (0 miles)',NULL,NULL,'0',NULL,NULL,1426053248,1426060615,1,'shop'),(32,27,'Advert exposure (30 miles)',NULL,NULL,'30',NULL,NULL,1426053248,NULL,1,'shop'),(33,27,'Advert exposure (60 miles)',NULL,NULL,'60',NULL,NULL,1426053248,NULL,1,'shop'),(34,27,'Advert exposure (national)',NULL,NULL,'national',NULL,NULL,1426053248,NULL,1,'shop'),(35,28,'Item quantities',NULL,NULL,NULL,NULL,NULL,1426053248,NULL,1,'shop'),(36,29,'Full social integration',NULL,NULL,NULL,NULL,NULL,1426053248,NULL,1,'shop');
/*!40000 ALTER TABLE `upsell` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-03-11 14:32:44
