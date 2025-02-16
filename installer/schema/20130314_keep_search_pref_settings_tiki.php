<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * prefs for search type changed between tiki 10 and 11 in r45112 - this belatedly attempts to maintain current settings
 *
 * @param $installer
 */
function upgrade_20130314_keep_search_pref_settings_tiki($installer)
{
    $unisearch = $installer->getOne("SELECT `value` FROM `tiki_preferences` WHERE `name` = 'feature_search'");

    if ($unisearch !== 'n') {   // default values can be empty
        $preferences = $installer->table('tiki_preferences');
        $preferences->insertOrUpdate(['value' => 'y'], ['name' => 'feature_search']);
        $preferences->insertOrUpdate(['value' => 'n'], ['name' => 'feature_search_fulltext']);
    }
}
