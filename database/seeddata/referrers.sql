-- Adminer 4.8.1 MySQL 5.7.33 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

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

INSERT INTO `referrers` (`id`, `referrer_id`, `name`, `email`, `phone_number`, `profit_percent`, `inhouse_user_uid`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
(1, 'innovate', 'Innovate', 'NULL', 'NULL', NULL, '_system_', '2022-03-09 11:03:47',  'neelsdev', '2022-03-09 11:03:47',  'neelsdev');

-- 2023-11-25 01:19:01
