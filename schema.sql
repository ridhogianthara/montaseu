-- SQL Dump untuk Database Montaseu Studio Web Absensi
-- Digunakan untuk MySQL / MariaDB via phpMyAdmin (nama database: montaseu_db)

CREATE DATABASE IF NOT EXISTS `montaseu_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `montaseu_db`;

-- 1. Struktur Tabel Users (Pengguna Admin & Karyawan)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) UNIQUE NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'karyawan',
  `job_title` VARCHAR(255) NOT NULL DEFAULT 'Staff',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data Akun Login Default:
-- Admin: username 'admin' / pass 'admin123'
-- Karyawan: username 'karyawan' / pass 'user123'
INSERT INTO `users` (`id`, `username`, `name`, `email`, `password`, `role`, `job_title`) VALUES
(1, 'admin', 'Admin Montaseu', 'admin@montaseu.com', '$2y$10$LQjxGERUqacFkEtQzagnGuAcWcl5UlLs2R.cexIYyHf9.BNeSQMT.', 'admin', 'Studio Manager / Admin'),
(2, 'karyawan', 'Karyawan Montaseu', 'karyawan@montaseu.com', '$2y$10$M01I42o.RgTsFWxaLSOki.4DJFfj3gYlVFQouKEw9NBZXqdDkipMO', 'karyawan', 'Staff Interior Designer')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- 2. Struktur Tabel Attendances (Presensi Foto & Location Tracking GPS)
CREATE TABLE IF NOT EXISTS `attendances` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `clock_in_time` DATETIME DEFAULT NULL,
  `clock_in_photo` TEXT DEFAULT NULL,
  `clock_in_lat` DOUBLE DEFAULT NULL,
  `clock_in_lng` DOUBLE DEFAULT NULL,
  `clock_in_address` TEXT DEFAULT NULL,
  `clock_in_status` VARCHAR(50) DEFAULT 'On Time',
  `clock_in_notes` TEXT DEFAULT NULL,
  `clock_out_time` DATETIME DEFAULT NULL,
  `clock_out_photo` TEXT DEFAULT NULL,
  `clock_out_lat` DOUBLE DEFAULT NULL,
  `clock_out_lng` DOUBLE DEFAULT NULL,
  `clock_out_address` TEXT DEFAULT NULL,
  `clock_out_notes` TEXT DEFAULT NULL,
  `work_duration` VARCHAR(100) DEFAULT NULL,
  `location_type` VARCHAR(100) DEFAULT 'Office',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Struktur Tabel Settings (Pengaturan Studio & Koordinat GPS Kantor)
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(100) PRIMARY KEY,
  `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Data Pengaturan Kantor Default:
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('company_name', 'Montaseu Studio'),
('office_address', 'Jl. Senopati No. 45, Kebayoran Baru, Jakarta Selatan'),
('office_lat', '-6.230588'),
('office_lng', '106.808018'),
('office_radius', '500'),
('work_start', '08:30'),
('work_end', '17:30')
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;
