SELECT `id`, 
`client_id`, `name`, `personal_email`, `id_number`, `phone_number`, 
`inhouse_referrer_15_percent`, `third_party_referrer`
FROM `clients`;


Please assist in generating a Google Sheets formula that lists the columns on one sheet called "S2 Referrers" 
and add the columns of another "S3 Referrers", matching the rows by a common sorted primary key column called "client_id".  
"client_id" is in col A of each source sheet.  Each sheet has four data columns.


SELECT c.`client_id`, c.`cif_number`, c.`id_number`, c.`phone_number`, 
c.`name` as `client_name`, c.`personal_email`, c.`inhouse_referrer_15_percent`, 
c.`third_party_referrer`, c.`referrer_id`, r.`name` as `referrer_name`
FROM `clients` c LEFT JOIN `ch_referrers` r ON r.`id` = c.`referrer_id`;

WHERE c.`inhouse_referrer_15_percent` IS NOT NULL OR c.`referrer_id` IS NOT NULL;



select * from clients
where inhouse_referrer_15_percent = "chmarkts" and third_party_referrer = "f0be6121"

select * from clients where inhouse_referrer_15_percent = "f0be6121" 
  or third_party_referrer = "f0be6121" or third_party_referrer like "%koin%"


update clients set referrer_id = 8
where inhouse_referrer_15_percent = "chmarkts" and third_party_referrer = "f0be6121"

1cf01750


update clients set referrer_id = 16, third_party_referrer = NULL 
where inhouse_referrer_15_percent = "e43e2b33" ;


select * from clients where inhouse_referrer_15_percent = "1cf01750" 
  or third_party_referrer = "1cf01750" or third_party_referrer like "%koin%"

update clients set referrer_id = 8 where inhouse_referrer_15_percent = "1cf01750"

update clients set inhouse_referrer_15_percent = "f0be6121" where inhouse_referrer_15_percent = "1cf01750"