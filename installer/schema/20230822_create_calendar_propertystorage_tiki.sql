CREATE TABLE IF NOT EXISTS `tiki_calendar_propertystorage` (
    `id` INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `path` VARBINARY(1024) NOT NULL,
    `name` VARBINARY(100) NOT NULL,
    `valuetype` INT UNSIGNED,
    `value` MEDIUMBLOB,
    UNIQUE KEY `path_property` (path(600), name(100))
) ENGINE=MyISAM;
