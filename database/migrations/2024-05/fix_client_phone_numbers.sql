UPDATE clients
SET phone_number = REPLACE(REPLACE(phone_number, ' ', ''), '+27', '27')
WHERE phone_number LIKE '+27%';

UPDATE clients
SET phone_number = CONCAT('27', SUBSTR(phone_number, 2))
WHERE phone_number LIKE '0%';
