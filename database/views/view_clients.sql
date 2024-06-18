DROP VIEW IF EXISTS `view_clients`;
CREATE VIEW view_clients AS
SELECT
  `c`.*,
  `r`.`referrer_id` as `referrer_uid`,
  `r`.`name` as `referrer_name`
FROM
  `clients` `c`
LEFT JOIN
  `ch_referrers` `r` ON `r`.`id` = `c`.`referrer_id`