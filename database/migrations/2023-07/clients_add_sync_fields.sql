ALTER TABLE `clients`
ADD COLUMN sync_at DATETIME DEFAULT NULL AFTER notes,
ADD COLUMN sync_by varchar(20) DEFAULT NULL AFTER sync_at,
ADD COLUMN sync_from ENUM('local', 'remote') DEFAULT NULL AFTER sync_by,
ADD COLUMN sync_type ENUM('new', 'update') DEFAULT NULL AFTER sync_from;


ALTER TABLE `clients`
ADD COLUMN sync_by varchar(20) DEFAULT NULL AFTER sync_at;


ALTER TABLE `tccs`
ADD COLUMN sync_by varchar(20) DEFAULT NULL AFTER sync_at;