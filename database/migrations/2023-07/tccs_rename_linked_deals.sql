ALTER TABLE `tccs`
CHANGE `linked_deals` `allocated_trades` varchar(255) COLLATE 'latin1_swedish_ci' NULL AFTER `notes`;