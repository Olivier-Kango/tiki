UPDATE `tiki_tracker_fields` SET `visibleInViewMode` = 'y' WHERE `visibleInViewMode` IS NULL;
UPDATE `tiki_tracker_fields` SET `visibleInEditMode` = 'y' WHERE `visibleInEditMode` IS NULL;
UPDATE `tiki_tracker_fields` SET `visibleInHistoryMode` = 'y' WHERE `visibleInHistoryMode` IS NULL;
ALTER TABLE `tiki_tracker_fields` MODIFY COLUMN `visibleInViewMode` char(1) NOT NULL default 'y', MODIFY COLUMN `visibleInEditMode` char(1) NOT NULL default 'y', MODIFY COLUMN `visibleInHistoryMode` char(1) NOT NULL default 'y';
