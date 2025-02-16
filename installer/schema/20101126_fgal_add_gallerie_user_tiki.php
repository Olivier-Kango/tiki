<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @param $installer
 */
function upgrade_20101126_fgal_add_gallerie_user_tiki($installer)
{
    global $dbs_tiki;

    // Add user file Gallery (feature_use_fgal_for_user_files)
    $installer->query(
        "INSERT INTO `tiki_file_galleries`" .
        " (`name`, `type`, `description`, `visible`, `user`, `public`, `parentId`)" .
        " VALUES ('Users File Galleries', 'system', '', 'y', 'admin', 'y', -1)"
    );
    // Search last insert id
    $result = $installer->getOne("SELECT `galleryId` FROM `tiki_file_galleries` WHERE `name` = 'Users File Galleries' and `type`='system'");

    if ($result != 0) {
        $installer->query("INSERT INTO `tiki_preferences` (`name`, `value`) VALUES ('fgal_root_user_id', '" . $result . "');");
    }
}
