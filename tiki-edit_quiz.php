<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'                => [
            'quizId'                      => 'int',            //post
            'questionsPerPage'            => 'int',            //post
            'save'                        => 'bool',           //post
            'publish_Meridian'            => 'digits',         //post
            'publish_Hour'                => 'digits',         //post
            'expire_Meridian'             => 'digits',         //post
            'expire_Hour'                 => 'digits',         //post
            'publish_Minute'              => 'digits',         //post
            'publish_Month'               => 'digits',         //post
            'publish_Day'                 => 'digits',         //post
            'publish_Year'                => 'digits',         //post
            'expire_Minute'               => 'digits',         //post
            'expire_Month'                => 'digits',         //post
            'expire_Day'                  => 'digits',         //post
            'expire_Year'                 => 'digits',         //post
            'canRepeat'                   => 'bool',           //get
            'storeResults'                => 'bool',           //post
            'immediateFeedback'           => 'bool',           //post
            'showAnswers'                 => 'alpha',          //get
            'shuffleQuestions'            => 'bool',           //post
            'timeLimited'                 => 'bool',           //post
            'name'                        => 'string',         //post
            'description'                 => 'xss',            //get
            'timeLimit'                   => 'digits',         //post
            'passingperct'                => 'string',         //post
            'remove'                      => 'int',            //post
            'sort_mode'                   => 'word',          //get
            'offset'                      => 'int',            //get
            'find'                        => 'alpha',          //get
        ],
    ],
];
require_once('tiki-setup.php');

$access->check_feature('feature_quizzes');

$quizlib = TikiLib::lib('quiz');

if (! isset($_REQUEST["quizId"])) {
    $_REQUEST["quizId"] = 0;
}

$smarty->assign('quizId', $_REQUEST["quizId"]);

$smarty->assign('individual', 'n');

$tikilib->get_perm_object($_REQUEST["quizId"], 'quiz');

$access->check_permission('tiki_p_admin_quizzes');

$auto_query_args = [
            'quizId',
            'offset',
            'sort_mode',
            'find',
];

$_REQUEST["questionsPerPage"] = 999;
//Use 12- or 24-hour clock for $publishDate time selector based on admin and user preferences
$userprefslib = TikiLib::lib('userprefs');
$smarty->assign('use_24hr_clock', $userprefslib->get_user_clock_pref($user));


$info = [];
$info["name"] = '';
$info["description"] = '';
$info["publishDate"] = $tikilib->now;
$cur_time = explode(',', $tikilib->date_format('%Y,%m,%d,%H,%M,%S', $info["publishDate"]));
$info["expireDate"] = $tikilib->make_time($cur_time[3], $cur_time[4], $cur_time[5], $cur_time[1], $cur_time[2], $cur_time[0] + 1);
$info["canRepeat"] = 'n';
$info["storeResults"] = 'n';
$info["immediateFeedback"] = 'n';
$info["showAnswers"] = 'n';
$info["shuffleQuestions"] = 'n';
$info["shuffleAnswers"] = 'n';
$info["questionsPerPage"] = 10;
$info["timeLimited"] = 'n';
$info["passingperct"] = '';
$info["timeLimit"] = 60 * 60;

