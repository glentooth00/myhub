-- Adminer 4.8.1 MySQL 5.7.33 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `banks`;
CREATE TABLE `banks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(65) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `cities`;
CREATE TABLE `cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` varchar(20) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `personal_email` varchar(255) DEFAULT NULL,
  `bank` varchar(30) DEFAULT NULL,
  `accountant` varchar(30) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `trader_id` varchar(20) DEFAULT NULL,
  `trading_capital` int(11) DEFAULT NULL,
  `sda_mandate` int(11) DEFAULT NULL,
  `fia_mandate` int(11) DEFAULT NULL,
  `fia_approved` int(11) DEFAULT NULL,
  `sda_used` int(11) DEFAULT NULL,
  `fia_used` int(11) DEFAULT NULL,
  `inhouse_referrer_15_percent` varchar(20) DEFAULT NULL,
  `third_party_referrer` varchar(255) DEFAULT NULL,
  `third_party_profit_percent` decimal(5,2) DEFAULT NULL,
  `fx_intermediary` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `suburb` varchar(50) DEFAULT NULL,
  `city` varchar(30) DEFAULT NULL,
  `province` varchar(30) DEFAULT NULL,
  `country` varchar(30) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `ovex_email` varchar(255) DEFAULT NULL,
  `mercantile_name` varchar(50) DEFAULT NULL,
  `bp_number` varchar(20) DEFAULT NULL,
  `cif_number` varchar(20) DEFAULT NULL,
  `fia_pending` int(11) DEFAULT NULL,
  `fia_declined` int(11) DEFAULT NULL,
  `ovex_ref` varchar(20) DEFAULT NULL,
  `capitec_id` varchar(20) DEFAULT NULL,
  `id_number` varchar(20) DEFAULT NULL,
  `tax_number` varchar(20) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `spare_1` varchar(255) DEFAULT NULL,
  `spare_2` varchar(255) DEFAULT NULL,
  `spare_3` varchar(255) DEFAULT NULL,
  `spare_4` varchar(255) DEFAULT NULL,
  `spare_5` varchar(255) DEFAULT NULL,
  `next_years_sda_mandate` int(11) DEFAULT NULL,
  `next_years_fia_mandate` int(11) DEFAULT NULL,
  `last_years_statement` varchar(255) DEFAULT NULL,
  `statement_file` varchar(255) DEFAULT NULL,
  `statement_pdf` varchar(255) DEFAULT NULL,
  `last_action` varchar(255) DEFAULT NULL,
  `action_at` datetime DEFAULT NULL,
  `action_by` varchar(30) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(30) DEFAULT NULL,
  `settings` text,
  `notes` text,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(20) DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `sync_by` varchar(20) DEFAULT NULL,
  `sync_from` enum('local','remote','both') DEFAULT NULL,
  `sync_type` enum('new','update','merge') DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `countries`;
CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `intermediaries`;
CREATE TABLE `intermediaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(36) NOT NULL,
  `type` varchar(20) DEFAULT 'Forex',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `provinces`;
CREATE TABLE `provinces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `referrers`;
CREATE TABLE `referrers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `referrer_id` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `profit_percent` decimal(5,2) DEFAULT NULL,
  `inhouse_user_uid` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `home` varchar(255) NOT NULL,
  `permissions` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `suburbs`;
CREATE TABLE `suburbs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(36) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `tasks`;
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` varchar(36) DEFAULT NULL,
  `job_desc` varchar(255) DEFAULT NULL,
  `status` enum('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `retries` int(11) NOT NULL DEFAULT '0',
  `result` varchar(255) DEFAULT NULL,
  `progress` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '0 - 100 %',
  `progress_message` varchar(255) DEFAULT NULL,
  `max_retries` int(11) NOT NULL DEFAULT '0',
  `expires_after` int(11) DEFAULT '60' COMMENT 'Minutes',
  `expires_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_cron_',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `tccs`;
CREATE TABLE `tccs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tcc_id` varchar(50) NOT NULL,
  `client_id` varchar(20) NOT NULL,
  `status` enum('Pending','Awaiting Docs','Approved','Declined','Expired') NOT NULL DEFAULT 'Pending',
  `application_date` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `amount_cleared` decimal(15,2) DEFAULT NULL,
  `amount_reserved` decimal(15,2) DEFAULT NULL,
  `rollover` decimal(15,2) DEFAULT NULL,
  `amount_cleared_net` decimal(15,2) DEFAULT NULL,
  `amount_used` decimal(15,2) DEFAULT NULL,
  `amount_remaining` decimal(15,2) DEFAULT NULL,
  `amount_available` decimal(15,2) DEFAULT NULL,
  `expired` int(11) DEFAULT NULL,
  `tcc_pin` varchar(20) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `allocated_trades` text,
  `tax_case_no` varchar(20) DEFAULT NULL,
  `tax_cert_pdf` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(20) DEFAULT NULL,
  `sync_at` datetime DEFAULT NULL,
  `sync_by` varchar(20) DEFAULT NULL,
  `sync_from` enum('local','remote') DEFAULT NULL,
  `sync_type` enum('new','update') DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tcc_id` (`tcc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `trades`;
CREATE TABLE `trades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trade_id` varchar(20) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `forex` enum('Capitec','Mercantile') DEFAULT NULL,
  `forex_reference` varchar(20) DEFAULT NULL COMMENT 'e.g. Capitec Deal Ref',
  `otc` enum('OVEX','VALR') DEFAULT NULL,
  `otc_reference` varchar(20) DEFAULT NULL,
  `client_id` varchar(20) DEFAULT NULL,
  `sda_fia` varchar(10) DEFAULT NULL,
  `zar_sent` decimal(15,2) DEFAULT NULL,
  `usd_bought` decimal(15,2) DEFAULT NULL,
  `trade_fee` decimal(5,2) DEFAULT NULL,
  `forex_rate` decimal(6,3) DEFAULT NULL,
  `zar_profit` decimal(15,2) DEFAULT NULL,
  `percent_return` decimal(5,2) DEFAULT NULL,
  `fee_category_percent_profit` decimal(5,2) DEFAULT NULL,
  `recon_id1` varchar(20) DEFAULT NULL,
  `recon_id2` varchar(20) DEFAULT NULL,
  `amount_covered` decimal(15,2) DEFAULT NULL,
  `allocated_pins` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trade_id` (`trade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(10) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` enum('pending','active','suspended','inactive') NOT NULL DEFAULT 'pending',
  `home` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  `failed_logins` int(11) NOT NULL DEFAULT '0',
  `verification_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `user_activity`;
CREATE TABLE `user_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('login','logout','page_view','data_update','other') NOT NULL,
  `activity_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `user_settings`;
CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- 2023-11-29 06:23:17
