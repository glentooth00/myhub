ALTER TABLE `tccs`
CHANGE `amount_reserved` `amount_reserved` decimal(15,2) NULL AFTER `amount_cleared`,
CHANGE `rollover` `rollover` decimal(15,2) NULL AFTER `amount_reserved`;