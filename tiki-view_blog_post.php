<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'blogs';
$inputConfiguration = [
    [
        'staticKeyFilters'      => [
            'postId'            => 'int',      //get
            'watch_event'       => 'word',     //get
            'watch_action'      => 'word',     //get
            'offset'            => 'int',      //get
            'sort_mode'         => 'word',     //get
            'find'              => 'word',     //get
            'page'              => 'int',      //get
            'savenotepad'       => 'none',     //get
        ]
    ],
];
require_once('tiki-setup.php');
$bloglib = TikiLib::lib('blog');

use Tiki\Wiki\WikiPaginationUtils;

$auto_query_args = [
    'postId',
    'blogId',
    'offset',
    'find',
    'sort_mode',
    'page',
    'mode',
    'show_comments'
];

$access->check_feature('feature_blogs');

if (empty($_REQUEST["postId"])) {
    $smarty->assign('msg', tra('No post indicated'));
    $smarty->display('error.tpl');
    die;
} else {
    $postId = $_REQUEST['postId'];
}

$post_info = $bloglib->get_post($postId);
if (! $post_info) {
    $smarty->assign('msg', tra("Post not found"));
    $smarty->display("error.tpl");
    die;
} else {
    $bloglib->add_blog_post_hit($postId);
}
$blogId = $post_info['blogId'];

//Keep track of month of last viewed posts for months_links module foldable display
$_SESSION['blogs_last_viewed_month'] = TikiLib::date_format("%Y-%m", $post_info['created']);

$blog_data = $bloglib->get_blog($blogId);
if (! $blog_data) {
    $smarty->assign('msg', tra("Blog not found"));
    $smarty->display("error.tpl");
    die;
}

$tikilib->get_perm_object($postId, 'blog post');

$access->check_permission('tiki_p_read_blog', '', 'blog post', $postId);

$ownsblog = 'n';
if ($user && $user == $blog_data["user"]) {
    $ownsblog = 'y';
}

$ownspost = 'n';
if ($user && $user == $post_info["user"]) {
    $ownspost = 'y';
}

if ($ownspost == 'n' && $ownsblog == 'n' && $tiki_p_blog_admin != 'y' && $post_info["priv"] == 'y') {
    $smarty->assign('errortype', 401);
    $smarty->assign('msg', tra("You do not have permission to view this blog post while it is marked as private"));
    $smarty->display("error.tpl");
    die;
}
if ($ownspost == 'n' && $ownsblog == 'n' && $tiki_p_blog_admin != 'y' && $post_info['created'] > $tikilib->now) {
    $smarty->assign('errortype', 401);
    $smarty->assign('msg', tra('Permission denied'));
    $smarty->display("error.tpl");
    die;
}

$allowprivate = 'n';
if (($user && $ownsblog == 'y') || $tiki_p_blog_admin == 'y') {
    $allowprivate = 'y';
}
$post_info['adjacent'] = $bloglib->get_adjacent_posts($blogId, $post_info['created'], $tiki_p_blog_admin == 'y' ? null : $tikilib->now, $user, $allowprivate);

if ($prefs['feature_freetags'] == 'y') {
    // Get Tags
    $freetaglib = TikiLib::lib('freetag');
    $post_info['freetags'] = $freetaglib->get_tags_on_object($postId, "blog post");

    if ($blog_data['show_related'] == 'y' && ! empty($post_info['freetags'])) {
        $post_info['related_posts'] = $bloglib->get_related_posts($postId, $blog_data['related_max']);
    }
}

// We need to figure out in which section and theme we are before any call to tiki-modules.php
// which needs $tc_theme for deciding on the visible modules everywhere in the page
$cat_type = 'blog';
$cat_objid = $blogId;
include_once('tiki-section_options.php');

