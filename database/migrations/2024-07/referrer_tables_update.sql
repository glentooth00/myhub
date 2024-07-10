-- Adminer 4.8.1 MySQL 8.0.30 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';


CREATE TABLE IF NOT EXISTS `ch_beneficiaries_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `ch_beneficiaries_types` (
  `id`, `name`, `deleted_at`
) VALUES
(1, 'Type 1', NULL)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `deleted_at` = VALUES(`deleted_at`);


CREATE TABLE IF NOT EXISTS `ch_beneficiaries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type_id` int NOT NULL,
  `referrer_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_type_id` (`type_id`),
  CONSTRAINT `fk_type_id` FOREIGN KEY (`type_id`) REFERENCES `ch_beneficiaries_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `ch_beneficiaries` (
  `id`, `name`, `type_id`, `referrer_id`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`
) VALUES
(1, 'Smith Johnson', 1, 12, '2024-06-18 21:04:03', NULL, '2024-06-25 10:28:59', 'markdev', NULL),
(2, 'John Doe', 1, 13, '2024-06-24 01:31:53', 'markdev', NULL, NULL, NULL),
(3, 'Cage Nic', 1, 12, '2024-06-25 02:51:20', 'markdev', NULL, NULL, NULL),
(4, 'Beneficiary 2', 1, 12, '2024-06-25 14:36:15', 'markdev', '2024-06-25 14:36:21', 'markdev', NULL)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `type_id` = VALUES(`type_id`),
  `referrer_id` = VALUES(`referrer_id`),
  `created_at` = VALUES(`created_at`),
  `created_by` = VALUES(`created_by`),
  `updated_at` = VALUES(`updated_at`),
  `updated_by` = VALUES(`updated_by`),
  `deleted_at` = VALUES(`deleted_at`);


CREATE TABLE IF NOT EXISTS `ch_referrer_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- Check if the column exists
SELECT COUNT(*) INTO @exist FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'ch_referrer_types' AND COLUMN_NAME = 'deleted_at';

-- If the column does not exist, add it
SET @sql = IF(@exist = 0, 'ALTER TABLE `ch_referrer_types` ADD `deleted_at` datetime NULL', 'SELECT "Column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


INSERT INTO `ch_referrer_types` (`id`, `name`, `description`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`) VALUES
(8, 'Type 1', 'Referrer Type 8', '2024-06-20 09:23:39', '_system_', NULL, '', NULL),
(9, 'Type 2', 'Referrer Type 9', '2024-06-20 06:47:36', 'markdev', NULL, '', NULL),
(10, 'Type 6', 'Referrer Type 10', '2024-06-21 05:02:54', 'markdev', '2024-06-21 13:28:30', 'markdev', NULL),
(11, 'Type 8', 'Referrer Type 11', '2024-06-21 05:17:38', 'markdev', '2024-06-21 13:27:22', 'markdev', NULL)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `created_at` = VALUES(`created_at`),
  `created_by` = VALUES(`created_by`),
  `updated_at` = VALUES(`updated_at`),
  `updated_by` = VALUES(`updated_by`),
  `deleted_at` = VALUES(`deleted_at`);


CREATE TABLE IF NOT EXISTS `ch_referrers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `referrer_id` varchar(20) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `notes` text,
  `type_id` varchar(20) NOT NULL DEFAULT '99',
  `user_id` varchar(20) DEFAULT NULL,
  `client_id` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `referrer_id` (`referrer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `ch_referrers` (
  `id`, `referrer_id`, `name`, `id_number`, `phone_number`, `email`, `notes`, `type_id`, `user_id`, `client_id`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`
) VALUES
(12, 'k09fbc92', 'Katty', '1000012', '09223451256', 'katth@gmail.com', NULL, '10', NULL, NULL, '2024-06-14 11:14:35', 'markdev', NULL, NULL, NULL, NULL),
(13, 'a11b5af6', 'Adam', '1000013', '27381249522', 'adam@gmail.com', NULL, '11', NULL, NULL, '2024-06-14 11:47:33', 'markdev', NULL, NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE
  `referrer_id` = VALUES(`referrer_id`),
  `name` = VALUES(`name`),
  `id_number` = VALUES(`id_number`),
  `phone_number` = VALUES(`phone_number`),
  `email` = VALUES(`email`),
  `notes` = VALUES(`notes`),
  `type_id` = VALUES(`type_id`),
  `user_id` = VALUES(`user_id`),
  `client_id` = VALUES(`client_id`),
  `created_at` = VALUES(`created_at`),
  `created_by` = VALUES(`created_by`),
  `updated_at` = VALUES(`updated_at`),
  `updated_by` = VALUES(`updated_by`),
  `deleted_at` = VALUES(`deleted_at`),
  `deleted_by` = VALUES(`deleted_by`);


CREATE TABLE IF NOT EXISTS `ch_revenue_model_beneficiaries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `revenue_model_id` int NOT NULL,
  `beneficiary_id` int NOT NULL,
  `revenue_share` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_revenue_model_id` (`revenue_model_id`),
  KEY `fk_beneficiary_id` (`beneficiary_id`),
  CONSTRAINT `fk_beneficiary_id` FOREIGN KEY (`beneficiary_id`) REFERENCES `ch_beneficiaries` (`id`),
  CONSTRAINT `fk_revenue_model_id` FOREIGN KEY (`revenue_model_id`) REFERENCES `ch_revenue_models` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `ch_revenue_model_beneficiaries` (
  `id`, `revenue_model_id`, `beneficiary_id`, `revenue_share`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`
) VALUES
(1, 1, 2, 25, '2024-06-25 10:44:34', NULL, NULL, NULL, NULL),
(2, 2, 4, 45, '2024-06-25 14:56:41', 'markdev', NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE
  `revenue_model_id` = VALUES(`revenue_model_id`),
  `beneficiary_id` = VALUES(`beneficiary_id`),
  `revenue_share` = VALUES(`revenue_share`),
  `created_at` = VALUES(`created_at`),
  `created_by` = VALUES(`created_by`),
  `updated_at` = VALUES(`updated_at`),
  `updated_by` = VALUES(`updated_by`),
  `deleted_at` = VALUES(`deleted_at`);


CREATE TABLE IF NOT EXISTS `ch_revenue_model_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `ch_revenue_model_types` (
  `id`, `name`, `description`, `created_at`, `created_by`, `updated_at`, `updated_by`
) VALUES
(1, 'Type 1', 'Revenue Model 1', '2024-06-19 10:47:04', '_system_', NULL, ''),
(2, 'Type 2', 'Revenue Model 2', '2024-06-20 05:51:00', 'markdev', '2024-06-20 14:00:37', 'markdev'),
(3, 'Type 3', 'Revenue Model 3', '2024-06-20 06:57:36', 'markdev', NULL, ''),
(4, 'Type 4', 'Revenue Model 4', '2024-06-21 05:19:22', 'markdev', NULL, '')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `created_at` = VALUES(`created_at`),
  `created_by` = VALUES(`created_by`),
  `updated_at` = VALUES(`updated_at`),
  `updated_by` = VALUES(`updated_by`);


CREATE TABLE IF NOT EXISTS `ch_revenue_model_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `model_type_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_template_model_id` (`model_type_id`),
  CONSTRAINT `fk_template_model_id` FOREIGN KEY (`model_type_id`) REFERENCES `ch_revenue_model_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `ch_revenue_model_templates` (
  `id`, `name`, `model_type_id`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`
) VALUES
(1, 'Template 1', 1, '2024-06-19 18:47:41', NULL, NULL, NULL, NULL),
(2, 'Template 2', 1, '2024-06-25 03:34:14', 'markdev', '2024-06-25 03:50:41', 'markdev', NULL),
(3, 'Template 3', 1, '2024-06-25 14:36:47', 'markdev', '2024-06-25 14:36:53', 'markdev', NULL)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `model_type_id` = VALUES(`model_type_id`),
  `created_at` = VALUES(`created_at`),
  `created_by` = VALUES(`created_by`),
  `updated_at` = VALUES(`updated_at`),
  `updated_by` = VALUES(`updated_by`),
  `deleted_at` = VALUES(`deleted_at`);


CREATE TABLE IF NOT EXISTS `ch_revenue_models` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type_id` int NOT NULL,
  `client_id` int NOT NULL,
  `referrer_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_revenue_type_id` (`type_id`),
  KEY `fk_revenue_client_id` (`client_id`),
  KEY `fk_revenue_referrer_id` (`referrer_id`),
  CONSTRAINT `fk_revenue_client_id` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `fk_revenue_referrer_id` FOREIGN KEY (`referrer_id`) REFERENCES `ch_referrers` (`id`),
  CONSTRAINT `fk_revenue_type_id` FOREIGN KEY (`type_id`) REFERENCES `ch_beneficiaries_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `ch_revenue_models` (
  `id`, `name`, `type_id`, `client_id`, `referrer_id`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`
) VALUES
(1, 'Revenue Model 1', 1, 'neelsdev', 12, '2024-06-19 17:29:26', NULL, NULL, NULL, NULL),
(2, 'Revenue Model 2', 1, '99d4d4d3', 13, '2024-06-25 02:56:19', 'markdev', '2024-06-25 03:51:17', 'markdev', NULL),
(3, 'Revenue Model 3', 1, 'ac6cb22d', 13, '2024-06-25 14:36:33', 'markdev', '2024-06-25 14:36:37', 'markdev', NULL)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `type_id` = VALUES(`type_id`),
  `client_id` = VALUES(`client_id`),
  `referrer_id` = VALUES(`referrer_id`),
  `created_at` = VALUES(`created_at`),
  `created_by` = VALUES(`created_by`),
  `updated_at` = VALUES(`updated_at`),
  `updated_by` = VALUES(`updated_by`),
  `deleted_at` = VALUES(`deleted_at`);


-- 2024-06-25 13:33:29