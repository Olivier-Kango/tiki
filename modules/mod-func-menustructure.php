<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_menustructure_info()
{
    return [
        'name' => tra('Wiki Structure Menu'),
        'description' => tra('Displays a structure.') . ' ' . tra('N.B. Deprecated, use the Menu module instead'),
        'prefs' => ['feature_wiki_structure'],
        'params' => [
            'structure' => [
                'name' => tra('Structure'),
                'description' => tra('Structure to display in the menu.'),
                'required' => true
            ]
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_menustructure($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $structure = $module_params['structure'];

    if (! empty($structure)) {
        $structlib = TikiLib::lib('struct');
        $smarty->assign('tpl_module_title', $structure);

        $structureId = $structlib->get_struct_ref_id($structure);

        if ($structureId) {
            $smarty->assign_by_ref('structureId', $structureId);
        }
    }
}
