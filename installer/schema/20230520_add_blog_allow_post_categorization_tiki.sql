# Keep past behaviour on upgrades with DEFAULT 'y'. For new blogs we will default to 'n' as it makes more sense.
ALTER TABLE `tiki_blogs` ADD `allow_post_categorization` CHAR(1) DEFAULT 'y' AFTER `allow_comments`;
