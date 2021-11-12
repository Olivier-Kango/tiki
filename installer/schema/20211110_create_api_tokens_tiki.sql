CREATE TABLE IF NOT EXISTS `tiki_api_tokens` (
  `tokenId` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(200) NOT NULL,
  `token` varchar(100) NOT NULL,
  `created` int NOT NULL,
  `lastModif` int NOT NULL,
  `expireAfter` int NULL,
  `hits` int NOT NULL default 0,
  PRIMARY KEY (`tokenId`),
  KEY `token` (`token`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;
