<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
use Tiki\Installer\Installer;

/**
 * Removes the hash column from tiki_articles table in case it exists.
 * Some Tiki instances might still have this column
 * @param Installer $installer
 */
function upgrade_20220601_discard_hash_column_from_articles_tiki(Installer $installer)
{
    global $dbs_tiki;

    $exists = $installer->getOne(
        "SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE COLUMN_NAME = 'hash' 
                AND TABLE_SCHEMA = '" . $dbs_tiki . "'
                AND TABLE_NAME = 'tiki_articles'"
    );

    if (boolval($exists)) {
        $installer->query("ALTER TABLE tiki_articles DROP COLUMN hash");
    }
}
