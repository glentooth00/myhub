ALTER TABLE `trades`
ADD `forex` enum('Capitec','Mercantile') COLLATE 'latin1_swedish_ci' NULL AFTER `date`,
CHANGE `reference` `forex_reference` varchar(20) COLLATE 'latin1_swedish_ci' NULL COMMENT 'Mercantile' AFTER `forex`,
CHANGE `otc` `otc` enum('OVEX','VALR') COLLATE 'latin1_swedish_ci' NULL AFTER `forex_reference`,
ADD `otc_reference` varchar(20) COLLATE 'latin1_swedish_ci' NULL AFTER `otc`,
CHANGE `client_id` `client_id` varchar(20) COLLATE 'latin1_swedish_ci' NULL AFTER `otc_reference`,
CHANGE `sda_fia` `sda_fia` varchar(10) COLLATE 'latin1_swedish_ci' NULL AFTER `client_id`,
CHANGE `zar_sent` `zar_sent` decimal(15,2) NULL AFTER `sda_fia`,
CHANGE `usd_bought` `usd_bought` decimal(15,2) NULL AFTER `zar_sent`,
CHANGE `trade_fee` `trade_fee` decimal(5,2) NULL AFTER `usd_bought`,
CHANGE `forex_rate` `forex_rate` decimal(15,3) NULL AFTER `trade_fee`,
CHANGE `zar_profit` `zar_profit` decimal(15,2) NULL AFTER `forex_rate`,
CHANGE `percent_return` `percent_return` decimal(5,2) NULL AFTER `zar_profit`,
ADD `allocated_pins` text COLLATE 'latin1_swedish_ci' NULL;

ALTER TABLE `trades`
CHANGE `forex_rate` `forex_rate` decimal(6,3) NULL AFTER `trade_fee`;