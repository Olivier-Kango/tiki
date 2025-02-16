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
        'staticKeyFilters'   => [
        'objdata'            => 'word',              //post
        'type'               => 'word',              //post
        'assign'             => 'bool',              //post
        'theme'              => 'word',              //post
        'delete'             => 'int',               //get
        'obj'                => 'int',               //get
        'sort_mode'          => 'word',              //get
        'offset'             => 'int',               //get
        'find'               => 'word',              //post
        ],
    ],
];
require_once('tiki-setup.php');
$themecontrollib = TikiLib::lib('themecontrol');
$categlib = TikiLib::lib('categ');
$filegallib = TikiLib::lib('filegal');
$themelib = TikiLib::lib('theme');
include_once('lib/htmlpages/htmlpageslib.php');
/**
 * @param $arr
 * @param $id
 * @param $name
 */
function correct_array(&$arr, $id, $name)
{
    $temp_max = count($arr);
    for ($i = 0; $i < $temp_max; $i++) {
        $arr[$i]['objId'] = $arr[$i][$id];
        $arr[$i]['objName'] = $arr[$i][$name];
    }
}
$access->check_feature('feature_theme_control');
$access->check_permission('tiki_p_admin');

$auto_query_args = ['find', 'sort_mode', 'offset', 'theme', 'theme_option', 'type', 'objdata'];
$smarty->assign('a_object', isset($_REQUEST['objdata']) ? $_REQUEST['objdata'] : '');

$themes = $themelib->list_themes_and_options();
$smarty->assign('themes', $themes);

$find_objects = '';
$objectypes = ['file gallery', 'forum', 'blog', 'wiki page', 'html page', 'faq', 'quiz', 'article'];
$smarty->assign('objectypes', $objectypes);
if (empty($_REQUEST['type'])) {
    $_REQUEST['type'] = 'wiki page';
}
$smarty->assign('type', $_REQUEST['type']);
switch ($_REQUEST['type']) {
    case 'file gallery':
        $objects = $filegallib->list_file_galleries(0, -1, 'name_desc', 'admin', $find_objects, $prefs['fgal_root_id']);
        $smarty->assign_by_ref('objects', $objects["data"]);
        $objects = $objects['data'];
        correct_array($objects, 'galleryId', 'name');
        break;

    case 'forum':
        $objects = TikiLib::lib('comments')->list_forums(0, -1, 'name_asc', $find_objects);
        $smarty->assign_by_ref('objects', $objects["data"]);
        $objects = $objects['data'];
        correct_array($objects, 'forumId', 'name');
        break;

    case 'blog':
        $bloglib = TikiLib::lib('blog');
        $objects = $bloglib->list_blogs(0, -1, 'title_asc', $find_objects);
        $smarty->assign_by_ref('objects', $objects["data"]);
        $objects = $objects['data'];
        correct_array($objects, 'blogId', 'title');
        break;

    case 'wiki page':
        $objects = $tikilib->list_pageNames(0, -1, 'pageName_asc', $find_objects);
        $smarty->assign_by_ref('objects', $objects["data"]);
        $objects = $objects['data'];
        correct_array($objects, 'pageName', 'pageName');
        break;

    case 'html page':
        $objects = $htmlpageslib->list_html_pages(0, -1, 'pageName_asc', $find_objects);
        $smarty->assign_by_ref('objects', $objects["data"]);
        $objects = $objects['data'];
        correct_array($objects, 'pageName', 'pageName');
        break;

    case 'faq':
        $faqlib = TikiLib::lib('faq');
        $objects = $faqlib->list_faqs(0, -1, 'title_asc', $find_objects);
        $smarty->assign_by_ref('objects', $objects["data"]);
        $objects = $objects['data'];
        correct_array($objects, 'faqId', 'title');
        break;

    case 'quiz':
        $objects = TikiLib::lib('quiz')->list_quizzes(0, -1, 'name_asc', $find_objects);
        $smarty->assign_by_ref('objects', $objects["data"]);
        $objects = $objects['data'];
        correct_array($objects, 'quizId', 'name');
        break;

    case 'article':
        $artlib = TikiLib::lib('art');
        $objects = $artlib->list_articles(0, -1, 'title_asc', $find_objects, 0, 0, $user);
        $smarty->assign_by_ref('objects', $objects["data"]);
        $objects = $objects['data'];
        correct_array($objects, 'articleId', 'title');
        break;

    default:
        break;
}
$smarty->assign_by_ref('objects', $objects);
if (isset($_REQUEST['assign'])) {
    $access->checkCsrf();
    list($id, $name) = explode('_', $_REQUEST['objdata']);
    if (isset($name) && $name !== '') {
        $themecontrollib->tc_assign_object($id, $_REQUEST['theme'], $_REQUEST['type'], $name);
    } else {
        Feedback::error(tr('The object name cannot be empty.'));
    }
}
if (isset($_REQUEST["delete"])) {
    $access->checkCsrf();
    if (isset($_REQUEST["obj"]) && is_array($_REQUEST["obj"])) {
        foreach (array_keys($_REQUEST["obj"]) as $obj) {
            $themecontrollib->tc_remove_object($obj);
        }
    } else {
        Feedback::error(tr('No object selected.'));
    }
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'name_asc';
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
$channels = $themecontrollib->tc_list_objects(null, $offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('cant_pages', $channels["cant"]);
$smarty->assign_by_ref('channels', $channels["data"]);
// Display the template
$smarty->assign('mid', 'tiki-theme_control_objects.tpl');
$smarty->display("tiki.tpl");
