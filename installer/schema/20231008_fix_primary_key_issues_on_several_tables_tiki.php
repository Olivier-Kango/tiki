<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use Tiki\Installer\Installer;

function upgrade_20231008_fix_primary_key_issues_on_several_tables_tiki(Installer $installer)
{
    applyAlterForTable($installer, 'tiki_actionlog_conf', "DROP PRIMARY KEY");
    applyAlterForTable($installer, 'tiki_actionlog_conf', "DROP INDEX `id`, ADD PRIMARY KEY (`id`)");
    applyAlterForTable($installer, 'tiki_actionlog_conf', "ADD UNIQUE KEY `uk_action_obj` (`action`, `objectType`)");

    applyAlterForTable($installer, 'tiki_translated_objects', "CHANGE `traId` `traId` int(14) NOT NULL DEFAULT 0");
    applyAlterForTable($installer, 'tiki_translated_objects', "DROP PRIMARY KEY, DROP INDEX `traId`");
    applyAlterForTable($installer, 'tiki_translated_objects', "ADD `id` INT(14) NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`)");
    applyAlterForTable($installer, 'tiki_translated_objects', "ADD UNIQUE KEY `uk_type_objId` (`type`, `objId`(141))");

    applyAlterForTable($installer, 'tiki_history', "DROP PRIMARY KEY");
    applyAlterForTable($installer, 'tiki_history', "ADD PRIMARY KEY (`historyId`), DROP INDEX `user`, DROP INDEX `historyId`");
    applyAlterForTable($installer, 'tiki_history', "ADD UNIQUE KEY `uk_version_pageName` (`pageName`,`version`), ADD KEY `k_user` (`user`(191))");

    applyAlterForTable($installer, 'tiki_untranslated', "DROP PRIMARY KEY");
    applyAlterForTable($installer, 'tiki_untranslated', "ADD PRIMARY KEY (`id`), DROP INDEX `id_2`, DROP INDEX `id`");
    applyAlterForTable($installer, 'tiki_untranslated', "ADD UNIQUE KEY `uk_source_lang` (`source`(255),`lang`)");

    return true;
}

function applyAlterForTable(Installer $installer, $tableName, $query): void
{
    global $output;
    if ($installer->tableExists($tableName)) {
        try {
            $installer::get()->queryException("ALTER TABLE " . $tableName . " " . $query);
        } catch (Exception $e) {
            $fname = basename(__FILE__, '.php');
            $output->writeLn('Warning : <href=' . __FILE__ . '>' . $fname . '</>, <error>' . $e->getMessage() . '</error> on ' . $tableName . ' table');
        }
    }
}
