<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_random_pages_info()
{
    return [
        'name' => tra('Random Pages'),
        'description' => tra('Displays the specified number of random wiki pages.'),
        'prefs' => ['feature_wiki'],
        'documentation' => 'Module random_pages',
        'params' => [],
        'common_params' => ["rows", "nonums"]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_random_pages($mod_reference, $module_params)
{
    $tikilib = TikiLib::lib('tiki');
    $pages = $tikilib->list_pages(0, $mod_reference["rows"], "random", '', '', true, true);
    $smarty = TikiLib::lib('smarty');
    $smarty->assign('modRandomPages', $pages["data"]);
}
