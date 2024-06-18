/* De dup ch_referrers first! */

SELECT r.*
FROM ch_referrers r
JOIN (
  SELECT id_number
  FROM ch_referrers
  WHERE deleted_at IS NULL
  GROUP BY id_number
  HAVING COUNT(*) > 1
) dup ON r.id_number = dup.id_number
WHERE r.deleted_at IS NULL
ORDER BY id_number;


UPDATE `ch_referrers`
SET `referrer_id` = CONCAT(LOWER(LEFT(`name`, 4)), LEFT(`id_number`, 4))
WHERE `referrer_id` IS NULL;

UPDATE `ch_referrers`
SET `referrer_id` = LEFT(UUID(), 8)
WHERE `referrer_id` IS NULL AND (`id_number` IS NULL OR `id_number` = '');
