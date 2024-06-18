ALTER TABLE `clients`
CHANGE `inhouse_referrer_15_percent` `inhouse_referrer_15_percent` varchar(20) NULL AFTER `fia_used`;

UPDATE clients SET inhouse_referrer_15_percent = NULL;
