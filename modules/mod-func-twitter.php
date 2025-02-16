<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Module special params:
// - user: Tiki username to show Twitter timeline of

/**
 * @return array
 */
function module_twitter_info()
{
    return [
        'name' => tra('Tweets'),
        'description' => tra('Shows the tweets from the Twitter timeline of a user'),
        'params' => [
            'user' => [
                'name' => 'user',
                'description' => tra('Tiki user to show Twitter timeline of.'),
                'required' => true
            ],
            'timelinetype' => [
                'name' => 'Timeline type',
                'description' => tra('Show public|friends timeline. '),
                'default' => 'public',
            ],
            'search' => [
                'name' => 'search',
                'description' => tra('Search string.'),
                'default' => 'tikiwiki',
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
function module_twitter($mod_reference, $module_params)
{
    global $prefs;
    global $socialnetworkslib;
    require_once('lib/socialnetworkslib.php');
    $smarty = TikiLib::lib('smarty');
    $tikilib = TikiLib::lib('tiki');

    if (! empty($module_params['user'])) {
        $user = $module_params['user'];

        $token = $tikilib->get_user_preference($user, 'twitter_token', '');
        $smarty->assign('twitter', ($token != ''));

        $response = $socialnetworkslib->getTwitterTimeline($user, $mod_reference['params']['timelinetype'], $module_params['search']);
        if ($response == -1) {
            $timeline[0]['text'] = tra('user not registered with twitter') . ": $user";
        }

        $module_params['timelinetype'] == 'search' ? $response = $response->statuses : true;
        for ($i = 0, $count_response_status = count($response); $i < $count_response_status; $i++) {
            $timeline[$i]['text'] = $response[$i]->text;
            $timeline[$i]['id'] = $response[$i]->id;
            $timeline[$i]['created_at'] = $response[$i]->created_at;
            $timeline[$i]['screen_name'] = $response[$i]->user->screen_name;
        }
    } else {
        $i = 0;
        $timeline[$i]['text'] = tra('No username given');
        $timeline[$i]['created_at'] = '';
        $timeline[$i]['screen_name'] = '';
    }

    $timeline = array_splice($timeline, 0, $mod_reference['rows'] ? $mod_reference['rows'] : 10);
    $smarty->assign('timeline', $timeline);
}
