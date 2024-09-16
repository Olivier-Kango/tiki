<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_elementplus_list()
{
    return [
        'elementplus_select_clearable' => [
            'name' => tra('Clearable Select'),
            'description' => tra('whether select can be cleared'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'feature_elementplus'
            ]
        ],
        'elementplus_select_collapse_tags' => [
            'name' => tra('Collapsible Tags'),
            'description' => tra('whether to collapse tags to a text when multiple selecting'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'feature_elementplus'
            ]
        ],
        'elementplus_select_max_collapse_tags' => [
            'name' => tra('Max Collapse Tags'),
            'description' => tra('max tags to show when collapsed'),
            'type' => 'text',
            'default' => '3',
            'dependencies' => [
                'elementplus_select_collapse_tags',
                'feature_elementplus'
            ]
        ],
        'elementplus_select_filterable' => [
            'name' => tra('Filterable Select'),
            'description' => tra('whether select can be filtered'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'feature_elementplus'
            ]
        ],
        'elementplus_select_allow_create' => [
            'name' => tra('Allow Create'),
            'description' => tra('whether creating new items is allowed'),
            'type' => 'flag',
            'default' => 'n',
            'dependencies' => [
                'elementplus_select_filterable',
                'feature_elementplus'
            ],
        ],
    ];
}
