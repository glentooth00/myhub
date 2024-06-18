ALTER TABLE `clients`
CHANGE `marriage_cert` `spare_1` varchar(255) COLLATE 'latin1_swedish_ci' NULL AFTER `phone_number`,
CHANGE `crypto_declaration` `spare_2` varchar(255) COLLATE 'latin1_swedish_ci' NULL AFTER `spare_1`;