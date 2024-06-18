DROP VIEW IF EXISTS `view_fees`;
CREATE VIEW view_fees AS
SELECT 
  f.id AS fee_id,
  f.fee_type_id,
  ft.name AS fee_type_name,
  ft.description AS fee_type_description,
  f.amount,
  f.start_date,
  f.end_date
FROM fees f
JOIN fee_types ft ON f.fee_type_id = ft.id;
