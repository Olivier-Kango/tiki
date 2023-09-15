<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @return array
 */
function module_pagetop_hero_info()
{
    return [
        'name' => tr('Page Topbar Hero'),
        'description' => tr('An easy page-top hero section located in Tiki\'s topbar module zone'),
        'params' => [
            'pagetitle' => [
                'required' => false,
                'name' => tr('Page title'),
                'description' => tr('Page title'),
            ],
            'description' => [
                'required' => false,
                'name' => tr('Page description'),
            ],
            'breadcrumbs' => [
                'required' => false,
                'name' => tr('Breadcrumbs'),
                'description' => tr('Allows you to specify the navigation paths to arrive at the current page, Separate items to display with commas'),
            ],
            'content_position' => [
                'required' => false,
                'name' => tra('Content position'),
                'description' => tra('Content position inside the hero image'),
                'default' => 'center',
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Center'), 'value' => 'center'],
                    ['text' => tra('Left-Center'), 'value' => 'leftcenter'],
                    ['text' => tra('Top-Left'), 'value' => 'topleft'],
                    ['text' => tra('Top-Center'), 'value' => 'topcenter'],
                    ['text' => tra('Top-Right'), 'value' => 'topright'],
                    ['text' => tra('Bottom-Left'), 'value' => 'bottomleft'],
                    ['text' => tra('Bottom-Center'), 'value' => 'bottomcenter'],
                    ['text' => tra('Bottom-Right'), 'value' => 'bottomright'],
                ],
            ],
            'bgimage' => [
                'required' => false,
                'name' => tra('Page Topbar Hero background image URL'),
                'description' => tra('Enter image URL, in the case of a single image.'),
            ],
            'usepagename' => [
                'required' => false,
                'name' => tr('Use page title'),
                'description' => tra('Allows the page title to be used as title of the pagetop module (y|n)'),
                'default' => 'n'
            ]
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_pagetop_hero($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');

    $pagetitle = '';
    $breadcrumbs = [];
    if ($module_params["usepagename"] == 'y') {
        $pagetitle = $_REQUEST['page'];
        $breadcrumbs[] = tra("Home");
        $breadcrumbs[] = $pagetitle;
    } else {
        $pagetitle = $module_params["pagetitle"];
        if (! empty($module_params["breadcrumbs"])) {
            $breadcrumbs = isset($module_params["breadcrumbs"]) ? explode(",", $module_params["breadcrumbs"]) : [];
            $breadcrumbs[] = $pagetitle;
        }
    }

    $smarty->assign('pagetitle', $pagetitle);
    $smarty->assign('description', isset($module_params["description"]) ? $module_params["description"] : '');
    $smarty->assign('content_position', isset($module_params["content_position"]) ? $module_params["content_position"] : 'center');
    $smarty->assign('breadcrumbs', $breadcrumbs);
    $smarty->assign('bgimage', isset($module_params["bgimage"]) ? $module_params["bgimage"] : '');
}