// Blog comment mail
if ($prefs['feature_user_watches'] == 'y') {
    if ($user && isset($_REQUEST['watch_event'])) {
        $access->checkCsrf();
        if ($_REQUEST['watch_action'] == 'add') {
            $tikilib->add_user_watch(
                $user,
                $_REQUEST['watch_event'],
                $_REQUEST['watch_object'],
                'blog',
                $blog_data['title'],
                "tiki-view_blog_post.php?postId=" . $_REQUEST['postId']
            );
        } else {
            $tikilib->remove_user_watch($user, $_REQUEST['watch_event'], $_REQUEST['watch_object'], 'blog');
        }
    }
    $smarty->assign('user_watching_blog', 'n');

    if ($user && $tikilib->user_watches($user, 'blog_comment_changes', $_REQUEST['postId'], 'blog')) {
        $smarty->assign('user_watching_blog', 'y');
    }

    // Check, if the user is watching this blog by a category.
    if ($prefs['feature_categories'] == 'y') {
        $categlib = TikiLib::lib('categ');
        $watching_categories_temp = $categlib->get_watching_categories($_REQUEST['postId'], 'blog', $user);
        $smarty->assign('category_watched', 'n');
        if (count($watching_categories_temp) > 0) {
            $smarty->assign('category_watched', 'y');
            $watching_categories = [];
            foreach ($watching_categories_temp as $wct) {
                $watching_categories[] = [
                    "categId" => $wct,
                    "name" => $categlib->get_category_name($wct)
                ];
            }
            $smarty->assign('watching_categories', $watching_categories);
        }
    }
}


if ($prefs['feature_categories'] == 'y') {
    $cat_type = 'blog post';
    $cat_objid = $postId;
    require_once('categorize_list.php');
}
$smarty->assign('ownsblog', $ownsblog);
$smarty->assign('postId', $postId);
$smarty->assign('blog_data', $blog_data);
$smarty->assign('blogId', $blogId);
$smarty->assign('public', $blog_data['public']);
$smarty->assign('headtitle', $post_info['title'] . ' : ' . $blog_data['title']);
$smarty->assign('title', $post_info['title'] . ' : ' . $blog_data['title']);
if (! isset($_REQUEST['offset'])) {
    $_REQUEST['offset'] = 0;
}
if (! isset($_REQUEST['sort_mode'])) {
    $_REQUEST['sort_mode'] = 'created_desc';
}
if (! isset($_REQUEST['find'])) {
    $_REQUEST['find'] = '';
}
$smarty->assign('offset', $_REQUEST["offset"]);
$smarty->assign('sort_mode', $_REQUEST["sort_mode"]);
$smarty->assign('find', $_REQUEST["find"]);
$offset = $_REQUEST["offset"];
$sort_mode = $_REQUEST["sort_mode"];
$find = $_REQUEST["find"];
if ($post_info['wysiwyg'] == "y") {
    $parsed_data = TikiLib::lib('parser')->parse_data($post_info["data"], ['is_html' => true, 'objectType' => 'post', 'objectId' => $postId, 'fieldName' => 'data']);
} else {
    $parsed_data = TikiLib::lib('parser')->parse_data($post_info["data"]);
}
if (! isset($_REQUEST['page'])) {
    $_REQUEST['page'] = 1;
}
$pages = WikiPaginationUtils::getNumberOfPages($parsed_data);
$post_info['parsed_data'] = WikiPaginationUtils::getPage($parsed_data, $_REQUEST['page']);
$post_info['pages'] = $pages;
if ($pages > $_REQUEST['page']) {
    $post_info['next_page'] = $_REQUEST['page'] + 1;
} else {
    $post_info['next_page'] = $_REQUEST['page'];
}
if ($_REQUEST['page'] > 1) {
    $post_info['prev_page'] = $_REQUEST['page'] - 1;
} else {
    $post_info['prev_page'] = 1;
}
$post_info['first_page'] = 1;
$post_info['last_page'] = $pages;
$post_info['pagenum'] = $_REQUEST['page'];
$smarty->assign('post_info', $post_info);

if ($user && $prefs['feature_notepad'] == 'y' && $tiki_p_notepad == 'y' && isset($_REQUEST['savenotepad'])) {
    $access->checkCsrf();
    $tikilib->replace_note($user, 0, $post_info['title'] ? $post_info['title'] : $tikilib->date_format("%d/%m/%Y [%H:%M]", $post_info['created']), $post_info['data']);
}

// Display the template
$smarty->assign('mid', 'tiki-view_blog_post.tpl');
$smarty->display("tiki.tpl");
