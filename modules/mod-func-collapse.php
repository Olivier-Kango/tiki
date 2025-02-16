<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_collapse_info()
{
    return [
        'name' => tr('Collapse Button'),
        'description' => tr('Bootstrap collapse button.'),
        'params' => [
            'target' => [
                'required' => true,
                'name' => tr('Target'),
                'description' => tr('CSS selector defining which objects get collapsed.'),
                'filter' => 'xss',
            ],
            'containerclass' => [
                'required' => false,
                'name' => tr('CSS Class'),
                'description' => tr('CSS class for containing DIV element'),
                'filter' => 'text',
                'default' => 'navbar-header',
            ],
            'parent' => [
                'required' => false,
                'name' => tr('Parent'),
                'description' => tr("CSS selector defining the collapsing objects' container."),
                'filter' => 'xss',
            ],
        ],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_collapse($mod_reference, $module_params)
{
}
