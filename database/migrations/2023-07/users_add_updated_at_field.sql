ALTER TABLE `users`
ADD `created_by` varchar(20) COLLATE 'latin1_spanish_ci' NULL AFTER `created_at`,
ADD `updated_at` timestamp NULL AFTER `created_by`,
ADD `updated_by` varchar(20) COLLATE 'latin1_spanish_ci' NULL AFTER `updated_at`;