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
        'staticKeyFilters'     => [
        'filter_name'          => 'word',               //post
        'filter_user'          => 'username',           //post
        'filter_operator'      => 'string',              //post
        'where'                => 'string',               //post
        'sort_mode'            => 'word',                //post
        'offset'               => 'digits',                //post
        'find'                 => 'string',                //post
        'view'                 => 'string',                //post
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/live_support/lsadminlib.php');
include_once('lib/live_support/lslib.php');
$access->check_feature('feature_live_support');
if ($tiki_p_live_support_admin != 'y' && ! $lsadminlib->user_is_operator($user)) {
    $smarty->assign('errortype', 401);
    $smarty->assign('msg', tra("You do not have the permission that is needed to use this feature"));
    $smarty->display("error.tpl");
    die;
}
$where = '';
$wheres = [];
if (! isset($_REQUEST['filter_name'])) {
    $_REQUEST['filter_user'] = '';
}
if (! isset($_REQUEST['filter_operator'])) {
    $_REQUEST['filter_operator'] = '';
}
if (($_REQUEST['filter_user'])) {
    $wheres[] = " tiki_user='" . $_REQUEST['filter_name'] . "'";
}
if (($_REQUEST['filter_operator'])) {
    $wheres[] = " operator='" . $_REQUEST['filter_operator'] . "'";
}
$where = implode('and', $wheres);
if (isset($_REQUEST['where'])) {
    $where = $_REQUEST['where'];
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'chat_started_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign_by_ref('offset', $offset);
if (isset($_REQUEST["find"])) {
    $find = $_REQUEST["find"];
} else {
    $find = '';
}
$smarty->assign('find', $find);
$smarty->assign('where', $where);
$smarty->assign_by_ref('sort_mode', $sort_mode);
$items = $lsadminlib->list_support_requests($offset, $maxRecords, $sort_mode, $find, $where);
$smarty->assign_by_ref('cant_pages', $items["cant"]);
$smarty->assign_by_ref('items', $items["data"]);
$smarty->assign('users', $lsadminlib->get_all_tiki_users());
$smarty->assign('operators', $lsadminlib->get_all_operators());
if (isset($_REQUEST['view'])) {
    $smarty->assign('events', $lsadminlib->get_events($_REQUEST['view']));
}
// Display the template
$smarty->assign('mid', 'tiki-live_support_transcripts.tpl');
$smarty->display("tiki.tpl");
