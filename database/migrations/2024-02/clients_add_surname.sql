ALTER TABLE `clients`
ADD `surname` varchar(50) COLLATE 'latin1_swedish_ci' NULL AFTER `name`;

ALTER TABLE `clients`
ADD `first_name` varchar(50) COLLATE 'latin1_swedish_ci' NULL AFTER `name`,
ADD `middle_name` varchar(50) COLLATE 'latin1_swedish_ci' NULL AFTER `first_name`,
CHANGE `surname` `last_name` varchar(50) COLLATE 'latin1_swedish_ci' NULL AFTER `middle_name`;

ALTER TABLE `clients`
ADD `spouse_id` varchar(20) COLLATE 'latin1_swedish_ci' NULL AFTER `client_id`;

ALTER TABLE `clients`
ADD `ncr` varchar(20) COLLATE 'latin1_swedish_ci' NULL AFTER `personal_email`;