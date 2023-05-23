ALTER TABLE `tiki_object_relations` ADD COLUMN `metadata_itemId` INT(12) default NULL;
ALTER TABLE `tiki_object_relations` ADD KEY `metadata_itemId` (`metadata_itemId`);