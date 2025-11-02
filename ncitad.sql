-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: ncitad
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `concern_devices`
--

DROP TABLE IF EXISTS `concern_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `concern_devices` (
  `concern_id` int NOT NULL,
  `device_id` int NOT NULL,
  PRIMARY KEY (`concern_id`,`device_id`),
  KEY `device_id` (`device_id`),
  CONSTRAINT `concern_devices_ibfk_1` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`concern_id`) ON DELETE CASCADE,
  CONSTRAINT `concern_devices_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `concern_devices`
--

LOCK TABLES `concern_devices` WRITE;
/*!40000 ALTER TABLE `concern_devices` DISABLE KEYS */;
INSERT INTO `concern_devices` VALUES (1,1),(2,4),(3,10),(4,13),(5,7);
/*!40000 ALTER TABLE `concern_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `concern_history`
--

DROP TABLE IF EXISTS `concern_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `concern_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `concern_id` int NOT NULL,
  `user_id` int NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Pending','Ongoing','Resolved','Declined','Unresolved') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `resolution_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `faculty_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `devices` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `concern_history`
--

LOCK TABLES `concern_history` WRITE;
/*!40000 ALTER TABLE `concern_history` DISABLE KEYS */;
INSERT INTO `concern_history` VALUES (1,100,2,'Printer in Faculty Lounge showing paper jam error.','Resolved','2025-10-15 09:30:00','2025-10-15 14:20:00','Cleared paper jam and cleaned paper rollers. Printer working normally.','Prof. John Cruz','john.cruz@ncit.edu','[{\"device_name\":\"Canon ImageClass Printer\",\"facility_name\":\"Faculty Lounge\"}]','2025-10-15 14:25:00'),(2,101,3,'Computer Lab 1 projector has dim display.','Declined','2025-10-18 10:15:00','2025-10-18 11:30:00','Request does not fall under IT department scope - requires facilities maintenance.','Ms. Anna Reyes','anna.reyes@ncit.edu','[{\"device_name\":\"Epson Projector EB-X05\",\"facility_name\":\"Computer Lab 1\"}]','2025-10-18 11:35:00');
/*!40000 ALTER TABLE `concern_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `concerns`
--

DROP TABLE IF EXISTS `concerns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `concerns` (
  `concern_id` int NOT NULL AUTO_INCREMENT,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Pending','Ongoing','Resolved','Declined','Unresolved') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolution_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `user_id` int NOT NULL,
  PRIMARY KEY (`concern_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `concerns`
--

LOCK TABLES `concerns` WRITE;
/*!40000 ALTER TABLE `concerns` DISABLE KEYS */;
INSERT INTO `concerns` VALUES (1,'Desktop PC in Dean\'s Office not turning on. Power button is unresponsive.','Pending','2025-11-01 08:15:00','2025-11-01 08:15:00',NULL,2),(2,'Faculty Lounge printer showing offline status repeatedly.','Pending','2025-11-01 09:30:00','2025-11-01 09:30:00',NULL,3),(3,'Computer Lab 1 Desktop PC 01 monitor showing no signal error.','Ongoing','2025-10-30 10:20:00','2025-10-31 14:00:00',NULL,2),(4,'Computer Lab 2 Desktop PC 15 running very slow, needs optimization.','Ongoing','2025-10-29 11:45:00','2025-10-30 09:15:00',NULL,3),(5,'Server Room network switch making unusual beeping sounds.','Pending','2025-11-02 07:30:00','2025-11-02 07:30:00',NULL,2);
/*!40000 ALTER TABLE `concerns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `devices` (
  `device_id` int NOT NULL AUTO_INCREMENT,
  `device_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `facility_id` int NOT NULL,
  PRIMARY KEY (`device_id`),
  KEY `department_id` (`facility_id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devices`
--

LOCK TABLES `devices` WRITE;
/*!40000 ALTER TABLE `devices` DISABLE KEYS */;
INSERT INTO `devices` VALUES (1,'Desktop PC - Main Workstation',1),(2,'Desktop PC - Secretary',1),(3,'HP LaserJet Pro Printer',1),(4,'Canon ImageClass Printer',4),(5,'Epson Projector EB-X05',5),(6,'Canon Scanner LiDE 400',7),(7,'Cisco Network Switch 24-Port',9),(8,'Midea Air Conditioner 2HP',3),(9,'Dell Laptop Inspiron 15',1),(10,'Desktop PC 01',5),(11,'Desktop PC 02',5),(12,'Desktop PC 03',5),(13,'Desktop PC 15',6),(14,'Desktop PC 16',6),(15,'Desktop PC 22',2),(16,'Wireless Router TP-Link AC1750',4),(17,'Samsung Smart TV 55\"',1),(18,'ZKTeco Biometric Scanner',10),(19,'CCTV Camera 01',11),(20,'CCTV Camera 08',11),(21,'Dell Server PowerEdge T340',9),(22,'UPS APC 3000VA',9),(23,'Desktop PC - Accounting',12),(24,'Xerox Multifunction Printer',7),(25,'BenQ Projector MX535',6);
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facilities`
--

DROP TABLE IF EXISTS `facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facilities` (
  `facility_id` int NOT NULL AUTO_INCREMENT,
  `facility_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`facility_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facilities`
--

LOCK TABLES `facilities` WRITE;
/*!40000 ALTER TABLE `facilities` DISABLE KEYS */;
INSERT INTO `facilities` VALUES (1,'Dean\'s Office'),(2,'Computer Science Department'),(3,'Information Technology Department'),(4,'Faculty Lounge'),(5,'Computer Lab 1'),(6,'Computer Lab 2'),(7,'Registrar Office'),(8,'Library'),(9,'Server Room'),(10,'Human Resources'),(11,'Security Office'),(12,'Accounting Office');
/*!40000 ALTER TABLE `facilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `reset_id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reset_id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `idx_token` (`token`),
  KEY `idx_expiry` (`expiry`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `faculty_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `first_login` tinyint NOT NULL DEFAULT '1',
  `account_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','IT Administrator','admin@ncit.edu','$2y$10$L6T2x1gln2yLA0448D76GOoMfY/KaOdn2C7cByv1/IQ2kPvromI0q',1,1,0,NULL,'2025-10-01 08:00:00','2025-11-02 08:00:00'),(2,'jcruz','Prof. John Cruz','john.cruz@ncit.edu','$2y$10$TLJF3xc48KgQ40H36RL8Wu5bRdQnv8pQwCPGoLCoCgmMkH5MJq5YW',0,1,0,NULL,'2025-10-05 09:00:00','2025-11-02 08:00:00'),(3,'areyes','Ms. Anna Reyes','anna.reyes@ncit.edu','$2y$10$TLJF3xc48KgQ40H36RL8Wu5bRdQnv8pQwCPGoLCoCgmMkH5MJq5YW',0,1,0,NULL,'2025-10-10 10:00:00','2025-11-02 08:00:00');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-02 13:00:49
