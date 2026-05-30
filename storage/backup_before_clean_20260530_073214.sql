-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: new_pos
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (11,'মিনিকেট চাল',NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(12,'নাজিরশাইল চাল',NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(13,'বিরি ধান চাল',NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(14,'আতব চাল',NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(15,'পোলাও চাল',NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(16,'অন্যান্য চাল',NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_areas`
--

DROP TABLE IF EXISTS `customer_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_areas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_areas`
--

LOCK TABLES `customer_areas` WRITE;
/*!40000 ALTER TABLE `customer_areas` DISABLE KEYS */;
INSERT INTO `customer_areas` VALUES (1,'tongi','2026-05-21 18:28:08','2026-05-21 18:28:08'),(2,'mirpur','2026-05-21 18:28:20','2026-05-21 18:28:20');
/*!40000 ALTER TABLE `customer_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_payments`
--

DROP TABLE IF EXISTS `customer_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `previous_due` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_date` date NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'নগদ',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_payments_customer_id_foreign` (`customer_id`),
  KEY `customer_payments_user_id_foreign` (`user_id`),
  CONSTRAINT `customer_payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_payments`
--

LOCK TABLES `customer_payments` WRITE;
/*!40000 ALTER TABLE `customer_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proprietor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `area_id` bigint unsigned DEFAULT NULL,
  `due_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_area_id_foreign` (`area_id`),
  CONSTRAINT `customers_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `customer_areas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'করিম স্টোর',NULL,'01700000001',NULL,'মিরপুর, ঢাকা',NULL,2350.00,'2026-05-10 22:37:38','2026-05-21 00:50:18'),(2,'রহিম ট্রেডার্স',NULL,'01800000002',NULL,'যাত্রাবাড়ী, ঢাকা',NULL,0.00,'2026-05-10 22:37:38','2026-05-10 23:01:09'),(3,'আল-আমিন রাইস হাউস',NULL,'01900000003',NULL,'নারায়ণগঞ্জ',2,14570.00,'2026-05-10 22:37:38','2026-05-27 02:10:06'),(4,'হাজী ব্রাদার্স',NULL,'01700000004',NULL,'চট্টগ্রাম',NULL,0.00,'2026-05-10 22:37:38','2026-05-29 19:20:12'),(5,'মেসার্স সালাম',NULL,'01800000005',NULL,'কুমিল্লা',NULL,0.00,'2026-05-10 22:37:38','2026-05-10 23:01:09'),(7,'নিউ ঢাকা রাইস',NULL,'01600000006',NULL,'গাজীপুর',2,0.00,'2026-05-10 23:01:09','2026-05-26 23:26:15');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary` decimal(12,2) NOT NULL DEFAULT '0.00',
  `join_date` date DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `extra_expenses`
--

DROP TABLE IF EXISTS `extra_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `extra_expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'expense',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expense_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `extra_expenses_user_id_foreign` (`user_id`),
  CONSTRAINT `extra_expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `extra_expenses`
--

LOCK TABLES `extra_expenses` WRITE;
/*!40000 ALTER TABLE `extra_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `extra_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_brands`
--

DROP TABLE IF EXISTS `item_brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_brands` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_brands`
--

LOCK TABLES `item_brands` WRITE;
/*!40000 ALTER TABLE `item_brands` DISABLE KEYS */;
/*!40000 ALTER TABLE `item_brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_types`
--

DROP TABLE IF EXISTS `item_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_types`
--

LOCK TABLES `item_types` WRITE;
/*!40000 ALTER TABLE `item_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `item_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `type_id` bigint unsigned DEFAULT NULL,
  `brand_id` bigint unsigned DEFAULT NULL,
  `unit_type_id` bigint unsigned DEFAULT NULL,
  `purchase_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `sale_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pcs',
  `description` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `items_code_unique` (`code`),
  KEY `items_category_id_foreign` (`category_id`),
  KEY `items_type_id_foreign` (`type_id`),
  KEY `items_brand_id_foreign` (`brand_id`),
  KEY `items_unit_type_id_foreign` (`unit_type_id`),
  CONSTRAINT `items_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `item_brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `items_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `items_type_id_foreign` FOREIGN KEY (`type_id`) REFERENCES `item_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `items_unit_type_id_foreign` FOREIGN KEY (`unit_type_id`) REFERENCES `unit_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
INSERT INTO `items` VALUES (9,'মিনিকেট চাল ৫০ কেজি বস্তা',NULL,11,NULL,NULL,NULL,2800.00,2950.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(10,'মিনিকেট চাল ২৫ কেজি বস্তা',NULL,11,NULL,NULL,NULL,1400.00,1480.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(11,'নাজিরশাইল চাল ৫০ কেজি বস্তা',NULL,12,NULL,NULL,NULL,3200.00,3380.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(12,'নাজিরশাইল চাল ২৫ কেজি বস্তা',NULL,12,NULL,NULL,NULL,1600.00,1700.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-21 20:27:24'),(13,'বিরি-২৮ চাল ৫০ কেজি বস্তা',NULL,13,NULL,NULL,NULL,2400.00,2550.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(14,'বিরি-২৯ চাল ৫০ কেজি বস্তা',NULL,13,NULL,NULL,NULL,2450.00,2600.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(15,'বিরি-২৮ চাল ২৫ কেজি বস্তা',NULL,13,NULL,NULL,NULL,1200.00,1280.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(16,'আতব চাল ৫০ কেজি বস্তা',NULL,14,NULL,NULL,NULL,2200.00,2350.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-21 20:16:48'),(17,'আতব চাল ২৫ কেজি বস্তা',NULL,14,NULL,NULL,NULL,1100.00,1180.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-21 20:15:50'),(18,'পোলাও চাল ৫০ কেজি বস্তা',NULL,15,NULL,NULL,NULL,4500.00,4750.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(19,'পোলাও চাল ২৫ কেজি বস্তা',NULL,15,NULL,NULL,NULL,2250.00,2400.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-21 20:27:24'),(20,'কাটারিভোগ চাল ৫০ কেজি বস্তা',NULL,16,NULL,NULL,NULL,5200.00,5500.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(21,'কাটারিভোগ চাল ২৫ কেজি বস্তা',NULL,16,NULL,NULL,NULL,2600.00,2750.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(22,'স্বর্ণা চাল ৫০ কেজি বস্তা',NULL,16,NULL,NULL,NULL,2300.00,2450.00,'বস্তা',NULL,NULL,'2026-05-10 23:01:09','2026-05-10 23:01:09');
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_01_01_000010_create_customers_table',1),(5,'2024_01_01_000011_create_categories_table',1),(6,'2024_01_01_000012_create_suppliers_table',1),(7,'2024_01_01_000013_create_items_table',1),(8,'2024_01_01_000014_create_stock_table',1),(9,'2024_01_01_000015_create_purchases_table',1),(10,'2024_01_01_000016_create_sales_table',1),(11,'2024_01_01_000017_create_employees_table',1),(12,'2024_01_01_000018_create_extra_expenses_table',1),(13,'2024_01_01_000019_create_store_config_table',1),(14,'2024_01_01_000020_add_role_to_users_table',1),(15,'2024_01_02_000000_add_invoice_fields',2),(16,'2024_01_03_000000_create_customer_area_and_payments',3),(17,'2024_01_04_000000_create_item_meta_tables',4),(18,'2024_01_05_000000_create_supplier_payments_table',5),(19,'2024_01_06_000000_add_payment_method_to_sales',6),(20,'2024_01_07_000000_add_payment_method_to_purchases',7),(21,'2026_05_22_023259_add_type_to_extra_expenses_table',8),(22,'2026_05_23_000001_add_previous_due_to_customer_payments_table',9),(23,'2026_05_24_000001_add_edit_fields_to_sales_table',10),(24,'2026_05_27_000001_add_extra_labor_cost_to_sales',11),(25,'2026_05_27_000002_add_extra_labor_cost_to_purchases',12);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_items_item_id_foreign` (`item_id`),
  CONSTRAINT `purchase_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
INSERT INTO `purchase_items` VALUES (1,1,12,12.00,1600.00,19200.00),(2,2,17,100.00,1100.00,110000.00),(3,3,16,15.00,2200.00,33000.00),(4,4,12,1.00,1600.00,1600.00),(5,4,19,100.00,2250.00,225000.00);
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `extra_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `labor_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `due_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'নগদ',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `purchase_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchases_supplier_id_foreign` (`supplier_id`),
  KEY `purchases_user_id_foreign` (`user_id`),
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES (1,NULL,1,19200.00,0.00,0.00,10000.00,9200.00,'নগদ',NULL,'2026-05-20','2026-05-20 04:22:09','2026-05-20 04:22:09'),(2,5,1,110000.00,0.00,0.00,50000.00,60000.00,'নগদ',NULL,'2026-05-22','2026-05-21 20:15:50','2026-05-21 20:15:50'),(3,5,1,33000.00,0.00,0.00,5000.00,28000.00,'নগদ',NULL,'2026-05-22','2026-05-21 20:16:48','2026-05-21 20:16:48'),(4,5,1,226600.00,0.00,0.00,45000.00,181600.00,'নগদ',NULL,'2026-05-22','2026-05-21 20:27:24','2026-05-21 20:27:24');
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_items_sale_id_foreign` (`sale_id`),
  KEY `sale_items_item_id_foreign` (`item_id`),
  CONSTRAINT `sale_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
INSERT INTO `sale_items` VALUES (1,1,17,1.00,1180.00,1180.00),(2,1,16,1.00,2350.00,2350.00),(3,2,12,1.00,1700.00,1700.00),(4,2,17,1.00,1180.00,1180.00),(5,3,12,10.00,1700.00,17000.00),(6,3,10,5.00,1480.00,7400.00),(7,4,12,43.00,1700.00,73100.00),(8,5,12,1.00,1700.00,1700.00),(9,5,10,1.00,1480.00,1480.00),(10,6,17,1.00,1180.00,1180.00),(11,7,17,1.00,1180.00,1180.00),(12,8,21,1.00,2750.00,2750.00),(13,9,16,1.00,2350.00,2350.00),(14,10,16,1.00,2350.00,2350.00),(18,14,11,1.00,3380.00,3380.00),(19,15,11,1.00,3380.00,3380.00),(20,16,11,1.00,3380.00,3380.00),(21,17,11,1.00,3380.00,3380.00),(22,18,11,1.00,3380.00,3380.00),(23,19,11,1.00,3380.00,3380.00),(25,21,11,2.00,3380.00,6760.00),(26,22,11,1.00,3380.00,3380.00),(27,23,17,1.00,1180.00,1180.00),(30,24,17,1.00,1180.00,1180.00),(31,32,11,15.00,1800.00,27000.00),(32,33,12,5.00,1800.00,9000.00);
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `extra_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `labor_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `due_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `previous_due` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('completed','pending','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'নগদ',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_edited` tinyint(1) NOT NULL DEFAULT '0',
  `edit_note` text COLLATE utf8mb4_unicode_ci,
  `sale_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_customer_id_foreign` (`customer_id`),
  KEY `sales_user_id_foreign` (`user_id`),
  CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (1,NULL,1,3530.00,0.00,0.00,0.00,0.00,3530.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-11','2026-05-10 23:19:18','2026-05-10 23:19:18'),(2,NULL,1,2880.00,0.00,0.00,0.00,1080.00,1800.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-11','2026-05-10 23:22:51','2026-05-10 23:22:51'),(3,3,1,23400.00,1000.00,0.00,0.00,20000.00,3400.00,0.00,'completed','নগদ','উনি সাম্নের মাসের ১০ তারিখ বাকি পরিসধ করবে',0,NULL,'2026-05-18','2026-05-18 00:59:34','2026-05-18 00:59:34'),(4,NULL,1,73100.00,0.00,0.00,0.00,20000.00,53100.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-20','2026-05-20 04:23:25','2026-05-20 04:23:25'),(5,NULL,1,3180.00,0.00,0.00,0.00,0.00,3180.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-20','2026-05-20 05:11:56','2026-05-20 05:11:56'),(6,NULL,1,1180.00,0.00,0.00,0.00,0.00,1180.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-21','2026-05-21 00:40:00','2026-05-21 00:40:00'),(7,NULL,1,1180.00,0.00,0.00,0.00,0.00,1180.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-21','2026-05-21 00:43:17','2026-05-21 00:43:17'),(8,NULL,1,2750.00,0.00,0.00,0.00,2750.00,0.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-21','2026-05-21 00:49:35','2026-05-21 00:49:35'),(9,1,1,2350.00,0.00,0.00,0.00,0.00,2350.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-21','2026-05-21 00:50:18','2026-05-21 00:50:18'),(10,NULL,1,2350.00,0.00,0.00,0.00,0.00,2350.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-21','2026-05-21 01:34:41','2026-05-21 01:34:41'),(14,3,1,3380.00,0.00,0.00,0.00,6380.00,0.00,3400.00,'completed','নগদ',NULL,0,NULL,'2026-05-22','2026-05-21 19:04:33','2026-05-21 19:04:33'),(15,3,1,3380.00,0.00,0.00,0.00,4000.00,0.00,3400.00,'completed','নগদ',NULL,0,NULL,'2026-05-22','2026-05-21 19:06:53','2026-05-21 19:06:53'),(16,3,1,3380.00,0.00,0.00,0.00,6000.00,0.00,3400.00,'completed','নগদ',NULL,0,NULL,'2026-05-22','2026-05-21 19:23:39','2026-05-21 19:23:39'),(17,3,1,3380.00,0.00,0.00,0.00,3480.00,0.00,3400.00,'completed','নগদ',NULL,0,NULL,'2026-05-22','2026-05-21 19:31:28','2026-05-21 19:31:28'),(18,3,1,3380.00,0.00,0.00,0.00,6000.00,0.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-22','2026-05-21 19:46:30','2026-05-21 19:46:30'),(19,3,1,3380.00,0.00,0.00,0.00,300.00,3080.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-22','2026-05-21 19:47:18','2026-05-21 19:47:18'),(21,3,1,6760.00,0.00,0.00,0.00,3000.00,3760.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-22','2026-05-21 19:52:28','2026-05-21 19:52:28'),(22,3,1,3380.00,0.00,0.00,0.00,100.00,3280.00,1280.00,'completed','নগদ',NULL,0,NULL,'2026-05-08','2026-05-21 19:53:21','2026-05-21 19:53:21'),(23,3,1,1180.00,0.00,0.00,0.00,0.00,1180.00,4560.00,'completed','নগদ',NULL,0,NULL,'2026-05-23','2026-05-23 05:15:17','2026-05-23 05:15:17'),(24,3,1,1180.00,0.00,0.00,0.00,1000.00,180.00,44820.00,'completed','নগদ',NULL,1,NULL,'2026-05-23','2026-05-23 05:34:05','2026-05-23 23:30:02'),(25,3,1,0.00,0.00,0.00,0.00,50.00,0.00,5920.00,'completed','নগদ',NULL,0,NULL,'2026-05-23','2026-05-23 05:43:42','2026-05-23 05:43:42'),(29,3,1,0.00,0.00,0.00,0.00,100.00,0.00,51500.00,'completed','নগদ',NULL,0,NULL,'2026-05-24','2026-05-23 23:35:28','2026-05-23 23:35:28'),(30,3,1,0.00,0.00,0.00,0.00,10000.00,0.00,51400.00,'completed','নগদ',NULL,0,NULL,'2026-05-24','2026-05-23 23:36:18','2026-05-23 23:36:18'),(31,3,1,0.00,0.00,0.00,0.00,100.00,0.00,41400.00,'completed','নগদ',NULL,0,NULL,'2026-05-24','2026-05-23 23:55:28','2026-05-23 23:55:28'),(32,3,1,27000.00,0.00,0.00,0.00,6000.00,21000.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-27','2026-05-26 23:24:16','2026-05-26 23:24:16'),(33,7,1,9000.00,0.00,0.00,0.00,9000.00,0.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-27','2026-05-26 23:26:15','2026-05-26 23:26:15'),(34,3,1,0.00,0.00,0.00,0.00,1000.00,0.00,16670.00,'completed','নগদ',NULL,0,NULL,'2026-05-27','2026-05-27 01:57:18','2026-05-27 01:57:18'),(35,3,1,0.00,0.00,0.00,0.00,1000.00,0.00,15670.00,'completed','নগদ',NULL,0,NULL,'2026-05-27','2026-05-27 02:01:45','2026-05-27 02:01:45'),(36,3,1,0.00,0.00,0.00,0.00,100.00,0.00,14670.00,'completed','নগদ',NULL,0,NULL,'2026-05-27','2026-05-27 02:10:06','2026-05-27 02:10:06'),(37,4,1,0.00,0.00,0.00,0.00,5000.00,0.00,0.00,'completed','নগদ',NULL,0,NULL,'2026-05-30','2026-05-29 19:20:12','2026-05-29 19:20:12');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock`
--

DROP TABLE IF EXISTS `stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint unsigned NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `min_quantity` decimal(12,2) NOT NULL DEFAULT '5.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_item_id_foreign` (`item_id`),
  CONSTRAINT `stock_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock`
--

LOCK TABLES `stock` WRITE;
/*!40000 ALTER TABLE `stock` DISABLE KEYS */;
INSERT INTO `stock` VALUES (9,9,80.00,10.00,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(10,10,44.00,5.00,'2026-05-10 23:01:09','2026-05-20 05:11:56'),(11,11,36.00,10.00,'2026-05-10 23:01:09','2026-05-26 23:24:16'),(12,12,15.00,5.00,'2026-05-10 23:01:09','2026-05-26 23:26:15'),(13,13,100.00,15.00,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(14,14,90.00,15.00,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(15,15,60.00,8.00,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(16,16,82.00,10.00,'2026-05-10 23:01:09','2026-05-21 20:16:48'),(17,17,97.00,5.00,'2026-05-10 23:01:09','2026-05-23 23:34:56'),(18,18,30.00,5.00,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(19,19,120.00,3.00,'2026-05-10 23:01:09','2026-05-21 20:27:24'),(20,20,150.00,3.00,'2026-05-10 23:01:09','2026-05-26 23:25:28'),(21,21,30.00,3.00,'2026-05-10 23:01:09','2026-05-26 23:25:08'),(22,22,55.00,10.00,'2026-05-10 23:01:09','2026-05-10 23:01:09');
/*!40000 ALTER TABLE `stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_config`
--

DROP TABLE IF EXISTS `store_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_config_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_config`
--

LOCK TABLES `store_config` WRITE;
/*!40000 ALTER TABLE `store_config` DISABLE KEYS */;
INSERT INTO `store_config` VALUES (1,'store_name','মেসার্স রূপসী বাংলা ট্রেডার্স','2026-05-10 22:37:38','2026-05-11 00:01:39'),(2,'store_phone','01942 - 796401','2026-05-10 22:37:38','2026-05-11 00:01:39'),(3,'store_address','কাচারী রোড, টঙ্গী বাজার, গাজীপুর।','2026-05-10 22:37:38','2026-05-11 00:01:39'),(4,'currency','৳','2026-05-10 22:37:38','2026-05-10 22:37:38'),(5,'store_owner','মোঃ ফারুক হোসাইন','2026-05-10 23:43:59','2026-05-11 00:01:39'),(6,'store_tagline','পাইকারী চাউল বিক্রেতা ও কমিশন এজেন্ট।','2026-05-10 23:43:59','2026-05-11 00:01:39'),(7,'store_phone2','01925 - 507321','2026-05-10 23:43:59','2026-05-11 00:01:39'),(8,'payment_methods','[{\"name\":\"নগদ\",\"group\":\"নগদ\"},{\"name\":\"বিকাশ\",\"group\":\"মোবাইল ব্যাংকিং\"},{\"name\":\"নগদ মোবাইল\",\"group\":\"মোবাইল ব্যাংকিং\"},{\"name\":\"রকেট\",\"group\":\"মোবাইল ব্যাংকিং\"},{\"name\":\"উপায়\",\"group\":\"মোবাইল ব্যাংকিং\"},{\"name\":\"T.T. সোনালী ব্যাংক\",\"group\":\"ব্যাংক ট্রান্সফার\"},{\"name\":\"T.T. জনতা ব্যাংক\",\"group\":\"ব্যাংক ট্রান্সফার\"},{\"name\":\"T.T. অগ্রণী ব্যাংক\",\"group\":\"ব্যাংক ট্রান্সফার\"},{\"name\":\"T.T. রূপালী ব্যাংক\",\"group\":\"ব্যাংক ট্রান্সফার\"},{\"name\":\"T.T. ন্যাশনাল ব্যাংক\",\"group\":\"ব্যাংক ট্রান্সফার\"},{\"name\":\"T.T. উত্তরা ব্যাংক\",\"group\":\"ব্যাংক ট্রান্সফার\"},{\"name\":\"T.T. ইসলামী ব্যাংক\",\"group\":\"ব্যাংক ট্রান্সফার\"},{\"name\":\"T.T. ডাচবাংলা ব্যাংক\",\"group\":\"ব্যাংক ট্রান্সফার\"},{\"name\":\"T.T. IFIC ব্যাংক\",\"group\":\"ব্যাংক ট্রান্সফার\"},{\"name\":\"T.T. পূবালী ব্যাংক\",\"group\":\"ব্যাংক ট্রান্সফার\"},{\"name\":\"T.T. NRB ব্যাংক RTGS\",\"group\":\"ব্যাংক ট্রান্সফার\"},{\"name\":\"চেক (সাধারণ)\",\"group\":\"চেক\"},{\"name\":\"চেক ডাচবাংলা\",\"group\":\"চেক\"},{\"name\":\"চেক সোনালী\",\"group\":\"চেক\"},{\"name\":\"মাল ফেরত\",\"group\":\"অন্যান্য\"},{\"name\":\"কমিশন বাবদ\",\"group\":\"অন্যান্য\"},{\"name\":\"বাকী\",\"group\":\"অন্যান্য\"},{\"name\":\"অন্যান্য\",\"group\":\"অন্যান্য\"}]','2026-05-21 01:31:47','2026-05-21 01:31:47'),(9,'multimedia_enabled','0','2026-05-21 02:17:14','2026-05-21 18:26:22');
/*!40000 ALTER TABLE `store_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_payments`
--

DROP TABLE IF EXISTS `supplier_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'নগদ',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_payments_supplier_id_foreign` (`supplier_id`),
  KEY `supplier_payments_user_id_foreign` (`user_id`),
  CONSTRAINT `supplier_payments_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_payments`
--

LOCK TABLES `supplier_payments` WRITE;
/*!40000 ALTER TABLE `supplier_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `due_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'ঢাকা ট্রেডিং কোং','01711000001',NULL,'ঢাকা',0.00,'2026-05-10 22:37:37','2026-05-10 22:37:37'),(2,'চট্টগ্রাম সাপ্লাই','01811000002',NULL,'চট্টগ্রাম',0.00,'2026-05-10 22:37:37','2026-05-10 22:37:37'),(3,'রহিম ব্রাদার্স','01911000003',NULL,'সিলেট',0.00,'2026-05-10 22:37:37','2026-05-10 22:37:37'),(4,'ময়মনসিংহ রাইস মিল','01711000001',NULL,'ময়মনসিংহ',0.00,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(5,'কুমিল্লা অটো রাইস মিল','01811000002',NULL,'কুমিল্লা',269600.00,'2026-05-10 23:01:09','2026-05-21 20:27:24'),(6,'দিনাজপুর চাল সাপ্লাই','01911000003',NULL,'দিনাজপুর',0.00,'2026-05-10 23:01:09','2026-05-10 23:01:09'),(7,'নওগাঁ এগ্রো ট্রেডার্স','01611000004',NULL,'নওগাঁ',0.00,'2026-05-10 23:01:09','2026-05-10 23:01:09');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unit_types`
--

DROP TABLE IF EXISTS `unit_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `short` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unit_types`
--

LOCK TABLES `unit_types` WRITE;
/*!40000 ALTER TABLE `unit_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `unit_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','staff') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staff',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','admin@inventory.com','admin',NULL,'$2y$12$ldaWm4R.wpm.5DvQ5XSrg.dE0kpuk.U57S74cRwVvN4mK/E1rimsy','9fUlHAjXKAamMfVsb7lfb4n7t3yCfZgBJ6IE5Ezlt52WxfgeW4rASKJx3IJZ','2026-05-10 22:37:37','2026-05-18 00:54:55'),(2,'Hasan','hasan@inventory.com','staff',NULL,'$2y$12$eR6wjC5WtrolQPzubu.M6uVeHWPvL9Gg1fRwBIG8gMsjG478qg/Q6',NULL,'2026-05-10 22:37:37','2026-05-18 00:54:55');
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

-- Dump completed on 2026-05-30  7:32:15
