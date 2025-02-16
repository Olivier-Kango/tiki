<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

/**
 * @return array
 */
function module_last_category_objects_info()
{
    return [
        'name' => tra('Newest Category Items'),
        'description' => tra('Lists the specified number of objects of the given type in the given category, starting from the most recently created.'),
        'prefs' => ['feature_categories'],
        'params' => [
            'id' => [
                'name' => tra('Category identifier'),
                'description' => tra('Identifier of the category from which objects are listed. Objects merely in child categories will not be displayed.') .
                                " " . tra('Example value: 13.'),
                'filter' => 'int',
                'required' => true,
                'profile_reference' => 'category',
            ],
            'maxlen' => [
                'name' => tra('Maximum length'),
                'description' => tra('Maximum number of characters in object names allowed before truncating.'),
                'filter' => 'int',
            ],
            'type' => [
                'name' => tra('Object type filter'),
                'description' => tra('Type of the objects to list. Example values:') . ' *, ' . tra('wiki page') . ', ' . tra('article') . ', ' . tra('faq') . ', blog, image, ' . tra('file gallery') . ', ' . tra('tracker') . ', ',tra('trackerItem') . ', ' . tra('quiz') . ', ' . tra('poll') . ', ' . tra('survey') . ', ' . tra('sheet') . '. ' . tra('Default value:') . tra(' wiki page'),
                'filter' => 'striptags',
            ]
        ],
        'common_params' => ['rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_last_category_objects($mod_reference, $module_params)
{
    if (! isset($module_params['type'])) {
        $module_params['type'] = 'wiki page';
    }

    if ($module_params['type'] == '*') {
        $module_params['type'] = '';
    }

    $smarty = TikiLib::lib('smarty');
    $categlib = TikiLib::lib('categ');

    $last = $categlib->last_category_objects($module_params['id'], $mod_reference['rows'], $module_params['type']);

    $categperms = Perms::get(['type' => 'category', 'object' => $module_params['id']]);
    $jail = $categlib->get_jail();
    $smarty->assign(
        'mod_can_view',
        $categperms->view_category && (empty($jail) || in_array($module_params['id'], $jail))
    );

    if (! is_array($last) or ! is_array($last['data'])) {
        $last['data'][]['name'] = tra('no object here yet');
    }

    $smarty->assign('last', $last['data']);
    $smarty->assign('type', $module_params['type']);
    $smarty->assign('maxlen', isset($module_params['maxlen']) ? $module_params['maxlen'] : 0);
}
