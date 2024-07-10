DROP VIEW IF EXISTS `view_template`;
CREATE VIEW view_template AS
SELECT
  `t`.*,
  `m`.`name` as `type_name`,
FROM
  `ch_revenue_model_templates` `t`
LEFT JOIN
  `ch_revenue_model_types` `m` ON `m`.`id` = `t`.`model_type_id`;