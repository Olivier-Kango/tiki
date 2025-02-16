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
function module_calendar_new_info()
{
    return [
        'name' => tra('Calendar'),
        'description' => tra('Includes a calendar or a list of calendar events.'),
        'prefs' => ['feature_calendar'],
        'documentation' => 'Module calendar_new',
        'params' => [
            'calIds' => [
                'name' => tra('Calendars filter'),
                'description' => tra('If set to a list of calendar identifiers, restricts the events to those in the identified calendars. Identifiers are separated by vertical bars ("|"), commas (",") or colons (":"). Example values: "13", "4,7", "31:49". Not set by default.'),
                'profile_reference' => 'calendar',
            ],
            'month_delta' => [
                'name' => tra('Displayed month (relative)'),
                'description' => tra('Distance in month to the month to display. A distance of -1 would display the previous month. Setting this option implies a calendar view type with a month time span. Example values: 2, 0, -2, -12.'),
                'filter' => 'int',
            ],
            'viewlist' => [
                'name' => tra('View type'),
                'description' => tr('Determines how to show events. Possible values: %0, %1.', 'table', 'list'),
                'filter' => 'word',
                'default' => 'table',
            ],
            'viewmode' => [
                'name' => tra('Calendar view type time span'),
                'description' => tr(
                    'If in calendar (or "table") view type, determines the time span displayed by the calendar. Possible values: %0, %1, %2, %3 ,%4, %5. A user changing this time span in the calendar can change the time span the module displays for him.',
                    'year',
                    'semester',
                    'quarter',
                    'month',
                    'week',
                    'day'
                ),
                'filter' => 'word',
                'default' => 'month',
            ],
            'showaction' => [
                'name' => tra('Show action'),
                'description' => 'y|n',
                'filter' => 'word',
                'default' => 'y',
            ],
            'viewmodelink' => [
                'name' => tra('Viewmode when clicking on a day'),
                'description' => 'week|day',
                'filter' => 'word',
                'default' => 'week',
            ],
            'linkall' => [
                'name' => tra('Put a link on all the days , not only those with event'),
                'description' => 'y|n',
                'filter' => 'word',
                'default' => 'n',
            ],
            'viewnavbar' => [
                'name' => tra('View navigation bar'),
                'description' => 'y|n|partial',
                'filter' => 'word',
                'default' => 'y',
            ]
        ]
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_calendar_new($mod_reference, $module_params)
{
    global $prefs, $user, $tiki_p_admin_calendars;
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');
    $calendarlib = TikiLib::lib('calendar');
    $userlib = TikiLib::lib('user');
    global $calendarViewMode, $focusdate;
    $default = ['viewnavbar' => 'y', 'viewmodelink' => 'week', 'showaction' => 'y', 'linkall' => 'n'];
    $module_params = array_merge($default, $module_params);
    TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-calendar_edit_item.js');

    if (isset($_REQUEST['viewmode'])) {
        $save_viewmode = $_REQUEST['viewmode'];
    }
    if (! empty($module_params['viewmode'])) {
        $calendarViewMode['casedefault'] = $module_params['viewmode'];
    }

    if (isset($_REQUEST['todate'])) {
        $save_todate = $_REQUEST['todate'];
    }

    if (isset($module_params['month_delta'])) {
        $calendarViewMode['casedefault'] = 'month';
        list($focus_day, $focus_month, $focus_year) = [
            TikiLib::date_format("%d", $focusdate),
            TikiLib::date_format("%m", $focusdate),
            TikiLib::date_format("%Y", $focusdate)
        ];
        $_REQUEST['todate'] = $tikilib->make_time(0, 0, 0, $focus_month + $module_params['month_delta'], 1, $focus_year);
    }

    if (! empty($module_params['calIds'])) {
        $calIds = $module_params['calIds'];
        if (! is_array($module_params['calIds'])) {
            $calIds = preg_split('/[\|:\&,]/', $calIds);
        }
    } elseif (! empty($_SESSION['CalendarViewGroups'])) {
        $calIds = $_SESSION['CalendarViewGroups'];
    } elseif ($prefs['feature_default_calendars'] == 'n') {
        $calendars = $calendarlib->list_calendars();
        $calIds = array_keys($calendars['data']);
    } elseif (! empty($prefs['default_calendars'])) {
        $calIds = $_SESSION['CalendarViewGroups'] = is_array($prefs['default_calendars']) ? $prefs['default_calendars'] : unserialize($prefs['default_calendars']);
    } else {
        $calIds = [];
    }


    $_REQUEST['gbi'] = 'y';
    if (! empty($module_params['viewlist'])) {
        $_REQUEST['viewlistmodule'] = $module_params['viewlist'];
    } else {
        $_REQUEST['viewlistmodule'] = 'table';
    }

    foreach ($calIds as $i => $cal_id) {
        if ($tiki_p_admin_calendars != 'y' && ! $userlib->user_has_perm_on_object($user, $cal_id, 'calendar', 'tiki_p_view_calendar')) {
            unset($calIds[$i]);
        }
    }

    if (! empty($calIds)) {
        $tc_infos = $calendarlib->getCalendar($calIds, $viewstart, $viewend, 'day', 'events', true);
        if ($_REQUEST['viewlistmodule'] == 'list') {
            foreach ($tc_infos['listevents'] as $i => $e) {
                $tc_infos['listevents'][$i]['head'] = '';
                $tc_infos['listevents'][$i]['group_description'] = '';
            }
        }

        foreach ($tc_infos as $tc_key => $tc_val) {
            $smarty->assign($tc_key, $tc_val);
        }

        $smarty->assign('name', 'calendar_new');

        $smarty->assign('daformat2', $tikilib->get_long_date_format());
        $smarty->assign('var', '');
        $smarty->assign('myurl', 'tiki-calendar.php');
        $smarty->assign('show_calendar_module', 'y');
        $smarty->assign_by_ref('viewmodelink', $module_params['viewmodelink']);
        $smarty->assign_by_ref('linkall', $module_params['linkall']);
        $smarty->assign('calendarViewMode', $calendarViewMode['casedefault']);

        if (isset($save_todate)) {
            $_REQUEST['todate'] = $save_todate;
        } else {
            unset($_REQUEST['todate']);
        }
    }
}
