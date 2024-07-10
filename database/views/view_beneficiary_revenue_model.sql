DROP VIEW IF EXISTS `view_beneficiary_revenue_model`;
CREATE VIEW view_beneficiary_revenue_model AS
SELECT
  `a`.*,
  `m`.`name` as `revenue_model`,
  `b`.`name` as `beneficiary_name`
FROM
  `ch_revenue_model_beneficiaries` `a`
LEFT JOIN
  `ch_revenue_models` `m` ON `m`.`id` = `a`.`revenue_model_id`
LEFT JOIN
  `ch_beneficiaries` `b` ON `b`.`id` = `a`.`beneficiary_id`;

