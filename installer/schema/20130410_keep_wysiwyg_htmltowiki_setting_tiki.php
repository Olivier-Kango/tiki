<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * default for wysiwyg_htmltowiki changed between tiki 10 and 11 in r45440 - this maintains previous default setting
 *
 * @param $installer
 */
function upgrade_20130410_keep_wysiwyg_htmltowiki_setting_tiki($installer)
{
    $value = $installer->getOne("SELECT `value` FROM `tiki_preferences` WHERE `name` = 'wysiwyg_htmltowiki'");

    if ($value !== 'y') {   // default values can be empty
        $preferences = $installer->table('tiki_preferences');
        $preferences->insertOrUpdate(['value' => 'n'], ['name' => 'wysiwyg_htmltowiki']);
    }
}
