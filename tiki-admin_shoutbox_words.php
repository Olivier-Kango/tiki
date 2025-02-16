<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Admin to the filtering of bad shoutbox words
// First commit on cvs by damosoft aka damian
// Initialization
$inputConfiguration = [
    [
        'staticKeyFilters'          => [
            'word'                  => 'word',             //post
            'add'                   => 'bool',             //post
            'remove'                => 'int',              //get
            'sort_mode'             => 'word',             //get
            'offset'                => 'int',              //get
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/shoutbox/shoutboxlib.php');
$access->check_feature('feature_shoutbox');
$access->check_permission('tiki_p_admin_shoutbox');
//get_strings tra('Shoutbox Words')

// Do the add bad word form here
if (isset($_REQUEST["add"])) {
    $access->checkCsrf();
    if (empty($_REQUEST["word"])) {
        $smarty->assign('msg', tra("You have to provide a word"));
        $smarty->display("error.tpl");
        die;
    }
    $shoutboxlib->add_bad_word($_REQUEST["word"]);
}
if (! empty($_REQUEST["remove"]) && $access->checkCsrf()) {
    $shoutboxlib->remove_bad_word($_REQUEST["remove"]);
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'word_asc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign_by_ref('sort_mode', $sort_mode);
// If offset is set use it if not then use offset =0
// use the maxRecords php variable to set the limit
// if sortMode is not set then use lastModif_desc
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
$words = $shoutboxlib->get_bad_words($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('cant_pages', $words["cant"]);
// Get users (list of users)
$smarty->assign_by_ref('words', $words["data"]);
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-admin_shoutbox_words.tpl');
$smarty->display("tiki.tpl");
