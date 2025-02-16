<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_freetags_morelikethis_info()
{
    return [
        'name' => tra('Similar-Tag Items'),
        'description' => tra('Shows content with multiple tags in common.'),
        'prefs' => ['feature_freetags'],
        'params' => [
            'type' => [
                'required' => false,
                'name' => tra('Type'),
                'description' => tra('Type of objects to extract.'),
                'filter' => 'text',
            ],
        ],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_freetags_morelikethis($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $freetaglib = TikiLib::lib('freetag');

    $out = null;
    if (isset($module_params['type'])) {
        $out = $module_params['type'];
    }

    if ($object = current_object()) {
        $morelikethis = $freetaglib->get_similar($object['type'], $object['object'], $mod_reference["rows"], $out);
        $smarty->assign('modMoreLikeThis', $morelikethis);
        $smarty->assign('module_rows', $mod_reference["rows"]);
    }

    $smarty->assign('tpl_module_title', tra("Similar pages"));
}
