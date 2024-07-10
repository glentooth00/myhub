SELECT 
  u.ID AS user_id,
  u.user_login,
  u.user_email,
  MAX(CASE WHEN um.meta_key = 'wpvm_capabilities' THEN um.meta_value END) AS roles,
  MAX(CASE WHEN um.meta_key = 'billing_phone' THEN um.meta_value END) AS billing_phone
FROM 
  wpvm_users u
LEFT JOIN 
  wpvm_usermeta um ON u.ID = um.user_id
GROUP BY 
  u.ID, u.user_login, u.user_email;



SELECT u.ID AS user_id, u.display_name, u.user_login, u.user_email, 
MAX(CASE WHEN um.meta_key = 'billing_phone' THEN um.meta_value END) AS billing_phone,
MAX(CASE WHEN um.meta_key = 'digits_phone' THEN um.meta_value END) AS digits_phone,
MAX(CASE WHEN um.meta_key = 'wpvm_capabilities' THEN um.meta_value END) AS roles
FROM wpvm_users u LEFT JOIN wpvm_usermeta um ON u.ID = um.user_id 
GROUP BY u.ID, u.user_login, u.user_email;