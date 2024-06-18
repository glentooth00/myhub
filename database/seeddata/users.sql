-- Adminer 4.8.1 MySQL 5.7.33 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

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
  `home` varchar(255) NOT NULL,
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

INSERT INTO `users` (`id`, `user_id`, `role_id`, `username`, `password`, `first_name`, `last_name`, `email`, `status`, `home`, `created_at`, `created_by`, `updated_at`, `updated_by`, `last_login_at`, `last_activity_at`, `failed_logins`, `verification_token`) VALUES
(1, '_sysadm_', 1,  'Sysadmin', '$2y$10$eLVoPNRbZfHrD1377IAqk.G8C.rF4DMRrksAU6u0uCBtkHcpOQH2u', 'CH', 'Cron', 'sysadmin@currencyhub.co.za', 'active', '', '2020-08-01 08:30:00',  'neelsdev', NULL, NULL, NULL, NULL, 0,  NULL),
(2, '_acntnt_', 8,  'Accountant', '$2y$10$eLVoPNRbZfHrD1377IAqk.G8C.rF4DMRrksAU6u0uCBtkHcpOQH2u', 'CH', 'System', 'accountant@currencyhub.co.za', 'active', '', '2020-08-01 08:30:00',  'neelsdev', NULL, NULL, NULL, NULL, 0,  NULL);

-- 2023-11-25 15:02:36