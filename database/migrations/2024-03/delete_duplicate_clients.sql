# Find duplicates by id_number
SELECT c.client_id, c.name, c.id_number
FROM `clients` c
INNER JOIN (
  SELECT id_number
  FROM `clients`
  GROUP BY id_number
  HAVING COUNT(id_number) > 1
) dups ON c.id_number = dups.id_number;


# Select client rows
SELECT *
FROM `clients`
WHERE `client_id` IN ("99b4fa8e","335491c1")


# Delete duplicate clients - NM 29Mar24
DELETE FROM `clients` WHERE `id` = '1384';  # 335491c1 Ashley Sheasby
DELETE FROM `clients` WHERE `id` = '1425';  # a89ba360 Grant Booysen
DELETE FROM `clients` WHERE `id` = '2600';  # 73b72f59 Keryn Hayes
DELETE FROM `clients` WHERE `id` = '2528';  # 8d12c353 Alex Herzenberg


SELECT `id`, `client_id`, `name`, `tax_number`
FROM `clients`
WHERE `tax_number` = '0000000000'
ORDER BY `tax_number`

UPDATE `clients` SET `tax_number` = NULL
WHERE `tax_number` = '0000000000';


UPDATE `clients` set status = "Inactive" WHERE status IS NULL;