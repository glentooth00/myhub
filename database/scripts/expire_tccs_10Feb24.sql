# Expire TCCs
UPDATE `tccs`
SET `expired` = CASE
                  WHEN `rollover` > 0 THEN YEAR(`date`) + 1
                  ELSE YEAR(`date`)
                END,
    `status` = 'Expired'
WHERE `status` = 'Approved'
  AND `date` IS NOT NULL
  AND `date` <= DATE_SUB(NOW(), INTERVAL 1 YEAR);



UPDATE `tccs` SET `status` = 'Expired'
WHERE `status` = 'Approved' AND `date` <= '2023-02-10';

UPDATE `tccs` SET `amount_available` = 0
WHERE `status` = 'Expired' AND `amount_available` > '0';

UPDATE tccs
SET notes = REGEXP_REPLACE(notes, '\\| rollover was = [0-9]+\\.?[0-9]*', '');

UPDATE tccs
SET notes = REGEXP_REPLACE(notes, '\\| rollover was =\s*', '');

UPDATE tccs
SET notes = REGEXP_REPLACE(notes, '\s*\\| auto rollover\s*', '');

UPDATE tccs
SET notes = REGEXP_REPLACE(notes, '\s*\\|Set expired = 2023\s*', '');

UPDATE tccs
SET notes = REGEXP_REPLACE(notes, '\s*\\| Pin', 'Pin');