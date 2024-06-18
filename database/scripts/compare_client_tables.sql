# Compare Client Tables
SELECT
  o.client_id as client_uid,
  o.name as client_name_old,
  n.name as client_name_new,
  o.id_number as id_num_old, 
  n.id_number as id_num_new,
  o.phone_number as phone_old,
  n.phone_number as phone_new
FROM clients_bkp o
LEFT JOIN clients n on o.id = n.id;