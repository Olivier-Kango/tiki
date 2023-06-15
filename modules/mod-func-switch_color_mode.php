<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Return module information
 *
 * @return array
 */
function module_switch_color_mode_info()
{
    return [
        'name' => tra('Switch Color Mode'),
        'description' => tra('Switch between light, dark, and browser color scheme preference'),
        'params' => [],
    ];
}

/**
 * *TODO* Collect information about admin defined themes and apply value
 * on smarty template engine
 *
 * @param $mod_reference
 * @param $module_params
 */
function module_switch_color_mode($mod_reference, $module_params)
{
    $headerlib = TikiLib::lib('header');
    $headerlib->add_jsfile('/lib/jquery_tiki/mod-func-switch_color_mode.js');
}
