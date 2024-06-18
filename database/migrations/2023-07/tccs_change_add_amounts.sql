ALTER TABLE `tccs`
CHANGE `amount_cleared` `amount_cleared` decimal(15,2) NULL AFTER `date`,
ADD `amount_remaining` decimal(15,2) NULL AFTER `amount_cleared`,
ADD `amount_available` decimal(15,2) NULL AFTER `amount_remaining`,
CHANGE `amount_used` `amount_used` decimal(15,2) NULL AFTER `amount_available`,
CHANGE `rollover` `rollover` decimal(15,2) NULL AFTER `amount_used`;