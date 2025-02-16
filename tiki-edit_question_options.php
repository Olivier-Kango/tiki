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
        'staticKeyFilters'                => [
            'questionId'                  => 'int',            //post
            'quizId'                      => 'int',            //post
            'optionId'                    => 'int',            //post
            'remove'                      => 'int',            //post
            'save'                        => 'bool',           //post
            'optionText'                  => 'xss',            //post
            'points'                      => 'string',         //post
            'sort_mode'                   => 'alpha',          //get
            'offset'                      => 'int',            //get
            'find'                        => 'alpha',          //post
        ],
    ],
];
require_once('tiki-setup.php');
$quizlib = TikiLib::lib('quiz');

$auto_query_args = ['sort_mode', 'offset', 'find', 'questionId', 'quizId', 'optionId'];
$access->check_feature('feature_quizzes');

if (! isset($_REQUEST["questionId"])) {
    $smarty->assign('msg', tra("No question indicated"));

    $smarty->display("error.tpl");
    die;
}

$smarty->assign('questionId', $_REQUEST["questionId"]);
$quiz_info = $quizlib->get_quiz_question($_REQUEST["questionId"]);
if (! $quiz_info) {
    $smarty->assign('msg', tra("The requested question was not found. Please check the question ID and try again."));
    $smarty->display("error.tpl");
    die;
}
$smarty->assign('question_info', $quiz_info);
$_REQUEST["quizId"] = $quiz_info["quizId"];
$smarty->assign('quizId', $_REQUEST["quizId"]);

$smarty->assign('individual', 'n');

$tikilib->get_perm_object($_REQUEST["quizId"], 'quiz');

$access->check_permission('tiki_p_admin_quizzes');

if (! isset($_REQUEST["optionId"])) {
    $_REQUEST["optionId"] = 0;
}

$smarty->assign('optionId', $_REQUEST["optionId"]);

if ($_REQUEST["optionId"]) {
    $info = $quizlib->get_quiz_question_option($_REQUEST["optionId"]);
} else {
    $info = [];

    $info["optionText"] = '';
    $info["points"] = '';
}

$smarty->assign('optionText', $info["optionText"]);
$smarty->assign('points', $info["points"]);

if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $quizlib->remove_quiz_question_option($_REQUEST["remove"]);
}

if (isset($_REQUEST["save"])) {
    $access->checkCsrf();
    $quizlib->replace_question_option($_REQUEST["optionId"], $_REQUEST["optionText"], $_REQUEST["points"], $_REQUEST["questionId"]);

    $smarty->assign('optionText', '');
    $smarty->assign('optionId', 0);
}

if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'optionText_asc';
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
$channels = $quizlib->list_quiz_question_options($_REQUEST["questionId"], $offset, $maxRecords, $sort_mode, $find);

$smarty->assign_by_ref('cant_pages', $channels["cant"]);

$smarty->assign_by_ref('channels', $channels["data"]);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Display the template
$smarty->assign('mid', 'tiki-edit_question_options.tpl');
$smarty->display("tiki.tpl");
