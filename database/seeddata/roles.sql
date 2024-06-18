-- Adminer 4.8.1 MySQL 5.7.33 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

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

INSERT INTO `roles` (`id`, `name`, `description`, `home`, `permissions`) VALUES
(1, 'sysadmin', 'Sysadmin', 'admin/dashboard',  ''),
(2, 'system-bot', 'System Bot', '', ''),
(3, 'super-admin',  'Super Admin',  'admin/dashboard',  ''),
(4, 'group-admin',  'Group Admin',  'admin/dashboard',  ''),
(5, 'admin',  'Admin',  'admin/dashboard',  ''),
(6, 'manager',  'Manager',  'admin/dashboard',  ''),
(7, 'trader', 'Trader', 'admin/dashboard',  ''),
(8, 'accountant', 'Accountant', 'accountant/dashboard', ''),
(9, 'client', 'Client', 'client/dashboard', '');

-- 2023-11-29 06:18:34