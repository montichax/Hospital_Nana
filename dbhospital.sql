-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for db_hospital
CREATE DATABASE IF NOT EXISTS `db_hospital` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `db_hospital`;

-- Dumping structure for table db_hospital.banners
CREATE TABLE IF NOT EXISTS `banners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` text,
  `image_name` varchar(500) DEFAULT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.banners: ~2 rows (approximately)
INSERT INTO `banners` (`id`, `department_id`, `title`, `subtitle`, `image_name`, `link_url`, `sort_order`, `is_active`, `created_at`) VALUES
	(1, NULL, 'พัฒนาคุณภาพอย่างต่อเนื่อง', '', '1782285999_banner___________________________________________.png', NULL, 1, 1, '2026-06-24 07:26:39'),
	(3, 1, 'เทสรวมกุมารเวชกรรม', '', '1783655135_banner___________________________________________.png', NULL, 1, 1, '2026-07-10 03:45:35');

-- Dumping structure for table db_hospital.departments
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.departments: ~21 rows (approximately)
INSERT INTO `departments` (`id`, `name`, `link_url`) VALUES
	(1, 'กุมารเวชกรรม', 'http://localhost/PAKCHONG_NANA_HOSPITAL/dept_pediatrics.php'),
	(2, 'ตรวจรักษาพิเศษ', 'dept_special_clinic.php'),
	(3, 'ผู้คลอด', 'dept_postpartum.php'),
	(4, 'พิเศษชั้น4', 'dept_private_floor4.php'),
	(5, 'พิเศษชั้น5', 'dept_private_floor5.php'),
	(6, 'รักษ์จิต', 'dept_psychiatry.php'),
	(7, 'วิสัญญี', 'dept_anesthesia.php'),
	(8, 'ศัลยกรรม', 'dept_surgery.php'),
	(9, 'ศัลยกรรมกระดูกและข้อ', 'dept_orthopedics.php'),
	(10, 'สูติ-นรีเวช', 'dept_obgyn.php'),
	(11, 'ห้องผ่าตัด', 'dept_operating_room.php'),
	(12, 'อายุรกรรมชาย', 'dept_medicine_male.php'),
	(13, 'อายุรกรรมหญิง', 'dept_medicine_female.php'),
	(14, 'อุบัติเหตุ-ฉุกเฉิน', 'dept_emergency.php'),
	(15, 'อุรเวชช์', 'dept_pulmonary.php'),
	(16, 'IC', 'dept_ic.php'),
	(17, 'MICU', 'dept_micu.php'),
	(18, 'NICU', 'dept_nicu.php'),
	(19, 'OPD', 'dept_opd.php'),
	(20, 'SICU', 'dept_sicu.php'),
	(21, 'Stroke Unit', 'dept_stroke_unit.php');

-- Dumping structure for table db_hospital.department_contents
CREATE TABLE IF NOT EXISTS `department_contents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_id` int DEFAULT NULL,
  `section` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text,
  `file_name` text,
  `link_url` varchar(500) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_department_section` (`department_id`,`section`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.department_contents: ~14 rows (approximately)
INSERT INTO `department_contents` (`id`, `department_id`, `section`, `title`, `content`, `file_name`, `link_url`, `sort_order`, `created_at`) VALUES
	(19, 3, 'knowledge', 'แนะนำหน่วยงานห้องคลอด', '', '1783322802_0_dept_content________________________________________________________________.mp4', NULL, 1, '2026-07-06 07:26:42'),
	(21, 3, 'personnel', 'เจ้าหน้าที่', '', '1783325305_0_dept_content_PDF_ID_card_LR___________.pdf', NULL, 2, '2026-07-06 08:08:25');

-- Dumping structure for table db_hospital.news
CREATE TABLE IF NOT EXISTS `news` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text,
  `image_name` varchar(255) DEFAULT 'default.jpg',
  `is_new` tinyint(1) DEFAULT '1',
  `link_url` varchar(255) DEFAULT NULL,
  `created_at` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.news: ~10 rows (approximately)

-- Dumping structure for table db_hospital.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('main','dept') NOT NULL DEFAULT 'dept',
  `department_id` int DEFAULT NULL,
  `display_name` varchar(128) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.users: ~4 rows (approximately)
INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `department_id`, `display_name`, `created_at`) VALUES
	(1, 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'main', NULL, 'ผู้ดูแลระบบหลัก', '2026-07-02 03:47:35'),
	(2, 'กุมารเวชกรรม', 'ee79976c9380d5e337fc1c095ece8c8f22f91f306ceeb161fa51fecede2c4ba1', 'dept', 1, 'k', '2026-07-02 06:26:51'),
	(3, 'ตรวจรักษาพิเศษ', 'ef797c8118f02dfb649607dd5d3f8c7623048c9c063d532cc95c5ed7a898a64f', 'dept', 2, '้้hh', '2026-07-02 06:28:40'),
	(4, 'ผู้คลอด', '1f3ce40415a2081fa3eee75fc39fff8e56c22270d1a978a7249b592dcebd20b4', 'dept', 3, 'gh', '2026-07-02 06:40:11');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
