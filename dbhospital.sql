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
  `title` varchar(255) NOT NULL,
  `subtitle` text,
  `image_name` varchar(500) DEFAULT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.banners: ~0 rows (approximately)
INSERT INTO `banners` (`id`, `title`, `subtitle`, `image_name`, `link_url`, `sort_order`, `is_active`, `created_at`) VALUES
	(1, 'พัฒนาคุณภาพอย่างต่อเนื่อง', '', '1782285999_banner___________________________________________.png', NULL, 1, 1, '2026-06-24 07:26:39');

-- Dumping structure for table db_hospital.departments
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.departments: ~21 rows (approximately)
INSERT INTO `departments` (`id`, `name`, `link_url`) VALUES
	(1, 'กุมารเวช', 'dept_pediatrics.php'),
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
  `department_id` int NOT NULL,
  `section` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text,
  `file_name` text,
  `link_url` varchar(500) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_department_section` (`department_id`,`section`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.department_contents: ~14 rows (approximately)
INSERT INTO `department_contents` (`id`, `department_id`, `section`, `title`, `content`, `file_name`, `link_url`, `sort_order`, `created_at`) VALUES
	(1, 1, 'knowledge', 'แนะนำหอผู้ป่วยกุมารเวชกรรม', '', '1782272180_0_dept_content_______________________________________________________________________________.MP4', NULL, 1, '2026-06-19 04:48:44'),
	(4, 1, 'knowledge', 'กำหนดแนวทางลดข้อร้องเรียน', '', '1781851229_dept_content____________________________________________________________________________.pdf', 'https://drive.google.com/drive/folders/100m-vsj3rdw-SrBSzZSlE4VycbyOanxI', 2, '2026-06-19 06:40:29'),
	(5, 1, 'knowledge', 'บอร์ดให้ความรู้ หอผู้ป่วยกุมานเวชกรรม ชักจากไข้สูง', '', '1781851836_dept_content___________________________________________________________________________________________________________________________________________________.pdf', NULL, 3, '2026-06-19 06:50:36'),
	(6, 1, 'knowledge', 'บอร์ดให้ความรู้ หอผู้ป่วยกุมารเวชกรรม', '', '1781851981_dept_content______________________________________________________________________________________________________________.pdf', NULL, 4, '2026-06-19 06:53:01'),
	(8, 1, 'personnel', 'นางสาวจุฑาทิพย์ ถนอมทรัพย์', 'ผู้ช่วยพยาบาล', '1781853231_dept_content_IMG_4124.JPG', NULL, 1, '2026-06-19 07:11:57'),
	(9, 1, 'service_profile', 'SP_หอผู้ป่วยกุมารเวชกรรมปีงบประมาณ2568', '', '1782275019_0_dept_content_SP.pdf', NULL, 1, '2026-06-22 06:44:52'),
	(10, 1, 'structure', 'โครงสร้างการบริหารงานตึกเด็ก', '', '1782118135_dept_content______________________________________________________________________________________.pdf', NULL, 1, '2026-06-22 08:48:55'),
	(11, 1, 'knowledge', 'รายชื่อคณะกรรมการ หน่วยงาน หอผู้ป่วยเด็ก', '', '1782272501_0_dept_content___________________________________________________________________________________________.pdf', NULL, 2, '2026-06-22 08:49:31'),
	(12, 1, 'structure', 'รายชื่อคณะกรรมการ หน่อยงาน หอผู้ป่วยเด็ก', '', '1782118335_dept_content_____________________________________________________________________________________________________________________.docx', NULL, 2, '2026-06-22 08:52:15'),
	(13, 1, 'wi', 'CNPG AGE', '', '1782118432_dept_content_CNPG_AGE.pdf', NULL, 1, '2026-06-22 08:53:52'),
	(14, 1, 'wi', 'CNPG febrile seizure', '', '1782118477_dept_content_CNPG_febrile_seizure.pdf', NULL, 2, '2026-06-22 08:54:37'),
	(15, 1, 'wi', 'CNPG-Thalassemia ในเด็ก', '', '1782118518_dept_content_CNPG-_Thalassemia___________________.pdf', NULL, 3, '2026-06-22 08:55:18'),
	(16, 1, 'knowledge', 'WI การพยาบาลป้องกันพลัดตกหกล้ม', '', '1782272372_0_dept_content________________________________________________________________.pdf', NULL, 4, '2026-06-22 08:56:08'),
	(17, 1, 'wi', 'WI การพยาบาลป้องกันพลัดตกหกล้ม', '', '1782272328_0_dept_content________________________________________________________________.pdf', NULL, 4, '2026-06-22 08:57:40');

-- Dumping structure for table db_hospital.events
CREATE TABLE IF NOT EXISTS `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text,
  `event_date` date NOT NULL,
  `image_name` varchar(255) DEFAULT 'default.jpg',
  `link_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.events: ~0 rows (approximately)

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.news: ~6 rows (approximately)
INSERT INTO `news` (`id`, `title`, `content`, `image_name`, `is_new`, `link_url`, `created_at`) VALUES
	(4, 'แจ้งกำหนดการประชุมวิชาการประจำปีกลุ่มงานการพยาบาล', '', 'default.jpg', 0, NULL, '2026-06-16'),
	(5, 'ผลการประเมินคุณภาพการพยาบาล ประจำไตรมาส 1/2569', '', 'default.jpg', 0, NULL, '2026-06-16'),
	(6, 'โครงการอบรมการช่วยฟื้นคืนชีพขั้นพื้นฐาน (BLS) รุ่นที่ 2', '', 'default.jpg', 0, NULL, '2026-06-16'),
	(7, ' แนวทางปฏิบัติการดูแลผู้ป่วย Sepsis ฉบับปรับปรุง 2569', '', 'default.jpg', 0, NULL, '2026-06-16'),
	(8, 'ประกาศผลการคัดเลือกพยาบาลวิชาชีพ (สัญญาจ้าง)', '', 'default.jpg', 1, NULL, '2026-06-16'),
	(9, 'พิธีรับพระราชทานเครื่องราชอิสริยาภรณ์ชั้นสายสพาย ประจำปี 2567', 'test', '1782288519_0_news_7.png', 1, NULL, '2026-03-17');

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_hospital.users: ~1 rows (approximately)
INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `department_id`, `display_name`, `created_at`) VALUES
	(1, 'admin', '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9', 'main', NULL, 'ผู้ดูแลระบบหลัก', '2026-07-02 02:49:22');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

CREATE TABLE `home_slides` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`image_name` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`sort_order` INT NOT NULL DEFAULT '1',
	`is_active` TINYINT(1) NULL DEFAULT '1',
	`created_at` TIMESTAMP NULL DEFAULT (now()),
	PRIMARY KEY (`id`) USING BTREE
)