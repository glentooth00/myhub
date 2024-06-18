DROP VIEW IF EXISTS `view_trades`;
CREATE VIEW view_trades AS
SELECT 
  trades.*, 
  clients.id AS client_id2,
  clients.name AS client_name,
  clients.accountant AS client_accountant
FROM 
  trades
LEFT JOIN 
  clients ON trades.client_id = clients.client_id;