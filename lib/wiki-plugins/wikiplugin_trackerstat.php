<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_trackerstat_info()
{
    return [
        'name' => tra('Tracker Stats'),
        'documentation' => 'PluginTrackerStat',
        'description' => tra('Display statistics about a tracker.'),
        'prefs' => [ 'feature_trackers', 'wikiplugin_trackerstat' ],
        'body' => tra('Title'),
        'iconname' => 'chart',
        'introduced' => 2,
        'params' => [
            'trackerId' => [
                'required' => true,
                'name' => tra('Tracker ID'),
                'description' => tra('Numeric value representing the tracker ID'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker',
            ],
            'fields' => [
                'required' => false,
                'name' => tra('Fields'),
                'description' => tra('Colon-separated list of field IDs to be displayed. Example:')
                    . ' <code>2:4:5</code>' . tra('. ') . tra('Leave it empty to display all fields from this tracker.'),
                'since' => '2.0',
                'default' => '',
                'separator' => ':',
                'profile_reference' => 'tracker_field',
            ],
            'show_count' => [
                'required' => false,
                'name' => tra('Show Count'),
                'description' => tra('Choose whether to show the count of votes each option received (shown by default)'),
                'since' => '10.3',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'show_percent' => [
                'required' => false,
                'name' => tra('Show Percentage'),
                'description' => tra('Choose whether to show the percentage of the vote each option received (not shown
                    by default)'),
                'since' => '2.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'show_bar' => [
                'required' => false,
                'name' => tra('Show Bar'),
                'description' => tra('Choose whether to show a bar representing the number of votes each option received
                    (not shown by default)'),
                'since' => '2.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'status' => [
                'required' => false,
                'name' => tra('Status Filter'),
                'description' => tra('Only show items matching certain status filters'),
                'since' => '2.0',
                'filter' => 'alpha',
                'default' => 'o',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Open'), 'value' => 'o'],
                    ['text' => tra('Pending'), 'value' => 'p'],
                    ['text' => tra('Closed'), 'value' => 'c'],
                    ['text' => tra('Open & Pending'), 'value' => 'op'],
                    ['text' => tra('Open & Closed'), 'value' => 'oc'],
                    ['text' => tra('Pending & Closed'), 'value' => 'pc'],
                    ['text' => tra('Open, Pending & Closed'), 'value' => 'opc'],
                ],
            ],
            'show_link' => [
                'required' => false,
                'name' => tra('Show Link'),
                'description' => tra('Add a link to the tracker'),
                'since' => '3.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'show_lastmodif' => [
                'required' => false,
                'name' => tra('Last Modification Date'),
                'description' => tr(
                    'Show last modification date of a tracker. Set to Yes (%0) to use site setting for
                    the short date format or use PHP\'s format (www.php.net/strftime). Example:',
                    '<code>y</code>',
                    '<code>%A %d of %B, %Y</code>'
                ),
                'since' => '5.0',
                'filter' => 'text',
                'default' => '',
            ]
        ]
    ];
}

function wikiplugin_trackerstat($data, $params)
{
    global $prefs, $tiki_p_admin_trackers;
    $trklib = TikiLib::lib('trk');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');
    extract($params, EXTR_SKIP);

    if ($prefs['feature_trackers'] != 'y' || ! isset($trackerId) || ! ($tracker_info = $trklib->get_tracker($trackerId))) {
        return $smarty->fetch("wiki-plugins/error_tracker.tpl");
    }
    $perms = Perms::get(['type' => 'tracker', 'object' => $trackerId]);
    if (! $perms->view_trackers) {
        return tra('Permission denied');
    }
    if (! empty($show_lastmodif)) {
        $date = $trklib->lastModif($trackerId);
        if (! function_exists('smarty_modifier_tiki_date_format')) {
            include('lib/smarty_tiki/modifier.tiki_date_format.php');
        }
        if ($show_lastmodif == 'y') {
            $show_lastmodif = $prefs['short_date_format'];
        }
        return smarty_modifier_tiki_date_format($date, tra($show_lastmodif));
    }

    if (! isset($status)) {
        $status = 'o';
    } elseif (! $trklib->valid_status($status)) {
        return tra('invalid status');
    }

    if (isset($show_count) && $show_count == 'n') {
        $smarty->assign('show_count', 'n');
    } else {
        $smarty->assign('show_count', 'y');
    }
    if (isset($show_percent) && $show_percent == 'y') {
        $average = 'y';
        $smarty->assign('show_percent', 'y');
    } else {
        $smarty->assign('show_percent', 'n');
    }
    if (isset($show_bar) && $show_bar == 'y') {
        $average = 'y';
        $smarty->assign('show_bar', 'y');
    } else {
        $smarty->assign('show_bar', 'n');
    }
    if (isset($show_link) && $show_link == 'y') {
        $smarty->assign('show_link', 'y');
    } else {
        $smarty->assign('show_link', 'n');
    }

    $allFields = $trklib->list_tracker_fields($trackerId, 0, -1, 'position_asc', '');
    for ($iUser = count($allFields['data']) - 1; $iUser >= 0; $iUser--) {
        if ($allFields['data'][$iUser]['type'] == 'u') { // this tracker has a user field - can look for the value the user sets
            break;
        }
    }
    if ($iUser <= -1) {
        for ($iIp = count($allFields['data']) - 1; $iIp >= 0; $iIp--) {
            if ($allFields['data'][$iIp]['type'] == 'I') { // this tracker has a IP field - can look for the value the user sets
                break;
            }
        }
    }
    if (! empty($fields)) {
        $listFields = $fields;
    } else {
        foreach ($allFields['data'] as $f) {
            $listFields[] = $f['fieldId'];
        }
    }

    if ($t = $trklib->get_tracker_options($trackerId)) {
        $tracker_info = array_merge($tracker_info, $t);
    }

    $status_types = $trklib->status_types();

    foreach ($listFields as $fieldId) {
        $v = [];
        for ($i = count($allFields['data']) - 1; $i >= 0; $i--) {
            if ($allFields['data'][$i]['fieldId'] == $fieldId) {
                break;
            }
        }
        if ($i < 0) {
            return tra('incorrect fieldId') . ' ' . $fieldId;
        }
        if ($allFields['data'][$i]['type'] == 'I' || $allFields['data'][$i]['type'] == 's') {
            continue;
        }
        if (! ($allFields['data'][$i]['isHidden'] == 'n' || $allFields['data'][$i]['isHidden'] == 'p' || ($allFields['data'][$i]['isHidden'] == 'y' && $tiki_p_admin_trackers == 'y'))) {
            continue;
        }
        if ($allFields['data'][$i]['type'] == 'e') {
            $categlib = TikiLib::lib('categ');
            $parent = (int) $allFields['data'][$i]['options']; // FIXME: Lazy access to the first option. Only works when a field only has its first option set.
            if ($parent > 0) {
                $filter = ['identifier' => $parent, 'type' => 'children'];
                $listCategs = $categlib->getCategories($filter, true, false);
            } else {
                $listCategs = [];
            }
            if ($tracker_info['oneUserItem'] == 'y') {
                $itemId = $trklib->get_user_item($trackerId, $tracker_info);
            }
            $j = 0;
            foreach ($listCategs as $category) {
                $objects = $categlib->deprecatedGetCategoryObjectsRows([$category]['categId'], 'trackeritem', deprecatedFilter: ['table' => 'tiki_tracker_items', 'join' => 'itemId', 'filter' => 'trackerId', 'bindvars' => $trackerId]);
                if ($status == 'opc' || $tracker_info['showStatus'] == 'n') {
                    $v[$j]['count'] = count($objects);
                } else {
                    $v[$j]['count'] = 0;
                    foreach ($objects as $o) {
                        $s = $trklib->get_item_info($o['itemId']);
                        if (strstr($status, $s['status']) !== false) {
                            ++$v[$j]['count'];
                        }
                    }
                }
                $v[$j]['value'] = $category['name'];
                if ($tracker_info['oneUserItem'] == 'y') {
                    foreach ($objects as $o) {
                        if ($o['itemId'] == $itemId) {
                            $v[$j]['me'] = 'y';
                            break;
                        }
                    }
                }
                $v[$j]['href'] = "trackerId=$trackerId&amp;filterfield=$fieldId&amp;filtervalue[$fieldId][]=" . $category['categId'];
                $j++;
            }
        } elseif ($allFields['data'][$i]['type'] == 'h') {//header
            $stat['name'] = $allFields["data"][$i]['name'];
            $stat['values'] = [];
            $stats[] = $stat;
            continue;
        } else {
            if ($iUser >= 0) {
                global $user;
                $userValues = $trklib->get_filtered_item_values($allFields['data'][$iUser]['fieldId'], $user, $allFields['data'][$i]['fieldId']);
            } elseif ($iIp >= 0) {
                $userValues = $trklib->get_filtered_item_values($allFields['data'][$iIp]['fieldId'], $tikilib->get_ip_address(), $allFields['data'][$i]['fieldId']);
            }

            $allValues = $trklib->get_all_items($trackerId, $fieldId, $status);
            $j = -1;
            foreach ($allValues as $value) {
                $value = trim($value);
                if ($j < 0 || $value != $v[$j]['value']) {
                    ++$j;
                    $v[$j]['value'] = $value;
                    $v[$j]['count'] = 1;
                    if (isset($userValues) && in_array($value, $userValues)) {
                        $v[$j]['me'] = 'y';
                    }
                    $v[$j]['href'] = "trackerId=$trackerId&amp;filterfield=$fieldId&amp;filtervalue[$fieldId]=" . urlencode($value);
                } else {
                    ++$v[$j]['count'];
                }
            }
        }
        if (isset($average) && ! empty($v)) {
            for (; $j >= 0; --$j) {
                if (isset($v[$j])) {
                    $v[$j]['average'] = 100 * $v[$j]['count'] / array_sum(array_map(function ($v) {
                        return $v['count'];
                    }, $v));
                    if ($tracker_info['showStatus'] == 'y') {
                        $v[$j]['href'] .= "&amp;status=$status";
                    }
                }
            }
        }
        if (! empty($v)) {
            $stat['name'] = $allFields['data'][$i]['name'];
            $stat['values'] = $v;
            $stats[] = $stat;
        }
        unset($v);
    }
    $smarty->assign_by_ref('stats', $stats);
    return '~np~' . $smarty->fetch('wiki-plugins/wikiplugin_trackerstat.tpl') . '~/np~';
}
