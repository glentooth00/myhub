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

DROP VIEW IF EXISTS `view_revenue_model`;
CREATE VIEW view_revenue_model AS
SELECT
  `b`.*,
  `m`.`name` as `type_name`,
  `r`.`name` as `referrer_name`,
  `c`.`name` as `client_name`
FROM
  `ch_revenue_models` `b`
LEFT JOIN
  `ch_revenue_model_types` `m` ON `m`.`id` = `b`.`type_id`
LEFT JOIN
  `ch_referrers` `r` ON `r`.`id` = `b`.`referrer_id`
LEFT JOIN
  `clients` `c` ON `c`.`id` = `b`.`client_id`;

DROP VIEW IF EXISTS `view_template`;
CREATE VIEW view_template AS
SELECT
  `t`.*,
  `m`.`name` as `type_name`
FROM
  `ch_revenue_model_templates` `t`
LEFT JOIN
  `ch_revenue_model_types` `m` ON `m`.`id` = `t`.`model_type_id`;  
