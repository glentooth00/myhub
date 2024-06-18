ALTER TABLE `clients`
ADD `marriage_cert` varchar(255) COLLATE 'latin1_swedish_ci' NULL AFTER `phone_number`,
ADD `crypto_declaration` varchar(255) COLLATE 'latin1_swedish_ci' NULL AFTER `marriage_cert`;

ALTER TABLE `clients`
DROP `spare_1`,
DROP `spare_2`;