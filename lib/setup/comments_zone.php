<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

/*
 * Show comments zone on page load by default
 */
$comzone = $_REQUEST['comzone'];
if ($comzone == 'show') {
    if (strstr($_SERVER['REQUEST_URI'], 'tiki-read_article') and $prefs['feature_article_comments'] == 'y') {
        $prefs['show_comzone'] = 'y';
    }

    if (strstr($_SERVER['REQUEST_URI'], 'tiki-poll_results') and $prefs['feature_poll_comments'] == 'y') {
        $prefs['show_comzone'] = 'y';
    }

    if (strstr($_SERVER['REQUEST_URI'], 'tiki-index') and $prefs['feature_wiki_comments'] == 'y') {
        $prefs['show_comzone'] = 'y';
    }

    if (strstr($_SERVER['REQUEST_URI'], 'tiki-view_faq') and $prefs['feature_faq_comments'] == 'y') {
        $prefs['show_comzone'] = 'y';
    }

    if (strstr($_SERVER['REQUEST_URI'], 'tiki-list_file_gallery') and $prefs['feature_file_galleries_comments'] == 'y') {
        $prefs['show_comzone'] = 'y';
    }

    if (strstr($_SERVER['REQUEST_URI'], 'tiki-view_blog_post') and $prefs['feature_blogposts_comments'] == 'y') {
        $prefs['show_comzone'] = 'y';
    }

    if (strstr($_SERVER['REQUEST_URI'], 'tiki-map') and $prefs['feature_map_comments'] == 'y') {
        $prefs['show_comzone'] = 'y';
    }

    if ($prefs['show_comzone'] == 'y') {
        $smarty->assign('show_comzone', 'y');
    }
}
