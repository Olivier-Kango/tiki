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
        'staticKeyFilters'                => [
            'which'                    => 'string',              //post
            'limit'                    => 'string',              //post
        ],
    ]
];
require_once('tiki-setup.php');
include_once('lib/rankings/ranklib.php');
$access->check_feature(['feature_blogs', 'feature_blog_rankings']);
$access->check_permission('tiki_p_read_blog');

$allrankings = [
    [
        'name' => tra('Top visited blogs') ,
        'value' => 'blog_ranking_top_blogs'
    ] ,
    [
        'name' => tra('Last posts') ,
        'value' => 'blog_ranking_last_posts'
    ] ,
    [
        'name' => tra('Top active blogs') ,
        'value' => 'blog_ranking_top_active_blogs'
    ]
];
$smarty->assign('allrankings', $allrankings);
if (! isset($_REQUEST["which"])) {
    $which = 'blog_ranking_top_blogs';
} else {
    $which = $_REQUEST["which"];
}
$smarty->assign('which', $which);
// Get the page from the request var or default it to HomePage
if (! isset($_REQUEST["limit"])) {
    $limit = 10;
} else {
    $limit = $_REQUEST["limit"];
}
$smarty->assign_by_ref('limit', $limit);
// Rankings:
// Top Pages
// Last pages
// Top Authors
$rankings = [];
$rk = $ranklib->$which($limit);
$rank["data"] = $rk["data"];
$rank["title"] = $rk["title"];
$rank["y"] = $rk["y"];
$rank["type"] = $rk["type"];
$rankings[] = $rank;
include_once('tiki-section_options.php');
$smarty->assign_by_ref('rankings', $rankings);
$smarty->assign('rpage', 'tiki-blog_rankings.php');

// Display the template
$smarty->assign('mid', 'tiki-ranking.tpl');
$smarty->display("tiki.tpl");
