ALTER TABLE `tccs`
CHANGE `status` `status` enum('Pending','Awaiting Docs','Approved','Declined','Expired','Used') NOT NULL DEFAULT 'Pending' AFTER `client_id`;