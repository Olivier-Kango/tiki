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
        'staticKeyFilters'         => [
        'blogId'                   => 'digits',            //post
        'postId'                   => 'digits',            //post
        'remove_image'             => 'int',               //get
        'wysiwyg'                  => 'bool',              //post
        'publish_Meridian'         => 'text',              //post
        'publish_Hour'             => 'digits',            //post
        'publish_Minute'           => 'digits',            //post
        'publish_Month'            => 'digits',            //post
        'publish_Day'              => 'digits',            //post
        'publish_Year'             => 'digits',            //post
        'cancel'                   => 'bool',              //post
        'referer'                  => 'word',              //post
        'lang'                     => 'lang',              //post
        'data'                     => 'none',              //post
        'mode_normal'              => 'bool',              //post
        'mode_wysiwyg'             => 'bool',              //post
        'blogpriv'                 => 'bool',              //post
        'preview'                  => 'bool',              //post
        'excerpt'                  => 'text',              //post
        'freetag_string'           => 'text',              //post
        'save'                     => 'bool',              //post
        'contributions'            => 'text',              //post
        'title'                    => 'word',              //post
        'geolocation'              => 'word',              //post
        ],
    ],
];

require_once('tiki-setup.php');
$categlib = TikiLib::lib('categ');
$bloglib = TikiLib::lib('blog');
$editlib = TikiLib::lib('edit');

$access->check_feature('feature_blogs');

$blogId = isset($_REQUEST['blogId']) ? $_REQUEST['blogId'] : 0;

// Now check which blogs the user has permission to post (if any)
if ($tiki_p_blog_admin == 'y') {
    $blogsd = $bloglib->list_blogs(0, -1, 'created_desc', '');
    $blogs = $blogsd['data'];
} else {
    $blogs = $bloglib->list_blogs_user_can_post();
}

$smarty->assign_by_ref('blogs', $blogs);

// If user doesn't have permission to post in any blog display error message
if (count($blogs) == 0) {
    $smarty->assign('msg', tra("It isn't possible to post in any blog.") . ' <a href="tiki-edit_blog.php" >' . tra("You may need to create a blog first.") . '</a>');
    $smarty->display("error.tpl");
    die;
} elseif ($blogId == 0 && count($blogs) == 1) {
    $blogId = $blogs[0]['blogId'];
}

if ($blogId > 0) {
    $blog_data = $bloglib->get_blog($blogId);
    $smarty->assign_by_ref('blog_data', $blog_data);
}

$postId = isset($_REQUEST["postId"]) ? $_REQUEST["postId"] : 0;

if ($postId > 0) {
    $data = $bloglib->get_post($_REQUEST["postId"]);

    // If the blog is public and the user has posting permissions then he can edit
    // If the user owns the weblog then he can edit
    if (! $user || ($data["user"] != $user && $user != $blog_data["user"] && ! ($blog_data['public'] == 'y' && $tikilib->user_has_perm_on_object($user, $_REQUEST['blogId'], 'blog', 'tiki_p_blog_post')))) {
        if ($tiki_p_blog_admin != 'y' && ! $tikilib->user_has_perm_on_object($user, $_REQUEST['blogId'], 'blog', 'tiki_p_blog_admin')) {
            $smarty->assign('errortype', 401);
            $smarty->assign('msg', tra("You do not have permission to edit this post"));
            $smarty->display("error.tpl");
            die;
        }
    }
    if (isset($data['wysiwyg']) && ! isset($_POST['wysiwyg'])) {
        $_POST['wysiwyg'] = $data['wysiwyg'];
    }
}

$smarty->assign('blogId', $blogId);
$smarty->assign('postId', $postId);

//Use 12- or 24-hour clock for $publishDate time selector based on admin and user preferences
$userprefslib = TikiLib::lib('userprefs');
$smarty->assign('use_24hr_clock', $userprefslib->get_user_clock_pref($user));

if (isset($_POST["publish_Hour"])) {
    //Convert 12-hour clock hours to 24-hour scale to compute time
    if (! empty($_POST['publish_Meridian'])) {
        $_POST['publish_Hour'] = date('H', strtotime($_POST['publish_Hour'] . ':00 ' . $_POST['publish_Meridian']));
    }
    $publishDate = $tikilib->make_time($_POST["publish_Hour"], $_POST["publish_Minute"], 0, $_POST["publish_Month"], $_POST["publish_Day"], $_POST["publish_Year"]);
} else {
    $publishDate = $tikilib->now;
}

