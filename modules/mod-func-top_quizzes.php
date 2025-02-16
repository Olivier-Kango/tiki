<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_top_quizzes_info()
{
    return [
        'name' => tra('Top Quizzes'),
        'description' => tra('Displays the specified number of quizzes with links to them, starting with the one having the most hits.'),
        'prefs' => ['feature_quizzes'],
        'params' => [],
        'common_params' => ['nonums', 'rows']
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_top_quizzes($mod_reference, $module_params)
{
    $smarty = TikiLib::lib('smarty');
    $quizlib = TikiLib::lib('quiz');

    $ranking = $quizlib->list_quiz_sum_stats(0, $mod_reference["rows"], 'timesTaken_desc', '');
    $smarty->assign('modTopQuizzes', $ranking["data"]);
}
