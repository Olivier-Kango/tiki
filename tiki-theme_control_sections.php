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
        'staticKeyFilters'     => [
        'section'              => 'striptags',    //post
        'assign'               => 'bool',         //post
        'theme'                => 'striptags',    //post
        'delete'               => 'striptags',    //post
        'sec'                  => 'striptags',    //post
        ],
        'catchAllUnset' => null
    ],
];
require_once('tiki-setup.php');
$themecontrollib = TikiLib::lib('themecontrol');
$categlib = TikiLib::lib('categ');
$themelib = TikiLib::lib('theme');

$access->check_feature('feature_theme_control', '', 'look');
$access->check_permission('tiki_p_admin');

$auto_query_args = ['find', 'sort_mode', 'offset', 'theme', 'theme_option', 'section'];
$smarty->assign('a_section', isset($_REQUEST['section']) ? $_REQUEST['section'] : '');

$themes = $themelib->list_themes_and_options();
$smarty->assign('themes', $themes);

if (isset($_REQUEST['assign'])) {
    $access->checkCsrf();
    $themecontrollib->tc_assign_section($_REQUEST['section'], $_REQUEST['theme']);
}
if (isset($_REQUEST['delete'])) {
    $access->checkCsrf();
    if (isset($_REQUEST["sec"]) && is_array($_REQUEST["sec"])) {
        foreach (array_keys($_REQUEST["sec"]) as $sec) {
            $themecontrollib->tc_remove_section($sec);
        }
    } else {
        Feedback::error(tr('No section selected.'));
    }
}
$channels = $themecontrollib->tc_list_sections(0, -1, 'section_asc', '');
$smarty->assign_by_ref('channels', $channels["data"]);
$smarty->assign('sections', $sections_enabled);
$smarty->assign('mid', 'tiki-theme_control_sections.tpl');
$smarty->display('tiki.tpl');