if ($prefs['feature_freetags'] == 'y') {
    $freetaglib = TikiLib::lib('freetag');

    if ($prefs['feature_multilingual'] == 'y') {
        $languages = [];
        $langLib = TikiLib::lib('language');
        $languages = $langLib->list_languages();
        $smarty->assign_by_ref('languages', $languages);
        $smarty->assign('blog', 'y');
    }
}

// Exit edit mode (without javascript)
if (isset($_POST['cancel'])) {
    header("location: tiki-view_blog.php?blogId=$blogId");
}

// Exit edit mode (with javascript)
$smarty->assign('referer', ! empty($_POST['referer']) ? $_POST['referer'] : (empty($_SERVER['HTTP_REFERER']) ? 'tiki-view_blog.php?blogId=' . $blogId : $_SERVER['HTTP_REFERER']));

if (isset($_REQUEST['remove_image']) && $access->checkCsrf(true)) {
    $bloglib->remove_post_image($_POST['remove_image']);
}

if ($prefs['feature_wysiwyg'] == 'y' && ($prefs['wysiwyg_default'] == 'y' && ! isset($_POST['wysiwyg'])) || (isset($_POST['wysiwyg']) && $_POST['wysiwyg'] == 'y')) {
    $smarty->assign('wysiwyg', 'y');
    $is_wysiwyg = true;
} else {
    $smarty->assign('wysiwyg', 'n');
    $is_wysiwyg = false;
}

if ($postId > 0) {
    if (empty($data["data"])) {
        $data["data"] = '';
    }

    $smarty->assign('post_info', $data);
    $smarty->assign('data', $data['data']);
    $smarty->assign('parsed_data', TikiLib::lib('parser')->parse_data($data['data'], ['is_html' => $is_wysiwyg, 'objectType' => 'post', 'objectId' => $postId, 'fieldName' => 'data']));
    $smarty->assign('blogpriv', $data['priv']);

    $post_images = $bloglib->get_post_images($postId);
    $smarty->assign_by_ref('post_images', $post_images);
    $cat_type = 'blog post';
    $cat_objid = $postId;

    if (isset($_POST['lang'])) {
        $cat_lang = $_POST['lang'];
    }
}
include_once('freetag_list.php');

$smarty->assign('preview', 'n');

$blogpriv = 'n';
$smarty->assign('blogpriv', 'n');

if (isset($_POST["data"])) {
    $edit_data = $_POST["data"];
} else {
    if (isset($data["data"])) {
        $edit_data = $data["data"];
    } else {
        $edit_data = '';
    }
    if (isset($data["priv"])) {
        $smarty->assign('blogpriv', $data["priv"]);
        $blogpriv = $data["priv"];
    }
}

// Handles switching editor modes
if (isset($_POST['mode_normal']) && $_POST['mode_normal'] == 'y') {
    // Parsing page data as first time seeing html page in normal editor
    $smarty->assign('msg', "Parsing html to wiki");
    $parsed = $editlib->parseToWiki($edit_data);
    $smarty->assign('data', $parsed);
} elseif (isset($_POST['mode_wysiwyg']) && $_POST['mode_wysiwyg'] == 'y') {
    // Parsing page data as first time seeing wiki page in wysiwyg editor
    $smarty->assign('msg', "Parsing wiki to html");
    $parsed = $editlib->parseToWysiwyg($edit_data);
    $smarty->assign('data', $parsed);
}

if (isset($_POST["blogpriv"]) && $_POST["blogpriv"] == 'on') {
    $smarty->assign('blogpriv', 'y');
    $blogpriv = 'y';
}

