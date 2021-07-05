<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @return array
 */
function module_top_pages_info()
{
    return [
        'name' => tra('Top Pages'),
        'description' => tra('Displays the specified number of wiki pages with links to them, starting with the one having the most hits.'),
        'prefs' => ['feature_wiki'],
        'params' => [],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_top_pages($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    global $ranklib;
    include_once('lib/rankings/ranklib.php');
    $categs = $ranklib->get_jail();
    $ranking = $ranklib->wiki_ranking_top_pages($mod_reference["rows"], $categs ? $categs : []);

    $smarty->assign('modTopPages', $ranking["data"]);
}
