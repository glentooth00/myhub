ALTER TABLE `clients`
CHANGE `sync_from` `sync_from` enum('local','remote','both') COLLATE 'latin1_swedish_ci' NULL AFTER `sync_by`,
CHANGE `sync_type` `sync_type` enum('new','update','merge') COLLATE 'latin1_swedish_ci' NULL AFTER `sync_from`;