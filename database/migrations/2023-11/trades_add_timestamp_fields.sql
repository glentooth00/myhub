ALTER TABLE `trades`
ADD `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `allocated_pins`,
ADD `created_by` varchar(20) NOT NULL DEFAULT '_system_' AFTER `created_at`,
ADD `updated_at` datetime NULL AFTER `created_by`,
ADD `updated_by` varchar(20) NULL AFTER `updated_at`,
ADD `deleted_at` datetime NULL AFTER `updated_by`,
ADD `deleted_by` varchar(20) NULL AFTER `deleted_at`;