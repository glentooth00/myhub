DROP VIEW IF EXISTS `view_referrers`;
CREATE VIEW view_referrers AS
SELECT
	r.*, 
	t.name as referrer_type,
	t.description as type_desc
FROM `ch_referrers` r
LEFT JOIN `ch_referrer_types` t ON r.`type_id` = t.`id`