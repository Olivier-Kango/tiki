CREATE TABLE IF NOT EXISTS `tiki_xmpp_guest_credentials` (
  `username` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created` int(11) NOT NULL,
  `last_seen` int(11) NOT NULL,
  PRIMARY KEY (`username`)
) ENGINE=MyISAM;
