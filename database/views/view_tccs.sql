DROP VIEW IF EXISTS `view_tccs`;
CREATE VIEW view_tccs AS
SELECT 
  tccs.*, 
  clients.id AS client_id2,
  clients.name AS client_name, 
  clients.bank AS client_bank,
  clients.tax_number AS client_tax_number,
  clients.accountant AS client_accountant
FROM 
  tccs 
LEFT JOIN 
  clients ON tccs.client_id = clients.client_id;