ALTER TABLE `clients`
ADD `deleted_at` datetime NULL AFTER `notes`,
ADD `deleted_by` varchar(20) COLLATE 'latin1_swedish_ci' NULL AFTER `deleted_at`;