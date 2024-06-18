ALTER TABLE `ch_referrers`
ADD `deleted_at` datetime NULL,
ADD `deleted_by` varchar(20) COLLATE 'latin1_swedish_ci' NULL AFTER `deleted_at`;