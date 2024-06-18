SELECT c.`inhouse_referrer_15_percent`, 
u.`id`, CONCAT(u.`first_name`, " ", u.`last_name`) as name, u.`email`
FROM `clients` c JOIN `users` u ON u.`user_id` = c.`inhouse_referrer_15_percent` 
GROUP BY c.`inhouse_referrer_15_percent`, u.`id`, u.`first_name`, u.`last_name