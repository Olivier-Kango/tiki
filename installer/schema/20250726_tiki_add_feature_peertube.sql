INSERT INTO `tiki_preferences` (`name`, `value`) VALUES ('feature_peertube', 'n')
ON DUPLICATE KEY UPDATE `value` = 'n';