if (isset($_REQUEST["save"])) {
    $access->checkCsrf();

    //Convert 12-hour clock hours to 24-hour scale to compute time
    if (! empty($_REQUEST['publish_Meridian'])) {
        $_REQUEST['publish_Hour'] = date('H', strtotime($_REQUEST['publish_Hour'] . ':00 ' . $_REQUEST['publish_Meridian']));
    }
    if (! empty($_REQUEST['expire_Meridian'])) {
        $_REQUEST['expire_Hour'] = date('H', strtotime($_REQUEST['expire_Hour'] . ':00 ' . $_REQUEST['expire_Meridian']));
    }
    # convert from the displayed 'site' time to 'server' time
     $publishDate = $tikilib->make_time($_REQUEST["publish_Hour"], $_REQUEST["publish_Minute"], 0, $_REQUEST["publish_Month"], $_REQUEST["publish_Day"], $_REQUEST["publish_Year"]);
     $expireDate = $tikilib->make_time($_REQUEST["expire_Hour"], $_REQUEST["expire_Minute"], 0, $_REQUEST["expire_Month"], $_REQUEST["expire_Day"], $_REQUEST["expire_Year"]);

    if (isset($_REQUEST["canRepeat"]) && $_REQUEST["canRepeat"] == 'on') {
        $_REQUEST["canRepeat"] = 'y';
    } else {
        $_REQUEST["canRepeat"] = 'n';
    }

    if (isset($_REQUEST["storeResults"]) && $_REQUEST["storeResults"] == 'on') {
        $_REQUEST["storeResults"] = 'y';
    } else {
        $_REQUEST["storeResults"] = 'n';
    }

    if (isset($_REQUEST["immediateFeedback"]) && $_REQUEST["immediateFeedback"] == 'on') {
        $_REQUEST["immediateFeedback"] = 'y';
    } else {
        $_REQUEST["immediateFeedback"] = 'n';
    }

    if (isset($_REQUEST["showAnswers"]) && $_REQUEST["showAnswers"] == 'on') {
        $_REQUEST["showAnswers"] = 'y';
    } else {
        $_REQUEST["showAnswers"] = 'n';
    }

    if (isset($_REQUEST["shuffleQuestions"]) && $_REQUEST["shuffleQuestions"] == 'on') {
        $_REQUEST["shuffleQuestions"] = 'y';
    } else {
        $_REQUEST["shuffleQuestions"] = 'n';
    }

    if (isset($_REQUEST["shuffleAnswers"]) && $_REQUEST["shuffleAnswers"] == 'on') {
        $_REQUEST["shuffleAnswers"] = 'y';
    } else {
        $_REQUEST["shuffleAnswers"] = 'n';
    }

    if (isset($_REQUEST["timeLimited"]) && $_REQUEST["timeLimited"] == 'on') {
        $_REQUEST["timeLimited"] = 'y';
    } else {
        $_REQUEST["timeLimited"] = 'n';
    }

    // Pass dummy "n" for immediateFeedback, showAnswers, shuffleQuestions and shuffleAnswers, which are not implemented.
    $qid = $quizlib->replace_quiz(
        $_REQUEST["quizId"],
        $_REQUEST["name"],
        $_REQUEST["description"],
        $_REQUEST["canRepeat"],
        $_REQUEST["storeResults"],
        'n',
        'n',
        'n',
        'n',
        $_REQUEST["questionsPerPage"],
        $_REQUEST["timeLimited"],
        $_REQUEST["timeLimit"],
        $publishDate,
        $expireDate,
        $_REQUEST["passingperct"]
    );
    $cat_type = 'quiz';
    $cat_objid = $qid;
    $cat_desc = substr($_REQUEST["description"], 0, 200);
    $cat_name = $_REQUEST["name"];
    $cat_href = "tiki-take_quiz.php?quizId=" . $cat_objid;
    include_once("categorize.php");
    $_REQUEST["quizId"] = 0;
    $smarty->assign('quizId', $_REQUEST["quizId"]);
    $quizId = 0;
} elseif ($_REQUEST["quizId"]) {
    $result = $quizlib->get_quiz($_REQUEST["quizId"]);
    if (! $result) {
        Feedback::error(tra("The quiz you are trying to edit was not found. Please verify the quiz ID or create a new one."));
    } else {
        $info = $result;
    }

    if (! isset($info["publishDate"])) {
        $info["publishDate"] = $tikilib->now;
    }
    if (! isset($info["expireDate"])) {
        $cur_time = explode(',', $tikilib->date_format('%Y,%m,%d,%H,%M,%S', $tikilib->now));
        $info["expireDate"] = $tikilib->make_time($cur_time[3], $cur_time[4], $cur_time[5], $cur_time[1], $cur_time[2], $cur_time[0] + 1);
    }
}

