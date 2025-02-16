<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_top_objects_info()
{
    return [
        'name' => tra('Top Objects'),
        'description' => tra('Displays the specified number of objects, starting with the one having the most hits.'),
        'prefs' => ['feature_stats'],
        'params' => [],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_top_objects($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $statslib = TikiLib::lib('stats');

    $best_objects_stats = $statslib->best_overall_object_stats($mod_reference["rows"]);

    $smarty->assign('modTopObjects', $best_objects_stats);
}