if (isset($_POST["preview"])) {
    $post_info = [];
    $parserlib = TikiLib::lib('parser');
    $edit_data = $tikilib->convertAbsoluteLinksToRelative($edit_data);
    $parsed_data = TikiLib::lib('parser')->parse_data($edit_data, ['is_html' => $is_wysiwyg]);
    $smarty->assign('data', $edit_data);
    $post_info['parsed_data'] = $parsed_data;

    $post_info['title'] = $_POST['title'];
    $post_info['excerpt'] = isset($_POST['excerpt']) ? $_POST['excerpt'] : '';
    $post_info['user'] = isset($data) ? $data['user'] : $user;
    $post_info['created'] = $publishDate;
    $post_info['avatar'] = isset($data) ? $data['avatar'] : '';
    $post_info['postId'] = $postId;

    if ($prefs['feature_freetags'] == 'y' && isset($_POST['freetag_string'])) {
        $tags = $freetaglib->dumb_parse_tags($_POST['freetag_string']);
        $smarty->assign('tags', $tags);
        $post_info['freetags'] = $tags;
        $smarty->assign('taglist', $_POST["freetag_string"]);
    }
    $smarty->assign('post_info', $post_info);

    $smarty->assign('preview', 'y');
}

if (isset($_POST['save']) && $prefs['feature_contribution'] == 'y' && $prefs['feature_contribution_mandatory_blog'] == 'y' && (empty($_POST['contributions']) || count($_POST['contributions']) <= 0)) {
    $contribution_needed = true;
    $smarty->assign('contribution_needed', 'y');
} else {
    $contribution_needed = false;
}

if (isset($_POST['save']) && ! $contribution_needed && $access->checkCsrf()) {
    $smarty->assign('individual', 'n');

    // TODO ImageGalleryRemoval23.x replace with a file gallery version
    //$edit_data = $imagegallib->capture_images($edit_data);
    $edit_data = $tikilib->convertAbsoluteLinksToRelative($edit_data);

    $title = isset($_POST['title']) ? $_POST['title'] : '';

    if ($postId > 0) {
        $bloglib->update_post($postId, $_POST["blogId"], $edit_data, $_POST['excerpt'] ?? '', $data["user"], $title, isset($_POST['contributions']) ? $_POST['contributions'] : '', $blogpriv, $publishDate, $is_wysiwyg);
    } else {
        if ($blog_data['always_owner'] == 'y') {
            $author = $blog_data['user'];
        } else {
            $author = $user;
        }
        $postId = $bloglib->blog_post($_POST["blogId"], $edit_data, $_POST['excerpt'] ?? '', $author, $title, isset($_POST['contributions']) ? $_POST['contributions'] : '', $blogpriv, $publishDate, $is_wysiwyg);
        $smarty->assign('postId', $postId);
    }

    if ($prefs['geo_locate_blogpost'] == 'y' && ! empty($_POST['geolocation'])) {
        TikiLib::lib('geo')->set_coordinates('blog post', $postId, $_POST['geolocation']);
    }

    // TAG Stuff
    $cat_type = 'blog post';
    $cat_objid = $postId;
    $cat_desc = TikiFilter::get('purifier')->filter(substr($edit_data, 0, 200));
    $cat_name = $title;
    $cat_href = "tiki-view_blog_post.php?postId=" . urlencode($postId);
    $cat_lang = $_POST['lang'] ?? '';
    include_once("freetag_apply.php");
    include_once("categorize.php");

    require_once('tiki-sefurl.php');
    $url = smarty_modifier_sefurl($postId, 'blogpost');
    header("location: $url");
    exit;
}

if ($contribution_needed) {
    $smarty->assign('title', $_POST["title"]);
    $smarty->assign('parsed_data', TikiLib::lib('parser')->parse_data($_POST['data'], ['is_html' => $is_wysiwyg, 'objectType' => 'post', 'objectId' => $postId, 'fieldName' => 'data']));
    $smarty->assign('data', $_POST['data']);
    if ($prefs['feature_freetags'] == 'y') {
        $smarty->assign('taglist', $_POST["freetag_string"]);
    }
}

$cat_type = 'blog post';
$cat_objid = $postId;
include_once("categorize_list.php");

if ($prefs['geo_locate_blogpost'] == 'y') {
    $smarty->assign('geolocation_string', TikiLib::lib('geo')->get_coordinates_string('blog post', $postId));
}

include_once('tiki-section_options.php');

if ($prefs['feature_contribution'] == 'y') {
    include_once('contribution.php');
}

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Display the Index Template
$smarty->assign('mid', 'tiki-blog_post.tpl');
$smarty->display("tiki.tpl");
