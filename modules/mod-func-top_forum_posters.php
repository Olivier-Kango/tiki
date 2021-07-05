<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @return array
 */
function module_top_forum_posters_info()
{
    return [
        'name' => tra('Top Forum Posters'),
        'description' => tra('Displays the specified number of users who posted to forums, starting with the one having most posts.'),
        'prefs' => ['feature_forums'],
        'params' => [],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_top_forum_posters($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    global $ranklib;
    include_once('lib/rankings/ranklib.php');
    $posters = $ranklib->forums_top_posters($mod_reference["rows"]);

    $smarty->assign('modTopForumPosters', $posters["data"]);
}
