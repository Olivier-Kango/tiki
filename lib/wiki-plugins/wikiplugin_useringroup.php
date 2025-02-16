<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_useringroup_info()
{
    global $prefs;
    $info = [
        'name' => tra('UserInGroup'),
        'documentation' => 'PluginUserInGroup',
        'icon' => 'img/icons/group_gear.png',
        'description' => tra('checks if an individual user is in a particular Group and simply return a string set to either true or false'),
        'prefs' => [ 'wikiplugin_useringroup' ],
        'tags' => [ 'basic' ],
        'introduced' => 15,
        'params' => [
            'userId' => [
                'required' => true,
                'name' => tra('userId'),
                'description' => tra('the userId to be checked'),
                'since' => '15.0',
                'filter' => 'text',
                'doctype' => 'text',
            ],
            'testgroup' => [
                'required' => true,
                'name' => tra('test group'),
                'description' => tra('the Group that the userId check is made against'),
                'since' => '15.0',
                'filter' => 'text',
                'doctype' => 'text',
            ],
            'truetext' => [
                'required' => false,
                'name' => tra('text for true result'),
                'description' => tra('The text that is displayed if the test result is true'),
                'since' => '15.0',
                'filter' => 'text',
                'doctype' => 'text',
                'default' => 'true',
            ],
            'falsetext' => [
                'required' => false,
                'name' => tra('text for false result'),
                'description' => tra('The text that is displayed if the test result is false'),
                'since' => '15.0',
                'filter' => 'text',
                'doctype' => 'text',
                'default' => 'false',
            ],
        ]
    ];
    return $info;
}

function wikiplugin_useringroup($data, $params)
{
    global $tikilib, $prefs, $info;
    $userlib = TikiLib::lib('user');

    $plugindata = [];

    $plugindata['truetext'] = 'true';
    $plugindata['falsetext'] = 'false';

    extract($params, EXTR_SKIP);

    if (! isset($params['testgroup'])) {
        return ("<span class='error'>Error: sorry you need to specify a testgroup parameter</span>");
    }

    if (! isset($params['userId'])) {
        return ("<span class='error'>Error: sorry you need to specify a userId parameter</span>");
    }

    $plugindata = array_merge($plugindata, $params);

    $result = $plugindata['falsetext'];

    if ($userlib->user_is_in_group($plugindata['userId'], $plugindata['testgroup'])) {
        $result = $plugindata['truetext'];
    }

    return $result;
}
