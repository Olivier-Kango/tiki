<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_freetags_prefered_info()
{
    return [
        'name' => tra('My Preferred Tags'),
        'description' => tra('Displays to registered users the tags they prefer, based on the number of objects they tagged. Greater preference is indicated by a larger text size.'),
        'prefs' => ['feature_freetags'],
        'params' => [],
        'common_params' => ['rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_freetags_prefered($mod_reference, $module_params)
{
    global $user;
    $smarty = TikiLib::lib('smarty');
    if ($user) {
        $freetaglib = TikiLib::lib('freetag');
        $preferred_tags = $freetaglib->get_most_popular_tags($user, 0, $mod_reference["rows"]);
        $smarty->assign('preferred_tags', $preferred_tags);
        $smarty->assign('tpl_module_title', tra('My preferred tags'));
    }
}
