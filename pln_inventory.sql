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


-- Dumping database structure for pln_inventory
DROP DATABASE IF EXISTS `pln_inventory`;
CREATE DATABASE IF NOT EXISTS `pln_inventory` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `pln_inventory`;

-- Dumping structure for table pln_inventory.items
DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_barang` varchar(100) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `stok` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pln_inventory.items: ~12 rows (approximately)
DELETE FROM `items`;
INSERT INTO `items` (`id`, `nama_barang`, `kategori`, `stok`) VALUES
	(1, 'Kabel Optik 10m', 'Material', 72),
	(2, 'Tang Kombinasi', 'Tools', 10),
	(3, 'Isolasi Listrik', 'Consumables', 0),
	(4, 'S-Clamp', 'Aksesoris & Lainnya', 10),
	(5, 'Hook Clamp', 'Aksesoris & Lainnya', 11),
	(6, 'Splitter', 'Aksesoris & Lainnya', 10),
	(7, 'Adaptor ONT', 'Perangkat Aktif (Router/Switch)', 10),
	(8, 'Paku Clamp', 'Aksesoris & Lainnya', 100),
	(9, 'Konektor', 'Aksesoris & Lainnya', 100),
	(10, 'Patchcord 100m', 'Kabel & Jaringan', 10),
	(11, 'Patchcord 200m', 'Kabel & Jaringan', 10),
	(12, 'Patchcord 200m', 'Kabel & Jaringan', 10);

-- Dumping structure for table pln_inventory.requests
DROP TABLE IF EXISTS `requests`;
CREATE TABLE IF NOT EXISTS `requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `item_id` int DEFAULT NULL,
  `jumlah` int DEFAULT NULL,
  `keterangan` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `tanggal` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pln_inventory.requests: ~6 rows (approximately)
DELETE FROM `requests`;
INSERT INTO `requests` (`id`, `user_id`, `item_id`, `jumlah`, `keterangan`, `status`, `tanggal`) VALUES
	(1, 3, 3, 1, '', 'approved', '2026-04-06 19:08:49'),
	(2, 3, 3, 1, '', 'approved', '2026-04-06 19:12:59'),
	(3, 3, 3, 1, '', 'approved', '2026-04-06 22:52:52'),
	(4, 3, 3, 1, '', 'approved', '2026-04-06 22:52:59'),
	(5, 3, 3, 1, 'dvd', 'approved', '2026-04-06 22:58:52'),
	(6, 3, 1, 9, 'fghj', 'approved', '2026-04-06 23:02:19');

-- Dumping structure for table pln_inventory.returns
DROP TABLE IF EXISTS `returns`;
CREATE TABLE IF NOT EXISTS `returns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `item_id` int NOT NULL,
  `jumlah` int NOT NULL,
  `kondisi` enum('Baik','Rusak') NOT NULL,
  `keterangan` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `tanggal` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pln_inventory.returns: ~6 rows (approximately)
DELETE FROM `returns`;
INSERT INTO `returns` (`id`, `user_id`, `item_id`, `jumlah`, `kondisi`, `keterangan`, `status`, `tanggal`) VALUES
	(1, 3, 1, 1, 'Baik', 'sisa tarikan tadi\r\n', 'approved', '2026-04-06 12:15:03'),
	(2, 3, 1, 10, 'Rusak', 'sisa kabel lama', 'approved', '2026-04-06 12:15:25'),
	(3, 3, 1, 10, 'Baik', 'sisa', 'approved', '2026-04-06 12:18:21'),
	(4, 3, 1, 10, 'Baik', 'snvindsi', 'approved', '2026-04-06 12:18:45'),
	(5, 3, 1, 10, 'Baik', '10', 'approved', '2026-04-06 12:21:44'),
	(6, 3, 5, 1, 'Baik', 'sisa', 'approved', '2026-04-06 16:27:27');

-- Dumping structure for table pln_inventory.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table pln_inventory.users: ~2 rows (approximately)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
	(1, 'admin_pln', 'admin123', 'admin'),
	(2, 'pegawai_gudang', 'user123', 'user'),
	(3, 'rama', 'rama123', 'user');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
