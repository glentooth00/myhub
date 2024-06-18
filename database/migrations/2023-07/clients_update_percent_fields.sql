ALTER TABLE `clients`
CHANGE `inhouse_referrer_15_percent` `inhouse_referrer_15_percent` decimal(5,2) NULL AFTER `fia_used`,
CHANGE `third_party_profit_percent` `third_party_profit_percent` decimal(5,2) NULL AFTER `third_party_referrer`;