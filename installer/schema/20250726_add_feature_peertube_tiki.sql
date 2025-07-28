
-- Clean old PeerTube menu entries (avoid duplication/misconfiguration)
DELETE FROM `tiki_menu_options` WHERE `name` IN (
  'PeerTube Video',
  'List PeerTube Media',
  'Upload PeerTube Video'
);

-- Clean old PeerTube permissions (ensure consistent state)
DELETE FROM `users_permissions` WHERE `permName` IN (
  'tiki_p_admin_peertube',
  'tiki_p_delete_peertube',
  'tiki_p_download_peertube',
  'tiki_p_edit_peertube',
  'tiki_p_list_peertube',
  'tiki_p_upload_peertube',
  'tiki_p_view_peertube'
);

-- Activate the preference as disabled by default
INSERT INTO `tiki_preferences` (`name`, `value`) VALUES ('feature_peertube', 'n')
ON DUPLICATE KEY UPDATE `value` = 'n';

-- Permissions
INSERT IGNORE INTO `users_permissions` (`permName`, `level`) VALUES
('tiki_p_admin_peertube', 'admin'),
('tiki_p_upload_peertube', 'registered'),
('tiki_p_edit_peertube', 'registered'),
('tiki_p_view_peertube', 'registered'),
('tiki_p_list_peertube', 'registered'),
('tiki_p_delete_peertube', 'registered'),
('tiki_p_download_peertube', 'registered');

-- Menu entries
INSERT IGNORE INTO `tiki_menu_options` (`menuId`, `type`, `name`, `url`, `position`, `section`, `perm`, `groupname`, `userlevel`) VALUES
(42, 's', 'PeerTube Video', 'tiki-list_peertube_entries.php', 960, 'feature_peertube', 'tiki_p_admin | tiki_p_admin_peertube | tiki_p_list_peertube', '', 0),
(42, 'o', 'List PeerTube Media', 'tiki-list_peertube_entries.php', 962, 'feature_peertube', 'tiki_p_admin | tiki_p_admin_peertube | tiki_p_list_peertube', '', 0),
(42, 'o', 'Upload PeerTube Video', 'tiki-peertube_upload.php', 964, 'feature_peertube', 'tiki_p_admin | tiki_p_admin_peertube | tiki_p_upload_peertube', '', 0);

-- Set icon for the menu section
UPDATE `tiki_menu_options` SET icon = 'video' WHERE `name` = 'PeerTube Video' AND `menuId` = 42;
