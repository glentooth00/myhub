DROP VIEW IF EXISTS `view_client_referrers`;
CREATE VIEW view_client_referrers AS
SELECT 
	c.`id`, 
	c.`client_id`, 
	c.`name` as `client_name`, 
	c.`inhouse_referrer_15_percent`,
	r.`id` as `client_referrer_id`,
	r.`name` as `client_referrer_name`,
	r.`referrer_type` as `client_referrer_type`,
	r.`notes` as `referrer_notes`,
	c.`third_party_referrer`,
	c.`third_party_profit_percent`, 
	c.`fx_intermediary`, 
	c.`trader_id`
FROM `clients` c LEFT JOIN `view_referrers` r ON ( c.`inhouse_referrer_15_percent` = r.`referrer_id` OR c.`inhouse_referrer_15_percent` = r.`name` )
WHERE c.`inhouse_referrer_15_percent` IS NOT NULL AND c.`inhouse_referrer_15_percent` != '_system_'
ORDER BY `inhouse_referrer_15_percent` DESC;


LOAD DATA INFILE 'C:/Users/xavie/Downloads/new_client_referrers.csv'
INTO TABLE new_client_referrers
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS;


LOAD DATA INFILE 'C:/Users/xavie/Downloads/new_client_referrers.csv'
INTO TABLE new_client_referrers
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(id,
 client_id,
 client_name,
 client_referrer_id,
 client_referrer_name,
 client_referrer_type,
 referrer_notes,
 third_party_referrer,
 @third_party_profit_percent,
 fx_intermediary,
 trader_id)
SET third_party_profit_percent = NULLIF(@third_party_profit_percent, '');


SELECT DISTINCT COALESCE(NULLIF(client_referrer_name, ''), client_referrer_id) AS referrer_name
FROM `new_client_referrers`;

SELECT
  r.`referrer_name`,
  c.`client_id`,
  c.`id_number`, 
  r.`id_number` as referer_id_number,
  c.`personal_email` as email,
  c.`phone_number`
FROM `referrer_names` r
LEFT JOIN `clients` c ON c.`name` LIKE CONCAT('%', r.`referrer_name`, '%')

SELECT client_id, name, id_number, phone_number, personal_email as email
FROM clients
WHERE id_number IN (
    '9312170082085',
    '7204285005089',
    '7810135049083',
    '8605060038088',
    '8809285017089',
    '9406155216088',
    '8111195219081',
    '9201225355085',
    '8907050135084',
    '8511275048088',
    '7604025114085',
    '7304095132089',
    '7103175113088',
    '8909305158085'
);