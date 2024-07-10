DROP VIEW IF EXISTS `view_beneficiary`;
CREATE VIEW view_beneficiary AS
SELECT
  `b`.*,
  `m`.`name` as `type_name`,
  `r`.`name` as `referrer_name`
FROM
  `ch_beneficiaries` `b`
LEFT JOIN
  `ch_revenue_model_types` `m` ON `m`.`id` = `b`.`type_id`
LEFT JOIN
  `ch_referrers` `r` ON `r`.`id` = `b`.`referrer_id`;