<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Lib\Logs\LogsQueryLib;

function wikiplugin_contributionsdashboard_info()
{
    return [
        'name' => tra('Contributions Dashboard'),
        'documentation' => 'PluginContributionsDashboard',
        'description' => tra('List users\' contributions to a page'),
        'prefs' => [ 'feature_trackers', 'wikiplugin_contributionsdashboard' ],
        'tags' => [ 'basic' ],
        'body' => tra('Notice'),
        'format' => 'html',
        'introduced' => 9,
        'iconname' => 'dashboard',
        'filter' => 'text',
        'params' => [
            'start' => [
                'required' => false,
                'name' => tra('Start Date'),
                'description' => tra('Default Beginning Date'),
                'since' => '9.0',
                'filter' => 'date',
                'default' => 'Today - 7 days',
            ],
            'end' => [
                'required' => false,
                'name' => tra('End Date'),
                'description' => tra('Default Ending Date'),
                'since' => '9.0',
                'filter' => 'date',
                'default' => 'Today',
            ],
            'types' => [
                'required' => true,
                'name' => tra('Dashboard Types'),
                'description' => tra('The types of charts that will be rendered, separated by commas'),
                'since' => '9.0',
                'filter' => 'text',
                'default' => 'trackeritems',
            ],
        ],
    ];
}

function wikiplugin_contributionsdashboard($data, $params)
{
    global $user;
    $headerlib = TikiLib::lib('header');
    $tikilib = TikiLib::lib('tiki');
    $trklib = TikiLib::lib("trk");
    $logsqrylib = TikiLib::lib("logsqry");
    $smarty = TikiLib::lib("smarty");

    static $iContributionsDashboard = 0;
    ++$iContributionsDashboard;
    $i = $iContributionsDashboard;

    $smarty->assign('iContributionsDashboard', $iContributionsDashboard);

    $default = [
        "start" => time() - (365 * 24 * 60 * 60),
        "end" => time(),
        "types" => "trackeritems,toptrackeritemsusers,toptrackeritemsusersip"
    ];

    $params = array_merge($default, $params);

    extract($params, EXTR_SKIP);

    $start = (! empty($_REQUEST["raphaelStart$i"]) ? ($_REQUEST["raphaelStart$i"]) : $start);
    $end = (! empty($_REQUEST["raphaelEnd$i"]) ? ($_REQUEST["raphaelEnd$i"]) : $end);

    $types = explode(',', $types);

    $headerlib->add_jsfile("vendor_bundled/vendor/jquery/jquery-sheet/plugins/raphael-min.js", true);
    $headerlib->add_jsfile("vendor_bundled/vendor/jquery/jquery-sheet/plugins/g.raphael-min.js", true);

    $usersTrackerItems = [];
    foreach ($tikilib->fetchAll("SELECT itemId FROM tiki_tracker_items WHERE createdBy = ?", ["simon"]) as $item) {
        $usersTrackerItems[] = $item['itemId'];
    }

    $headerlib->add_jq_onready(
        "$.fn.chart = function(s) {
            s = $.extend({
                labels: [],
                data: []
            }, s);

            var me = $(this);
            var r = Raphael(me[0]);

            r.g.barchart(10,10, me.width(), me.height(), [s.data])
                .on('mouseenter',unction () {
                    this.flag = r.g.popup(
                        this.bar.x,
                        this.bar.y,
                        s.labels[$(this.bar.node).index() - 2] + ' - ' + this.bar.value || '0'
                    ).insertBefore(this);
                }).on('mouseleave',function () {
                    this.flag.animate({
                        opacity: 0
                    },
                    300,
                    function () {
                        this.remove();
                    });
                });

            if (s.label) r.g.label($(this).width() / 2,30, s.label);
        };"
    );

    $result = "";
    foreach ($types as $type) {
        if ($type == "trackeritems") {
            $data = [];
            $dates = [];

            foreach (LogsQueryLib::trackerItem()->start($start)->end($end)->countByDateFilterId($usersTrackerItems) as $date => $count) {
                $data[] = $count * 1;
                $dates[] = $date;
            }

            $headerlib->add_jq_onready(
                "$('#raphaelTrackeritems$i').chart({
                    labels:     " . json_encode($dates) . ",
                    data:        " . json_encode($data) . ",
                    label:        'Tracker Item Activity Grouped By Date'
                });"
            );

            $result .= "<div id='raphaelTrackeritems$i' style='width: 100%; height: 400px; display: block;'></div>";
        }

        if ($type == "toptrackeritemsusers") {
            $hits = [];
            $users = [];

            foreach (LogsQueryLib::trackerItem()->start($start)->end($end)->countUsersFilterId($usersTrackerItems) as $user => $count) {
                $hits[] = $count;
                $users[] = $user;
            }

            $headerlib->add_jq_onready(
                "$('#raphaelTrackeritemsUsers$i').chart({
                    labels:     " . json_encode($users) . ",
                    data:        " . json_encode($hits) . ",
                    label:        'Tracker Item Activity Grouped By Users'
                });"
            );

            $result .= "<div id='raphaelTrackeritemsUsers$i' style='width: 100%; height: 400px; display: block;'></div>";
        }

        if ($type == "toptrackeritemsusersip") {
            $hits = [];
            $users = [];

            foreach (LogsQueryLib::trackerItem()->start($start)->end($end)->countUsersIPFilterId($usersTrackerItems) as $data => $count) {
                $data = json_decode($data);

                $hits[] = $count;
                $users[] = $data->user . ' ' . $data->ip;
            }

            $headerlib->add_jq_onready(
                "$('#raphaelTrackeritemsUsersIP$i').chart({
                    labels:     " . json_encode($users) . ",
                    data:        " . json_encode($hits) . ",
                    label:        'Tracker Item Activity Grouped By Users & IP Address'
                });"
            );

            $result .= "<div id='raphaelTrackeritemsUsersIP$i' style='width: 100%; height: 400px; display: block;'></div>";
        }
    }

    $tikidateStart = new TikiDate();
    $tikidateStart->setDate($start);
    $tikidateEnd = new TikiDate();
    $tikidateEnd->setDate($end);
    $timezone = $tikilib->get_display_timezone();

    $fields = [
        "fieldname" => "raphaelStart{$i}",
        "endfieldname" => "raphaelEnd{$i}",
        "date" => $start,
        "enddate" => $end,
        "timezone" => $timezone,
    ];
    return "
            <style>
                .header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    font-size: 16px;
                    padding: 1px 10px;
                    margin: 0;
                }
                .headerHelper {
                    display: flex;
                    align-items: center;
                    column-gap: 3px;
                    font-size: 12px;
                    flex-basis: 40%;
                }
                .headerAction {
                    align-self: normal;
                }
                .headerHelper > :nth-child(1) {
                    flex-basis: 20%;
                }
                .headerHelper > :nth-child(2) {
                    flex-basis: 75%;
                }
            </style>
            <div class='ui-widget ui-widget-content ui-corner-all'>
                <h3 class='header ui-state-default ui-corner-tl ui-corner-tr'>
                    " . tr("Contributions Dashboard") . "
                    <form class='headerHelper'>
                        <span>" . tr("Date Range") . "</span>
                        <span>" . smarty_function_jscalendar($fields, $smarty->getEmptyInternalTemplate()) . "</span>
                        <input type='hidden' name='refresh' value='1' />
                        <input type='submit' id='raphaelUpdate$i' class='headerAction' value='" . tr("Update") . "' />
                    </form>
                </h3>
                $result
            </div>";
}
