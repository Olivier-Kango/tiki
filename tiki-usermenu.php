<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'mytiki';

$inputConfiguration = [
    [
        'staticKeyFilters'      => [
            'menuId'            => 'int',           //post
            'delete'            => 'bool',          //post
            'addbk'             => 'bool',          //post
            'url'               => 'url',           //post
            'save'              => 'bool',          //post
            'name'              => 'string',        //post
            'position'          => 'int',           //post
            'mode'              => 'string',        //post
            'sort_mode'         => 'string',        //post
            'offset'            => 'int',           //post
            'find'              => 'string',        //post
        ],
        'staticKeyFiltersForArrays' => [
            'menu'          => 'string',    //post
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/usermenu/usermenulib.php');

$access->check_feature('feature_usermenu');
$access->check_user($user);
$access->check_permission('tiki_p_usermenu');

if (! isset($_REQUEST["menuId"])) {
    $_REQUEST["menuId"] = 0;
}
if (isset($_REQUEST["delete"]) && isset($_REQUEST["menu"])) {
    $access->checkCsrf();
    foreach (array_keys($_REQUEST["menu"]) as $men) {
        $usermenulib->remove_usermenu($user, $men);
    }
    if (isset($_SESSION['usermenu'])) {
        unset($_SESSION['usermenu']);
    }
}
if (isset($_REQUEST['addbk'])) {
    $access->checkCsrf();
    $usermenulib->add_bk($user);
    if (isset($_SESSION['usermenu'])) {
        unset($_SESSION['usermenu']);
    }
}
if ($_REQUEST["menuId"]) {
    $info = $usermenulib->get_usermenu($user, $_REQUEST["menuId"]);
} else {
    $info = [];
    $info['name'] = '';
    $info['url'] = isset($_REQUEST['url']) ? $_REQUEST['url'] : '';
    $info['mode'] = 'w';
    $info['position'] = $usermenulib->get_max_position($user) + 1;
}
if (isset($_REQUEST['save'])) {
    $access->checkCsrf();
    $usermenulib->replace_usermenu($user, $_REQUEST["menuId"], $_REQUEST["name"], $_REQUEST["url"], $_REQUEST['position'], $_REQUEST['mode']);
    $info = [];
    $info['name'] = '';
    $info['url'] = '';
    $info['position'] = 1;
    $_REQUEST["menuId"] = 0;
    unset($_SESSION['usermenu']);
}
$smarty->assign('menuId', $_REQUEST["menuId"]);
$smarty->assign('info', $info);
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'position_asc';
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
$smarty->assign_by_ref('sort_mode', $sort_mode);
if (isset($_SESSION['thedate'])) {
    $pdate = $_SESSION['thedate'];
} else {
    $pdate = $tikilib->now;
}
$channels = $usermenulib->list_usermenus($user, $offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('cant_pages', $channels["cant"]);
$smarty->assign_by_ref('channels', $channels["data"]);
include_once('tiki-mytiki_shared.php');
$smarty->assign('mid', 'tiki-usermenu.tpl');
$smarty->display("tiki.tpl");
