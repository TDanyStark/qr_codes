-- --------------------------------------------------
-- Schema dump for qr_codes system
-- Target: MySQL 8.0+ / MariaDB 10.5+
-- Encoding: utf8mb4
-- --------------------------------------------------

/*
  Uso rápido:
  1. Crear base e importar:
     mysql -u usuario -p < qr_codes.sql
  2. O dentro de mysql:
     SOURCE /ruta/al/archivo/qr_codes.sql;

  Ajusta si no quieres DROP DATABASE.
*/

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_notes = 0;

-- Opcional: eliminar base existente
DROP DATABASE IF EXISTS `qr_codes`;
CREATE DATABASE `qr_codes` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `qr_codes`;

-- --------------------------------------------------
-- Tabla: users
-- --------------------------------------------------
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `rol` ENUM('admin','user') NOT NULL DEFAULT 'user',
  `codigo` VARCHAR(255) NULL COMMENT 'Código de autenticación enviado por email',
  `fecha_expedicion` DATE NULL COMMENT 'Fecha en que fue generado el código',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Tabla: qrcodes
-- --------------------------------------------------
CREATE TABLE `qrcodes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(64) NOT NULL,
  `owner_user_id` BIGINT UNSIGNED NOT NULL,
  `foreground` VARCHAR(7) NULL COMMENT 'HEX color for QR foreground, e.g. #000000',
  `background` VARCHAR(7) NULL COMMENT 'HEX color for QR background, e.g. #FFFFFF',
  `target_url` TEXT NOT NULL,
  `name` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qrcodes_token` (`token`),
  KEY `idx_qrcodes_owner_user_id` (`owner_user_id`),
  CONSTRAINT `fk_qrcodes_user` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Tabla: scans
-- --------------------------------------------------
CREATE TABLE `scans` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `qrcode_id` BIGINT UNSIGNED NOT NULL,
  `scanned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `country` VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_scans_qrcode_id` (`qrcode_id`),
  KEY `idx_scans_scanned_at` (`scanned_at`),
  CONSTRAINT `fk_scans_qrcode` FOREIGN KEY (`qrcode_id`) REFERENCES `qrcodes` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Tabla: qr_subscriptions (usuarios suscritos a QR)
-- --------------------------------------------------
CREATE TABLE `qr_subscriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `qrcode_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qr_subscriptions_qrcode_user` (`qrcode_id`, `user_id`),
  KEY `idx_qr_subscriptions_qrcode_id` (`qrcode_id`),
  KEY `idx_qr_subscriptions_user_id` (`user_id`),
  CONSTRAINT `fk_qr_subscriptions_qrcode` FOREIGN KEY (`qrcode_id`) REFERENCES `qrcodes` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_qr_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------
-- Tabla: report_settings (configuracion de reportes)
-- --------------------------------------------------
CREATE TABLE `report_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_type` ENUM('monthly','weekly') NOT NULL DEFAULT 'monthly',
  `day_of_month` TINYINT UNSIGNED NULL,
  `day_of_week` TINYINT UNSIGNED NULL COMMENT '1=Monday ... 7=Sunday',
  `time_of_day` TIME NOT NULL DEFAULT '08:00:00',
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'UTC',
  `looker_url` TEXT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_run_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
SET sql_notes = 1;

-- --------------------------------------------------
-- (Opcional) Usuario admin inicial - QUITA el comentario si quieres crearlo.
-- Cambia el email antes de ejecutar en producción.
-- --------------------------------------------------
/*
INSERT INTO users (name, email, rol) VALUES ('Admin', 'admin@tu-dominio.com', 'admin');
*/

-- --------------------------------------------------
-- (Opcional) Configuracion inicial de reportes
-- --------------------------------------------------
/*
INSERT INTO report_settings (schedule_type, day_of_month, day_of_week, time_of_day, timezone, looker_url, active)
VALUES ('monthly', 1, NULL, '08:00:00', 'UTC', NULL, 1);
*/

-- Fin del script
