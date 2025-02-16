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
            'addtopic'              => 'bool',      //post
            'name'                  => 'word',      //post
            'remove'                => 'int',       //get
            'removeall'             => 'int',       //post
            'activate'              => 'int',       //post
            'deactivate'            => 'int',       //post
        ],
    ],
];
$section = 'cms';
require_once('tiki-setup.php');
$artlib = TikiLib::lib('art');
$access->check_feature('feature_articles');
// PERMISSIONS: NEEDS p_admin or tiki_p_articles_admin_topics
$access->check_permission(['tiki_p_articles_admin_topics']);

if (isset($_REQUEST["addtopic"])) {
    $access->checkCsrf();
    if (isset($_FILES['userfile1'])) {
        if (is_uploaded_file($_FILES['userfile1']['tmp_name'])) {
            $filegallib = TikiLib::lib('filegal');
            try {
                $filegallib->assertUploadedFileIsSafe($_FILES['userfile1']['tmp_name'], $_FILES['userfile1']['name']);
            } catch (Exception $e) {
                $smarty->assign('errortype', 403);
                $smarty->assign('msg', $e->getMessage());
                $smarty->display("error.tpl");
                die;
            }
            $fp = fopen($_FILES['userfile1']['tmp_name'], "rb");
            $data = fread($fp, filesize($_FILES['userfile1']['tmp_name']));
            fclose($fp);
            $imgtype = $_FILES['userfile1']['type'];
            $imgsize = $_FILES['userfile1']['size'];
            $imgname = $_FILES['userfile1']['name'];
        } else {
            Feedback::error($artlib->uploaded_file_error($_FILES['userfile1']['error']));
        }
    }
    if (! isset($data)) {
        $data = '';
        $imgtype = '';
        $imgsize = '';
        $imgname = '';
    }
    // Store the image
    $artlib->add_topic($_REQUEST["name"], $imgname, $imgtype, $imgsize, $data);
}
if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $artlib->remove_topic($_REQUEST["remove"]);
}
if (isset($_REQUEST["removeall"]) && $access->checkCsrf()) {
    $artlib->remove_topic($_REQUEST["removeall"], 1);
}
if (isset($_REQUEST["activate"]) && $access->checkCsrf()) {
    $artlib->activate_topic($_REQUEST["activate"]);
}
if (isset($_REQUEST["deactivate"]) && $access->checkCsrf()) {
    $artlib->deactivate_topic($_REQUEST["deactivate"]);
}
$topics = $artlib->list_topics();
/* To renumber array keys from 0 since smarty 3 doesn't seem to like arrays
 * that start with other keys in a section loop, which this variable is used in
 */
$topics = array_values($topics);
$smarty->assign('topics', $topics);
include_once('tiki-section_options.php');
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-admin_topics.tpl');
$smarty->display("tiki.tpl");
