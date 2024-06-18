UPDATE `clients` SET `inhouse_referrer_15_percent` = `third_party_referrer`, `third_party_referrer` = NULL
WHERE `third_party_referrer` IS NOT NULL;


UPDATE `clients` SET `inhouse_referrer_15_percent` = `third_party_referrer`, `third_party_referrer` = NULL
WHERE `third_party_referrer` IS NOT NULL AND ( `inhouse_referrer_15_percent` IS NULL OR `inhouse_referrer_15_percent` = '_system_' );


UPDATE `clients` SET `inhouse_referrer_15_percent` = 'antheami' WHERE `inhouse_referrer_15_percent` = 'AMichaels2';

UPDATE `clients` SET `inhouse_referrer_15_percent` = 'danielac' WHERE `inhouse_referrer_15_percent` = '8168f82f';

UPDATE `clients` SET `inhouse_referrer_15_percent` = NULL WHERE `inhouse_referrer_15_percent` = '_system_';

UPDATE `ch_referrers` SET `type_id` = 5 WHERE `client_id` IS NOT NULL;