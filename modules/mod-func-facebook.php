<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_facebook_info()
{
    return [
        'name' => tra('Facebook'),
        'description' => tra('Shows the Wall of a user'),
        'params' => [
            'user' => [
                'name' => 'user',
                'description' => tra('Tiki user to show Facebook wall of.'),
                'required' => true
            ],
            'showuser' => [
                'name' => 'showuser',
                'description' => tra('Show username in timeline. y|n'),
                'default' => 'n',
            ],
        ],
        'common_params' => ['nonums', 'rows'],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_facebook($mod_reference, $module_params)
{
    global $prefs;
    global $socialnetworkslib;
    require_once('lib/socialnetworkslib.php');
    if (! empty($module_params['user'])) {
        $user = $module_params['user'];

        $response = $socialnetworkslib->facebookGetWall($user, true);

        if ($response == -1) {
            $timeline[0]['message'] = tra('user not registered with facebook') . ": $user";
        } else {
            $timeline = $response;
        }
    } else {
        $i = 0;
        $timeline[$i]['message'] = tra('No username given');
        $timeline[$i]['created_time'] = '';
        $timeline[$i]['fromName'] = '';
    }

    $timeline = array_splice($timeline, 0, ! empty($module_params['max']) ? $module_params['max'] : 10);
    $smarty = TikiLib::lib('smarty');
    $smarty->assign('timeline', $timeline);
}
