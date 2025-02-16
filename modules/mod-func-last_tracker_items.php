<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_last_tracker_items_info()
{
    return [
        'name' => tra('Newest Tracker Items'),
        'description' => tra('Displays the value of a field of the specified number of tracker items. If the specified tracker has no main field, either Field name or Field identifier is required.'),
        'prefs' => ['feature_trackers'],
        'params' => [
            'trackerId' => [
                'name' => tra('Tracker identifier'),
                'description' => tra('Identifier of the tracker from which items are listed.') . " " . tra('Example value: 13.'),
                'filter' => 'int',
                'required' => true,
                'profile_reference' => 'tracker',
            ],
            'fieldId' => [
                'name' => tra('Field identifier'),
                'description' => tra('Identifier of the field from which values are listed. If Field name is set, this parameter is ignored.') . " " . tra('Example value: 13.'),
                'filter' => 'int',
                'profile_reference' => 'tracker_field',
            ],
            'name' => [
                'name' => tra('Field name'),
                'description' => tra('Name of the field from which values are listed.') . " " . tra('Example value: age.'),
                'filter' => 'striptags',
            ],
            'sort_mode' => [
                'name' => tra('Sort'),
                'description' => tra('Specifies how the items should be sorted.') . " " . tra('Possible values include created and created_asc (equivalent), created_desc, status, lastModif, createdBy and lastModifBy. Unless "_desc" is specified, the sort is ascending. "created" sorts on item creation date. "lastModif" sorts on the last modification date of items. "lastModif_desc" sorts in descending order of last modification date.') . ' ' . tra('Default value:') . " created_desc",
                'filter' => 'striptags',
            ],
            'status' => [
                'name' => tra('Status filter'),
                'description' => tra('If set, limits the listed items to those with the given statuses. Values are combinations of "o" (open), "p" (pending) and "c" (closed). Possible values:') . ' opc, oc, op, pc, o, p or c. ' . tra('Default value:') . ' opc',
                'filter' => 'word',
            ]
        ],
        'common_params' => ['rows', 'nonums']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_last_tracker_items($mod_reference, $module_params)
{
    global $prefs, $user;

    $tikilib = TikiLib::lib('tiki');
    $trklib = TikiLib::lib('trk');
    $smarty = TikiLib::lib('smarty');
    $smarty->assign('module_error', '');
    if (isset($module_params['trackerId'])) {
        if ($tikilib->user_has_perm_on_object($user, $module_params['trackerId'], 'tracker', 'tiki_p_view_trackers')) {
            if (isset($module_params['name'])) {
                $module_params['fieldId'] = $trklib->get_field_id($module_params['trackerId'], $module_params['name']);
            }
            if (empty($module_params['fieldId']) && isset($module_params['trackerId'])) {
                $definition = Tracker_Definition::get($module_params['trackerId']);
                if ($definition) {
                    $module_params['fieldId'] = $definition->getMainFieldId();
                }
            }
            if (empty($module_params['fieldId'])) {
                $smarty->assign('module_error', tra('Unable to determine which field to show. Tracker identifier may be invalid, or the tracker has no main field and neither Field identifier nor Field name were set.'));
            } else {
                $field_info = $trklib->get_tracker_field($module_params['fieldId']);
                if (! isset($module_params['status'])) {
                    $module_params['status'] = '';
                }
                if (empty($module_params['sort_mode'])) {
                    $module_params['sort_mode'] = 'created_desc';
                }
                $modLastItems = [];
                //list_items filters the fieldId if hidden...
                $tmp = $trklib->list_items($module_params['trackerId'], 0, $mod_reference["rows"], $module_params['sort_mode'], [$module_params['fieldId'] => $field_info], '', '', $module_params['status']);
                foreach ($tmp['data'] as $data) {
                    if (! empty($data['field_values'][0]['value'])) {
                        $data['subject'] = $data['field_values'][0]['value'];
                        $baseUrl = "tiki-view_tracker_item.php?itemId={$data['itemId']}";
                        $data['sefurl'] = ($prefs['feature_sefurl'] === 'y')
                            ? filter_out_sefurl($baseUrl, 'trackeritem', '', null, 'y')
                            : $baseUrl;
                        $modLastItems[] = $data;
                    }
                }
                $smarty->assign_by_ref('modLastItems', $modLastItems);
            }
        } else {
            $smarty->assign('module_error', tra('You do not have permission to view this tracker.'));
        }
    } else {
        $smarty->assign('module_error', tra('Unable to determine which field to show. Tracker identifier may be invalid, or the tracker has no main field and neither Field identifier nor Field name were set.'));
    }
    $smarty->assign('tpl_module_title', tra("Last Items"));
    if (empty($module_params['sort_mode'])) {
        $module_params['sort_mode'] = 'created_desc';
    }
    if (! strcasecmp($module_params['sort_mode'], 'lastModif_desc')) {
        $smarty->assign('tpl_module_title', tra("Last modified Items"));
    }
}
