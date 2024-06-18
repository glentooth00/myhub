-- Adminer 4.8.1 MySQL 5.7.33 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `ch_partners`;
CREATE TABLE `ch_partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(36) NOT NULL,
  `type` varchar(20) DEFAULT 'Forex',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(20) NOT NULL DEFAULT '_system_',
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `ch_partners` (`id`, `name`, `type`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
(1,	'Alpha Treasury',	'Forex',	'2023-11-29 06:17:50',	'_system_',	NULL,	NULL),
(2,	'BOFH',	'Forex',	'2023-11-29 06:17:50',	'_system_',	NULL,	NULL),
(3,	'OVEX',	'Forex',	'2023-11-29 06:17:50',	'_system_',	NULL,	NULL);

-- 2024-02-27 08:37:45