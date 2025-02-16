<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'user_messages';
require_once('tiki-setup.php');
$messulib = TikiLib::lib('message');
$access->check_user($user);
$access->check_feature('feature_messages');
$access->check_permission('tiki_p_messages');

if ((! isset($_REQUEST['msgId']) || $_REQUEST['msgId'] == 0) && empty($_POST['msgdel'])) {
    $smarty->assign('unread', 0);
    $smarty->assign('legend', tra("No more messages"));
    $smarty->assign('mid', 'messu-read.tpl');
    $smarty->display("tiki.tpl");
    die;
}


if (isset($_POST['action']) && $access->checkCsrf()) {
    $messulib->flag_message($user, $_POST['msgId'], $_POST['action'], $_POST['actionval']);
}
if (isset($_POST['msgdel']) && $access->checkCsrf(true)) {
    $result = $messulib->delete_message($user, $_POST['msgdel']);
    if ($result->numRows()) {
        Feedback::success(tr('Message deleted'));
    } else {
        Feedback::error(tr('Message not deleted'));
    }
    header('location: messu-mailbox.php');
    exit;
}

$smarty->assign('sort_mode', $_REQUEST['sort_mode']);
$smarty->assign('find', $_REQUEST['find']);
$smarty->assign('flag', $_REQUEST['flag']);
$smarty->assign('offset', $_REQUEST['offset']);
$smarty->assign('flagval', $_REQUEST['flagval']);
$smarty->assign('priority', $_REQUEST['priority']);
$smarty->assign('legend', '');

// Using the sort_mode, flag, flagval and find get the next and prev messages
$smarty->assign('msgId', $_REQUEST['msgId']);
$next = $messulib->get_next_message($user, $_REQUEST['msgId'], $_REQUEST['sort_mode'], $_REQUEST['find'], $_REQUEST['flag'], $_REQUEST['flagval'], $_REQUEST['priority']);
$prev = $messulib->get_prev_message($user, $_REQUEST['msgId'], $_REQUEST['sort_mode'], $_REQUEST['find'], $_REQUEST['flag'], $_REQUEST['flagval'], $_REQUEST['priority']);
$smarty->assign('next', $next);
$smarty->assign('prev', $prev);
// Mark the message as read in the receivers mailbox
$messulib->flag_message($user, $_REQUEST['msgId'], 'isRead', 'y');
// Get the message and assign its data to template vars
$msg = $messulib->get_message($user, $_REQUEST['msgId']);
$smarty->assign('msg', $msg);
// which quote format should tiki use?
global $prefs;
if ($prefs['feature_use_quoteplugin'] == 'y') {
    $quote_format = 'fancy';
} else {
    $quote_format = 'simple';
}
$smarty->assign('quote_format', $quote_format);
if ($messulib->get_user_preference($user, 'mess_sendReadStatus', 'n') == 'y') {
    // Mark the message as read in the senders sent box:
    $messulib->flag_message($msg['user_from'], $_REQUEST['msgId'], 'isRead', 'y', 'sent');
}
if ($prefs['feature_actionlog'] == 'y') {
    $logslib->add_action('Viewed', '', 'message');
}
include_once('tiki-section_options.php');
include_once('tiki-mytiki_shared.php');
$smarty->assign('mid', 'messu-read.tpl');
$smarty->display("tiki.tpl");
