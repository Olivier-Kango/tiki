DELETE FROM `tiki_modules`
WHERE `name` = 'last_image_galleries'
      OR  `name` = 'last_images'
      OR  `name` = 'random_images'
      OR  `name` = 'top_image_galleries'
      OR  `name` = 'top_images'
      OR  `name` = 'user_image_galleries'
;

DELETE FROM `tiki_menu_options`
WHERE (`menuId` = '42') AND (`section` LIKE '%feature_galleries%');

DELETE FROM `tiki_sefurl_regex_out` WHERE `feature` = 'feature_galleries';
