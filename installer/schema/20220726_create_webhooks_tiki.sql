CREATE TABLE IF NOT EXISTS `tiki_webhooks` (
  `webhookId` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `user` varchar(200) NOT NULL,
  `verification` varchar(100) NOT NULL,
  `algo` varchar(100) NOT NULL,
  `signatureHeader` varchar(100) NOT NULL,
  `secret` text NOT NULL,
  `created` int NOT NULL,
  `lastModif` int NOT NULL,
  PRIMARY KEY (`webhookId`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;
