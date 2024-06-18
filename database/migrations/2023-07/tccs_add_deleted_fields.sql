ALTER TABLE `tccs`
ADD `deleted_at` datetime NULL AFTER `updated_by`,
ADD `deleted_by` varchar(20) COLLATE 'latin1_swedish_ci' NULL AFTER `deleted_at`;