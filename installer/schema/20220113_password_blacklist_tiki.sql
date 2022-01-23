CREATE TABLE IF NOT EXISTS `tiki_password_blacklist` (
    `password` VARCHAR(30) NOT NULL,
    PRIMARY KEY (`password`) USING HASH
) ENGINE=MyISAM;