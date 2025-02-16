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
        'staticKeyFilters'         => [
        'maxRecords'               => 'int',                //post
        'inviter'                  => 'string',             //post
        'only_pending'             => 'bool',               //post
        'only_success'             => 'word',               //post
        'sort_mode'                => 'word',               //get
        'offset'                   => 'int',                //get
        'find'                     => 'word',               //post
        ],
    ],
];
require_once('tiki-setup.php');
$access->check_feature('feature_invite');
$access->check_permission('tiki_p_invite');

/**
 * @param int $offset
 * @param $max
 * @param null $inviter
 * @param null $status
 * @param null $nostatus
 * @param string $sort_mode
 * @return array
 */
function list_inviteds($offset = 0, $max = -1, $inviter = null, $status = null, $nostatus = null, $sort_mode = 'ts_desc')
{
    global $tikilib;
    $bindvars = [];
    if (! empty($inviter)) {
        $where[] = 'invite.`inviter` = ?';
        $bindvars[] = $inviter;
    }
    if (! empty($status)) {
        $where[] = 'guy.`used` = ?';
        $bindvars[] = $status;
    }
    if (! empty($nostatus)) {
        $where[] = 'guy.`used` != ?';
        $bindvars[] = $nostatus;
    }
    if (empty($where)) {
        $where[] = '1=1';
    }
    $query = ' FROM `tiki_invited` guy LEFT JOIN `tiki_invite` invite ON (guy.`id_invite` = invite.`id`) where ' . implode(' AND ', $where);
    $query_cant = "SELECT count(*) $query";
    $query = "SELECT guy.*, invite.* $query ORDER BY " . $tikilib->convertSortMode($sort_mode); // convertSortMode($sort_mode);
    $result = $tikilib->query($query, $bindvars, $max, $offset);
    $cant = $tikilib->getOne($query_cant, $bindvars);
    $ret = [];
    while ($res = $result->fetchRow()) {
        $ret[] = $res;
    }
    return ['cant' => $cant, 'data' => $ret];
}

$auto_query_args = ['max', 'sort_mode', 'offset', 'inviter', 'only_pending', 'only_success'];
if (! isset($_REQUEST['offset'])) {
    $_REQUEST['offset'] = 0;
}
if (! isset($_REQUEST['maxRecords'])) {
    $_REQUEST['maxRecords'] = $prefs['maxRecords'];
}
if (empty($_REQUEST['sort_mode'])) {
    $_REQUEST['sort_mode'] = 'ts_desc';
}
if ($tiki_p_admin == 'y') {
    if (! empty($_REQUEST['inviter'])) {
            $inviter = $_REQUEST['inviter'];
        $smarty->assign_by_ref('inviter', $_REQUEST['inviter']);
    } else {
        $inviter = null;
    }
} else {
    $inviter = $user;
}
$status = $nostatus = null;
if (! empty($_REQUEST['only_pending']) && $_REQUEST['only_pending'] == 'on') {
    $status = 'no';
    $smarty->assign('only_pending', 'y');
} elseif (! empty($_REQUEST['only_success'])) {
    $nostatus = 'no';
    $smarty->assign('only_success', 'y');
}
$inviteds = list_inviteds($_REQUEST['offset'], $_REQUEST['maxRecords'], $inviter, $status, $nostatus, $_REQUEST['sort_mode']);
$smarty->assign_by_ref('inviteds', $inviteds['data']);
$smarty->assign_by_ref('offset', $_REQUEST['offset']);
$smarty->assign_by_ref('max', $_REQUEST['maxRecords']);
$smarty->assign_by_ref('cant', $inviteds['cant']);
$smarty->assign('mid', 'tiki-list_invite.tpl');
$smarty->display('tiki.tpl');
