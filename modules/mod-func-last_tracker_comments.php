<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_last_tracker_comments_info()
{
    return [
        'name' => tra('Newest Tracker Comments'),
        'description' => tra('Lists the specified number of tracker comments (optionally restricting to those in a specific tracker or tracker item) starting from the most recently posted.'),
        'prefs' => ['feature_trackers'],
        'params' => [
            'trackerId' => [
                'name' => tra('Tracker identifier'),
                'description' => tra('If set to a tracker identifier, only displays the comments on the given tracker.') . " " . tra('Example value: 13.') . " " . tr('Not set by default.'),
                'filter' => 'int',
                'profile_reference' => 'tracker',
            ],
            'itemId' => [
                'name' => tra('Item identifier'),
                'description' => tra('If set to an item identifier, only displays the comments on the given item.') . " " . tra('Example value: 13.') . " " . tr('Not set by default.'),
                'filter' => 'int',
                'profile_reference' => 'tracker_item',
            ]
        ],
        'common_params' => ['rows', 'nonums']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_last_tracker_comments($mod_reference, $module_params)
{
    global $prefs;
    $smarty = TikiLib::lib('smarty');
    $trackerId = isset($module_params["trackerId"]) ? $module_params["trackerId"] : 0;

    $itemId = isset($module_params["itemId"]) ? $module_params["itemId"] : 0;

    $trklib = TikiLib::lib('trk');

    $ranking = $trklib->list_last_comments($trackerId, $itemId, 0, $mod_reference["rows"]);
    $smarty->assign('modLastModifComments', isset($ranking['data']) ? $ranking["data"] : []);
}
