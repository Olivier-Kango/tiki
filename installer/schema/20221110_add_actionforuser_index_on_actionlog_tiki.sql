ALTER TABLE `tiki_actionlog` ADD INDEX `actionforuser` (`user` (100), `objectType`, `action` (100));
