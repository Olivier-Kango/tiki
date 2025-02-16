<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_goal_list($partial = false)
{
    return [
        'goal_enabled' => [
            'name' => tr('Goal, recognition and rewards'),
            'description' => tr('A tool to set motivational goals to increase engagement on the site.'),
            'type' => 'flag',
            'default' => 'n',
            'admin' => $partial ?: TikiLib::lib('service')->getUrl(['controller' => 'goal', 'action' => 'admin']),
            'help' => 'Goals',
        ],
        'goal_badge_tracker' => [
            'name' => tr('Reward badge tracker'),
            'description' => tr('Tracker ID containing the list of badges to be awarded on goal completion. Each badge can be awarded only once per user.'),
            'type' => 'text',
            'filter' => 'int',
            'default' => 0,
            'hint' => tr('0 to disable'),
            'profile_reference' => 'tracker',
            'dependencies' => ['feature_trackers'],
        ],
        'goal_group_blacklist' => [
            'name' => tr('Groups not eligible for goals'),
            'description' => tr('Groups that will not be on the eligible group list.'),
            'type' => 'text',
            'separator' => ';',
            'filter' => 'groupname',
            'profile_reference' => 'group',
            'default' => ['Admins', 'Anonymous'],
        ],
    ];
}
