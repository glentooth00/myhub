UPDATE `clients` SET `city` = 'Cape Town' WHERE `city` REGEXP 'Cape.?Town';
UPDATE `clients`
SET `city` = REPLACE(`city`, CHAR(160 USING latin1), ' ')
WHERE `city` LIKE CONCAT('%', CHAR(160 USING latin1), '%');