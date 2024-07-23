<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$section = 'surveys';
$inputConfiguration = [
    [
        'staticKeyFilters'   => [
        'surveyId'           => 'int',               //get
        'clear'              => 'int',               //get
        'sort_mode'          => 'word',              //get
        'offset'             => 'int',               //get
        'find'               => 'word',              //post
        ],
    ],
];
require_once('tiki-setup.php');
include_once('lib/surveys/surveylib.php');

$access->check_feature('feature_surveys');

$tikilib->get_perm_object($_REQUEST['surveyId'], 'survey');
$access->check_permission('view_survey_stats', 'View Survey Statistics', 'survey', $_REQUEST['surveyId']);

if (! isset($_REQUEST["surveyId"])) {
    $smarty->assign('msg', tra("No survey indicated"));
    $smarty->display("error.tpl");
    die;
}
$smarty->assign('surveyId', $_REQUEST["surveyId"]);
$survey_info = $srvlib->get_survey($_REQUEST["surveyId"]);
$smarty->assign('survey_info', $survey_info);
if (isset($_REQUEST["clear"]) && $tiki_p_admin_surveys == 'y' && $access->checkCsrf()) {
    $srvlib->clear_survey_stats($_REQUEST["clear"]);
}
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

$usersthatvoted = $srvlib->list_users_that_voted($_REQUEST["surveyId"]);
$smarty->assign('usersthatvoted', $usersthatvoted);
$questions = $srvlib->list_survey_questions($_REQUEST["surveyId"], 0, -1, $sort_mode, $find);

$cant_pages = ceil($questions["cant"] / $maxRecords);
$smarty->assign_by_ref('cant_pages', $cant_pages);
$smarty->assign('actual_page', 1 + ($offset / $maxRecords));
if ($questions["cant"] > ($offset + $maxRecords)) {
    $smarty->assign('next_offset', $offset + $maxRecords);
} else {
    $smarty->assign('next_offset', -1);
}
// If offset is > 0 then prev_offset
if ($offset > 0) {
    $smarty->assign('prev_offset', $offset - $maxRecords);
} else {
    $smarty->assign('prev_offset', -1);
}
$smarty->assign_by_ref('questions', $questions["data"]);
include_once('tiki-section_options.php');
// Display the template
$smarty->assign('mid', 'tiki-survey_stats_survey.tpl');
if (isset($_REQUEST['print'])) {
    $smarty->display('tiki-print.tpl');
    $smarty->assign('print', 'y');
} else {
    $smarty->display('tiki.tpl');
}