$smarty->assign('name', $info["name"]);
$smarty->assign('description', $info["description"]);
$smarty->assign('canRepeat', $info["canRepeat"]);
$smarty->assign('storeResults', $info["storeResults"]);
$smarty->assign('immediateFeedback', $info["immediateFeedback"]);
$smarty->assign('showAnswers', $info["showAnswers"]);
$smarty->assign('shuffleQuestions', $info["shuffleQuestions"]);
$smarty->assign('shuffleAnswers', $info["shuffleAnswers"]);
$smarty->assign('questionsPerPage', $info["questionsPerPage"]);
$smarty->assign('timeLimited', $info["timeLimited"]);
$smarty->assign('timeLimit', $info["timeLimit"]);
$smarty->assign('passingperct', $info["passingperct"]);

if (isset($_REQUEST["remove"]) && $access->checkCsrf()) {
    $quizlib->remove_quiz($_REQUEST["remove"]);
}

if (! isset($_REQUEST["sort_mode"])) {
    $sort_mode = 'created_desc';
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
$channels = $quizlib->list_quizzes($offset, $maxRecords, $sort_mode, $find);

$temp_max = count($channels["data"]);
for ($i = 0; $i < $temp_max; $i++) {
    if ($userlib->object_has_one_permission($channels["data"][$i]["quizId"], 'quiz')) {
        $channels["data"][$i]["individual"] = 'y';

        if ($userlib->object_has_permission($user, $channels["data"][$i]["quizId"], 'quiz', 'tiki_p_take_quiz')) {
            $channels["data"][$i]["individual_tiki_p_take_quiz"] = 'y';
        } else {
            $channels["data"][$i]["individual_tiki_p_take_quiz"] = 'n';
        }

        if ($userlib->object_has_permission($user, $channels["data"][$i]["quizId"], 'quiz', 'tiki_p_view_quiz_stats')) {
            $channels["data"][$i]["individual_tiki_p_view_quiz_stats"] = 'y';
        } else {
            $channels["data"][$i]["individual_tiki_p_view_quiz_stats"] = 'n';
        }

        if ($userlib->object_has_permission($user, $channels["data"][$i]["quizId"], 'quiz', 'tiki_p_view_user_stats')) {
            $channels["data"][$i]["individual_tiki_p_view_user_stats"] = 'y';
        } else {
            $channels["data"][$i]["individual_tiki_p_view_user_stats"] = 'n';
        }

        if (
            $tiki_p_admin
            == 'y' || $userlib->object_has_permission($user, $channels["data"][$i]["quizId"], 'quiz', 'tiki_p_admin_quizzes')
        ) {
            $channels["data"][$i]["individual_tiki_p_take_quiz"] = 'y';

            $channels["data"][$i]["individual_tiki_p_view_quiz_stats"] = 'y';
            $channels["data"][$i]["individual_tiki_p_admin_quizzes"] = 'y';
            $channels["data"][$i]["individual_tiki_p_view_user_stats"] = 'y';
        }
    } else {
        $channels["data"][$i]["individual"] = 'n';
    }
}

$smarty->assign_by_ref('cant_pages', $channels["cant"]);

$smarty->assign_by_ref('channels', $channels["data"]);

// Fill array with possible number of questions per page
$qpp = [ 1, 2, 3, 4 ];

for ($i = 5; $i < 50; $i += 5) {
    $qpp[] = $i;
}

$hrs = [];

for ($i = 0; $i < 10; $i++) {
    $hrs[] = $i;
}

$mins = [];

for ($i = 1; $i < 120; $i++) {
    $mins[] = $i;
}

$smarty->assign('qpp', $qpp);
$smarty->assign('hrs', $hrs);
$smarty->assign('mins', $mins);

$cat_type = 'quiz';
$cat_objid = $_REQUEST["quizId"];
include_once("categorize_list.php");

$smarty->assign('publishDate', $info['publishDate']);
$smarty->assign('publishDateSite', $info['publishDate']);
$smarty->assign('expireDate', $info['expireDate']);
$smarty->assign('expireDateSite', $info['expireDate']);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Display the template
$smarty->assign('mid', 'tiki-edit_quiz.tpl');
$smarty->display("tiki.tpl");
