<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Removes the hash column from tiki_articles table in case it exists.
 * Some Tiki instances might still have this column
 * @param Installer $installer
 */
function upgrade_20221028_remove_openid_url_tiki(Installer $installer)
{
    global $dbs_tiki;

    $exists = $installer->getOne(
        "SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_NAME = 'openid_url' 
                AND TABLE_SCHEMA = '" . $dbs_tiki . "'
                AND TABLE_NAME = 'users_users'"
    );

    if (boolval($exists)) {
        $installer->query("ALTER TABLE `users_users` DROP KEY `openid_url`");
    }

    $exists = $installer->getOne(
        "SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE COLUMN_NAME = 'openid_url' 
                AND TABLE_SCHEMA = '" . $dbs_tiki . "'
                AND TABLE_NAME = 'users_users'"
    );

    if (boolval($exists)) {
        $installer->query("ALTER TABLE `users_users` DROP COLUMN `openid_url`");
    }

    return true;
}
