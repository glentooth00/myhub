DROP VIEW IF EXISTS `view_revenue_model`;
CREATE VIEW view_revenue_model AS
SELECT
  `b`.*,
  `m`.`name` as `type_name`,
  `r`.`name` as `referrer_name`
  `c`.`name` as `client_name`
FROM
  `ch_revenue_models` `b`
LEFT JOIN
  `ch_revenue_model_types` `m` ON `m`.`id` = `b`.`type_id`
LEFT JOIN
  `ch_referrers` `r` ON `r`.`id` = `b`.`referrer_id`
LEFT JOIN
  `clients` `c` ON `c`.`id` = `b`.`client_id`;