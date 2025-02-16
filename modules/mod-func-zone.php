<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_zone_info()
{
    return [
        'name' => tr('Module Zone'),
        'description' => tr('Can contain other modules so can be used as a Bootstrap navbar object, for example.'),
        'params' => [
            'name' => [
                'required' => true,
                'name' => tr('Zone Name'),
                'description' => tr('Must be unique; the zone becomes an "extra module zone" and will appear in the admin modules panel.'),
                'filter' => 'text',
                'default' => '',
            ],
            'zoneclass' => [
                'required' => false,
                'name' => tr('CSS Class'),
                'description' => tr('Example for a Bootstrap "social" navbar:') . ' "navbar navbar-inverse navbar-fixed-top"',
                'filter' => 'text',
                'default' => '',
            ],
            'accordion' => [
                'required' => false,
                'name' => tr('Accordion'),
                'description' => tr('Enable accordion behavior.'),
                'filter' => 'text',
                'default' => 'n',
            ],
        ],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_zone($mod_reference, $module_params)
{
    global $prefs;

    $modlib = TikiLib::lib('mod');

    if (! array_key_exists($module_params['name'], $modlib->module_zones)) {
        if (! in_array($module_params['name'], array_filter((array) $prefs['module_zone_available_extra']))) {
            $prefs['module_zone_available_extra'][] = $module_params['name'];
            TikiLib::lib('tiki')->set_preference('module_zone_available_extra', $prefs['module_zone_available_extra']);
            $modlib = new ModLib();
        }
    }
}
