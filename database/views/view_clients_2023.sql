DROP VIEW IF EXISTS `view_clients_2023`;
CREATE VIEW view_clients_2023 AS
SELECT 
  c23.*, 
  clients.`id` AS `client_id2`
FROM 
  `clients_2023` `c23`
LEFT JOIN 
  clients ON `c23`.`client_id` = clients.`client_id`;