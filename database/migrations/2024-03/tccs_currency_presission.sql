ALTER TABLE `tccs`
CHANGE `amount_cleared` `amount_cleared` decimal(15,3) NULL AFTER `date`,
CHANGE `amount_reserved` `amount_reserved` decimal(15,3) NULL AFTER `amount_cleared`,
CHANGE `rollover` `rollover` decimal(15,3) NULL AFTER `amount_reserved`,
CHANGE `amount_cleared_net` `amount_cleared_net` decimal(15,3) NULL AFTER `rollover`,
CHANGE `amount_used` `amount_used` decimal(15,3) NULL AFTER `amount_cleared_net`,
CHANGE `amount_remaining` `amount_remaining` decimal(15,3) NULL AFTER `amount_used`,
CHANGE `amount_available` `amount_available` decimal(15,3) NULL AFTER `amount_remaining`;


ALTER TABLE `tccs`
CHANGE `amount_cleared` `amount_cleared` decimal(15,2) NULL AFTER `date`,
CHANGE `amount_reserved` `amount_reserved` decimal(15,2) NULL AFTER `amount_cleared`,
CHANGE `rollover` `rollover` decimal(15,2) NULL AFTER `amount_reserved`,
CHANGE `amount_cleared_net` `amount_cleared_net` decimal(15,2) NULL AFTER `rollover`,
CHANGE `amount_used` `amount_used` decimal(15,2) NULL AFTER `amount_cleared_net`,
CHANGE `amount_remaining` `amount_remaining` decimal(15,2) NULL AFTER `amount_used`,
CHANGE `amount_available` `amount_available` decimal(15,2) NULL AFTER `amount_remaining`;