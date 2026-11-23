CREATE DATABASE  IF NOT EXISTS `smart_gym` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `smart_gym`;
-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: localhost    Database: smart_gym
-- ------------------------------------------------------
-- Server version	8.4.11

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
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'Abhinav','abhi@gmail.com','$2y$12$VM/p8q7ouTAck7piIkAVruvXwkaNf.OwauwGU1JPp8PxKO1l7IRzC','2026-11-22 19:00:14');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES (1,'Tom','tom@gmail.com','8558120805','$2y$12$eWASJf.tKJBIObRCjjNH8.7WhfUUF63zGJu9gteWEMbPbxmI/Lb9W','2026-11-22 18:45:45');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `complaints`
--

DROP TABLE IF EXISTS `complaints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `complaints` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `admin_response` text,
  `status` enum('Pending','In Progress','Resolved') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaints`
--

LOCK TABLES `complaints` WRITE;
/*!40000 ALTER TABLE `complaints` DISABLE KEYS */;
INSERT INTO `complaints` VALUES (1,1,'late response from trainer','hereby complaining about not getting proper reply from the assigned trainers within a specified time','trainer is awared about the situation and the problem is solved','Resolved','2026-11-23 08:39:07','2026-11-23 08:44:52');
/*!40000 ALTER TABLE `complaints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `diet_plans`
--

DROP TABLE IF EXISTS `diet_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `diet_plans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dietitian_id` int NOT NULL,
  `client_id` int NOT NULL,
  `plan_name` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `daily_calories` int DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `dietitian_id` (`dietitian_id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `diet_plans_ibfk_1` FOREIGN KEY (`dietitian_id`) REFERENCES `dietitians` (`id`) ON DELETE CASCADE,
  CONSTRAINT `diet_plans_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `diet_plans`
--

LOCK TABLES `diet_plans` WRITE;
/*!40000 ALTER TABLE `diet_plans` DISABLE KEYS */;
INSERT INTO `diet_plans` VALUES (1,1,1,'weight loss','chicken & salad',1500,'2026-11-23','2026-11-30','2026-11-22 20:17:49');
/*!40000 ALTER TABLE `diet_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dietitian_questions`
--

DROP TABLE IF EXISTS `dietitian_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dietitian_questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `dietitian_id` int NOT NULL,
  `question` text NOT NULL,
  `response` text,
  `status` enum('Pending','Answered') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `dietitian_id` (`dietitian_id`),
  CONSTRAINT `dietitian_questions_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dietitian_questions_ibfk_2` FOREIGN KEY (`dietitian_id`) REFERENCES `dietitians` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dietitian_questions`
--

LOCK TABLES `dietitian_questions` WRITE;
/*!40000 ALTER TABLE `dietitian_questions` DISABLE KEYS */;
INSERT INTO `dietitian_questions` VALUES (1,1,1,'Non veg protien diet','200g carb,250g chicken,150g fiber','Answered','2026-11-23 08:27:51','2026-11-23 08:31:02'),(2,1,1,'Non veg protien diet',NULL,'Pending','2026-11-23 08:31:09',NULL);
/*!40000 ALTER TABLE `dietitian_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dietitians`
--

DROP TABLE IF EXISTS `dietitians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dietitians` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dietitians`
--

LOCK TABLES `dietitians` WRITE;
/*!40000 ALTER TABLE `dietitians` DISABLE KEYS */;
INSERT INTO `dietitians` VALUES (1,'Maxy','maxy@gmail.com','8547896233','$2y$12$IRwILdewWUE9BOTLaYPxaO3CLxIhJ4WC2bY6JH28UQMD1tGQdbGPS','2026-11-22 20:06:16');
/*!40000 ALTER TABLE `dietitians` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membership_requests`
--

DROP TABLE IF EXISTS `membership_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `membership_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `message` text,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `requested_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `membership_requests_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membership_requests`
--

LOCK TABLES `membership_requests` WRITE;
/*!40000 ALTER TABLE `membership_requests` DISABLE KEYS */;
INSERT INTO `membership_requests` VALUES (3,1,'Monthly',NULL,'Approved','2026-11-23 09:19:01'),(4,1,'Monthly',NULL,'Pending','2026-11-23 09:25:04');
/*!40000 ALTER TABLE `membership_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `memberships`
--

DROP TABLE IF EXISTS `memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `memberships` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Paid','Pending') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `memberships_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `memberships`
--

LOCK TABLES `memberships` WRITE;
/*!40000 ALTER TABLE `memberships` DISABLE KEYS */;
INSERT INTO `memberships` VALUES (3,1,'Monthly','2026-11-23','2026-12-23',999.00,'Paid','2026-11-23 09:24:59');
/*!40000 ALTER TABLE `memberships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_id` int NOT NULL,
  `client_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('UPI','Card','Cash') NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `status` enum('Success','Failed') NOT NULL,
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  KEY `request_id` (`request_id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `membership_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,3,1,999.00,'Cash','DEMO-867238E1F705A45A','Success','2026-11-23 09:19:01'),(2,4,1,999.00,'Cash','DEMO-3AD3EA08BF85D6B8','Success','2026-11-23 09:25:04');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `progress_records`
--

DROP TABLE IF EXISTS `progress_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `progress_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `trainer_id` int NOT NULL,
  `client_id` int NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `height` decimal(5,2) NOT NULL,
  `bmi` decimal(5,2) NOT NULL,
  `notes` text,
  `recorded_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `trainer_id` (`trainer_id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `progress_records_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `progress_records_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `progress_records`
--

LOCK TABLES `progress_records` WRITE;
/*!40000 ALTER TABLE `progress_records` DISABLE KEYS */;
INSERT INTO `progress_records` VALUES (1,1,1,56.00,1.65,20.57,'weight loss in process','2026-11-22','2026-11-22 19:51:37');
/*!40000 ALTER TABLE `progress_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainer_clients`
--

DROP TABLE IF EXISTS `trainer_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trainer_clients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `trainer_id` int NOT NULL,
  `client_id` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_trainer_client` (`trainer_id`,`client_id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `trainer_clients_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trainer_clients_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainer_clients`
--

LOCK TABLES `trainer_clients` WRITE;
/*!40000 ALTER TABLE `trainer_clients` DISABLE KEYS */;
INSERT INTO `trainer_clients` VALUES (1,1,1,'2026-11-22 19:32:26');
/*!40000 ALTER TABLE `trainer_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainer_questions`
--

DROP TABLE IF EXISTS `trainer_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trainer_questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `trainer_id` int NOT NULL,
  `question` text NOT NULL,
  `response` text,
  `status` enum('Pending','Answered') DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `trainer_questions_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trainer_questions_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainer_questions`
--

LOCK TABLES `trainer_questions` WRITE;
/*!40000 ALTER TABLE `trainer_questions` DISABLE KEYS */;
INSERT INTO `trainer_questions` VALUES (1,1,1,'Quick dehydration','check sugar','Answered','2026-11-23 08:20:43','2026-11-23 08:23:28'),(2,1,1,'Quick dehydration',NULL,'Pending','2026-11-23 08:23:34',NULL);
/*!40000 ALTER TABLE `trainer_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainers`
--

DROP TABLE IF EXISTS `trainers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trainers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainers`
--

LOCK TABLES `trainers` WRITE;
/*!40000 ALTER TABLE `trainers` DISABLE KEYS */;
INSERT INTO `trainers` VALUES (1,'Rexe','rexe@gmail.com','7615452885','Cardio trainer','$2y$12$CSohZHYPd9T4Y6NXchPUU.O4Ti4/GBTocjqHaIj9IjNRnEuVmJgTy','2026-11-22 19:26:43');
/*!40000 ALTER TABLE `trainers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workout_plans`
--

DROP TABLE IF EXISTS `workout_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workout_plans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `trainer_id` int NOT NULL,
  `client_id` int NOT NULL,
  `plan_name` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `duration_weeks` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `trainer_id` (`trainer_id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `workout_plans_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workout_plans_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workout_plans`
--

LOCK TABLES `workout_plans` WRITE;
/*!40000 ALTER TABLE `workout_plans` DISABLE KEYS */;
INSERT INTO `workout_plans` VALUES (1,1,1,'Weight Loss','push ups 10*3',4,'2026-11-23','2026-12-01','2026-11-22 19:39:06');
/*!40000 ALTER TABLE `workout_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workout_videos`
--

DROP TABLE IF EXISTS `workout_videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workout_videos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `trainer_id` int NOT NULL,
  `client_id` int NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text,
  `video_filename` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `trainer_id` (`trainer_id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `workout_videos_ibfk_1` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workout_videos_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workout_videos`
--

LOCK TABLES `workout_videos` WRITE;
/*!40000 ALTER TABLE `workout_videos` DISABLE KEYS */;
/*!40000 ALTER TABLE `workout_videos` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-23 23:32:56
