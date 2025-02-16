<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_groupstat_info()
{
    return [
        'name' => tra('Group Stats'),
        'documentation' => 'PluginGroupStat',
        'description' => tra('Show the distribution of users among groups'),
        'body' => tra('Title'),
        'iconname' => 'group',
        'introduced' => 4,
        'params' => [
            'groups' => [
                'required' => false,
                'name' => tra('Groups'),
                'description' => tra('Select one or more groups. If none selected, all groups will be listed.'),
                'since' => '4.0',
                'separator' => ':',
                'profile_reference' => 'group',
            ],
            'percent_of' => [
                'required' => false,
                'name' => tra('Percentage of'),
                'description' => tra('Show percentage out of all users in site, or just those specified in the groups
                    parameter.'),
                'since' => '8.0',
                'default' => 'groups',
                'options' => [
                    ['text' => tra('Users in groups'), 'value' => 'groups'],
                    ['text' => tra('Site users'), 'value' => 'site']
                ]
            ],
            'show_percent' => [
                'required' => false,
                'name' => tra('Show Percentage'),
                'description' => tra('Show the percentage of total users that are members of each group (percentages
                    are shown by default)'),
                'since' => '4.0',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'show_bar' => [
                'required' => false,
                'name' => tra('Show Bar'),
                'description' => tra('Represent the percentage of total users that are members of each group in a bar
                    graph (default is not to show the bar graph)'),
                'since' => '4.0',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
        ],
    ];
}

function wikiplugin_groupstat($data, $params)
{
    global $prefs;
    $userlib = TikiLib::lib('user');
    $tikilib = TikiLib::lib('tiki');
    $smarty = TikiLib::lib('smarty');

    if (isset($params['groups'])) {
        $groups = $params['groups'];
        if (isset($params['percent_of']) && $params['percent_of'] == 'site') {
            $total = $userlib->nb_users_in_group();
        } else {
            $query = 'SELECT COUNT(DISTINCT `userId`) FROM `users_usergroups` WHERE `groupName` IN(' . implode(',', array_fill(0, count($groups), '?')) . ')';
            $total = $tikilib->getOne($query, $groups);
        }
    } else {
        $groups = $userlib->list_all_groups();
        $total = $userlib->nb_users_in_group();
    }
    $stats = [];
    foreach ($groups as $group) {
        $nb = $userlib->nb_users_in_group($group);
        $stats[] = ['group' => $group, 'nb' => $nb];
    }
    foreach ($stats as $i => $stat) {
        $stats[$i]['percent'] = ($stat['nb'] * 100) / $total;
    }
    $smarty->assign_by_ref('params', $params);
    $smarty->assign_by_ref('stats', $stats);
    return "~np~" . $smarty->fetch('wiki-plugins/wikiplugin_groupstat.tpl') . "~/np~";
}
