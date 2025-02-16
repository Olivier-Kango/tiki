<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_trackerhelp_info()
{
    return [
        'name' => tra('Tracker Help'),
        'description' => tra('Display the fields of a tracker (name and identifier)'),
        'prefs' => ["feature_trackers"],
        'params' => [
            'height' => [
                'name' => tra('Text field height'),
                'description' => tra('Number of lines'),
                'filter' => 'int'
            ],
            'cols' => [
                'name' => tra('Text field width'),
                'description' => tra('Number of characters'),
                'filter' => 'int'
            ],
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_trackerhelp($mod_reference, &$module_params)
{
    $smarty = TikiLib::lib('smarty');
    $default = ['height' => 4, 'cols' => 23];
    $module_params = array_merge($default, $module_params);
    if (! empty($_REQUEST['trackerhelp'])) {
        $trklib = TikiLib::lib('trk');
        $trackerId = $trklib->get_tracker_by_name($_REQUEST['trackerhelp_name']);
        if (empty($trackerId)) {
            $tracker_info = $trklib->get_tracker($_REQUEST['trackerhelp_name']);
            if (! empty($tracker_info)) {
                $trackerId = $tracker_info['trackerId'];
                $_REQUEST['trackerhelp_name'] = $tracker_info['name'];
            }
        }
        if (! empty($trackerId)) {
            $objectperms = Perms::get(['type' => 'tracker', 'object' => $trackerId]);
        }
        if (empty($trackerId) || ! $objectperms->view_trackers) {
            $_SESSION['trackerhelp_name'] = '';
            $_SESSION['trackerhelp_id'] = 0;
            $_SESSION['trackerhelp_text'] = [];
            $_SESSION['trackerhelp_pretty'] = [];
        } else {
            $_SESSION['trackerhelp_id'] = $trackerId;
            $_SESSION['trackerhelp_name'] = $_REQUEST['trackerhelp_name'];
            $fields = $trklib->list_tracker_fields($trackerId, 0, -1);
            $_SESSION['trackerhelp_text'] = [];
            $_SESSION['trackerhelp_pretty'] = [];
            foreach ($fields['data'] as $field) {
                $_SESSION['trackerhelp_text'][] = $field['fieldId'] . ':' . $field['name'];
                $_SESSION['trackerhelp_pretty'][] = $field['name'] . ' {$f_' . $field['fieldId'] . '}';
            }
        }
    }
    if (! empty($_SESSION['trackerhelp_text']) && count($_SESSION['trackerhelp_text']) < $module_params['height']) {
        $module_params['height'] = count($_SESSION['trackerhelp_text']);
    }

    $smarty->assign('tpl_module_title', tra('Tracker Help'));
}
