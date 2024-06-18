ALTER TABLE `roles`
ADD `home` varchar(255) COLLATE 'latin1_swedish_ci' NOT NULL,
ADD `permissions` text COLLATE 'latin1_swedish_ci' NOT NULL;