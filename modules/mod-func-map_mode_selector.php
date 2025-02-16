<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_map_mode_selector_info()
{
    return [
        'name' => tra('Mode Selector'),
        'description' => tra("Toggle input modes for the map."),
        'prefs' => [],
        'params' => [
        ],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_map_mode_selector($mod_reference, $module_params)
{
}
