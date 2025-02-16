<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'          => [
            'trackerId'              => 'int',       //get
        ],
    ],
];
require_once('tiki-setup.php');
$trklib = TikiLib::lib('trk');

$access->check_feature('feature_trackers');

if (! isset($_REQUEST['trackerId'])) {
    $smarty->assign('msg', tra('No tracker indicated'));
    $smarty->display('error.tpl');
    die;
}
if ($tracker_info = $trklib->get_tracker($_REQUEST['trackerId'])) {
    if ($t = $trklib->get_tracker_options($_REQUEST['trackerId'])) {
        $tracker_info = array_merge($tracker_info, $t);
    }
} else {
    $smarty->assign('msg', tra('Incorrect param'));
    $smarty->display('error.tpl');
    die;
}

$admin_perm = $tiki_p_admin_trackers;
if ($tiki_p_admin_trackers != 'y' && ! empty($_REQUEST['trackerId'])) {
    $perms = $tikilib->get_perm_object($_REQUEST['trackerId'], 'tracker', $info);
    $admin_perm = $perms['tiki_p_admin_trackers'];
}
if ($admin_perm != 'y') {
    $smarty->assign('errortype', 401);
    $smarty->assign('msg', tra("You don't have permission to use this feature"));
    $smarty->display("error.tpl");
    die;
}
$auto_query_args = [
    'trackerId',
    'offset',
    'sort_mode',
    'find',
    'max'
];
$tracker_info['pagetitle'] = tr('Tracker Fields %0', $tracker_info['name']);
$smarty->assign('trackerId', $_REQUEST["trackerId"]);
$smarty->assign('tracker_info', $tracker_info);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->display("tiki-admin_tracker_fields.tpl");
