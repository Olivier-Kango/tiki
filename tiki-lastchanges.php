<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'wiki page';
$section_class = "tiki_wiki_page manage";   // This will be body class instead of $section
$inputConfiguration = [
    [
        'staticKeyFilters'                => [
        'find'                          => 'word',               //post
        'days'                          => 'digits',             //get
        'sort_mode'                     => 'word',               //get
        'offset'                        => 'int',                //get
        ],
    ],
];
require_once('tiki-setup.php');
$histlib = TikiLib::lib('hist');
$auto_query_args = ['sort_mode', 'offset', 'find', 'days'];
$access->check_feature('feature_wiki');
$access->check_feature('feature_lastChanges');
$access->check_permission('tiki_p_view');
if (! isset($_REQUEST["find"])) {
    $findwhat = '';
} else {
    $findwhat = $_REQUEST["find"];
}
$smarty->assign('find', $findwhat);
if (! isset($_REQUEST["days"])) {
    $days = 1;
} else {
    $days = $_REQUEST["days"];
}
if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'lastModif_desc';
} else {
    $sort_mode = $_REQUEST["sort_mode"];
}
$smarty->assign_by_ref('days', $days);
$smarty->assign_by_ref('findwhat', $findwhat);
$smarty->assign_by_ref('sort_mode', $sort_mode);
if (! isset($_REQUEST["offset"])) {
    $offset = 0;
} else {
    $offset = $_REQUEST["offset"];
}
$smarty->assign_by_ref('offset', $offset);
// Get a list of last changes to the Wiki database
$more = 0;
$lastchanges = $histlib->get_last_changes($days, $offset, $maxRecords, $sort_mode, $findwhat, false);
$smarty->assign_by_ref('cant_records', $lastchanges["cant"]);
// If there're more records then assign next_offset
$cant_pages = ceil($lastchanges["cant"] / $maxRecords);
$smarty->assign_by_ref('cant_pages', $cant_pages);
$smarty->assign('actual_page', 1 + ($offset / $maxRecords));
if ($lastchanges["cant"] > ($offset + $maxRecords)) {
    $smarty->assign('next_offset', $offset + $maxRecords);
} else {
    $smarty->assign('next_offset', -1);
}
if ($offset > 0) {
    $smarty->assign('prev_offset', $offset - $maxRecords);
} else {
    $smarty->assign('prev_offset', -1);
}
$smarty->assign_by_ref('lastchanges', $lastchanges["data"]);

include_once('tiki-section_options.php');
// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
// Display the template
$smarty->assign('mid', 'tiki-lastchanges.tpl');
$smarty->display("tiki.tpl");
