ALTER TABLE `tiki_tracker_items` ADD COLUMN `is_locked` char(1) default 'n';
ALTER TABLE `tiki_tracker_items` ADD COLUMN `locked_by` int(12) default NULL;
ALTER TABLE `tiki_tracker_items` ADD COLUMN `locked_date` int(12) default NULL;