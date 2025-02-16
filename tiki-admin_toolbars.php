<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Lib\core\Toolbar\ToolbarItem;

$inputConfiguration = [
                [
        'staticKeyFilters'                    => [
                'save'                        => 'alpha',          //post
                'load'                        => 'alpha',          //post
                'pref'                        => 'striptags',      //post
                'section'                     => 'striptags',      //post
                'comments'                    => 'bool',           //post
                'view_mode'                   => 'striptags',      //post
                'reset_all_custom_tools'      => 'bool',           //post
                'reset'                       => 'bool',           //post
                'reset_global'                => 'bool',           //post
                'save_tool'                   => 'bool',           //post
                'tool_name'                   => 'striptags',      //post
                'tool_label'                  => 'striptags',      //post
                'tool_icon'                   => 'none',           //post
                'tool_token'                  => 'text',           //post
                'tool_syntax'                 => 'text',           //post
                'tool_type'                   => 'striptags',      //post
                'tool_plugin'                 => 'striptags',      //post
                'delete_tool'                 => 'bool',           //post
                    ],
                ],
];

$auto_query_args = ['section', 'comments', 'autoreload', 'view_mode'];

require_once 'tiki-setup.php';

$access->check_permission('tiki_p_admin');
$access->check_feature('feature_jquery_ui');

$sections = [ 'global' => tra('Global'), 'admin' => tra('Admin')];
$sections2 = [];

if ($prefs['feature_wiki'] == 'y') {
    $sections2['wiki page'] = tra('Wiki Pages');
}
if ($prefs['feature_trackers'] == 'y') {
    $sections2['trackers'] = tra('Trackers');
}
if ($prefs['feature_blogs'] == 'y') {
    $sections2['blogs'] = tra('Blogs');
}
if ($prefs['feature_calendar'] == 'y') {
    $sections2['calendar'] = tra('Calendars');
}
if ($prefs['feature_articles'] == 'y') {
    $sections2['cms'] = tra('Articles');
}
if ($prefs['feature_faqs'] == 'y') {
    $sections2['faqs'] = tra('FAQs');
}
if ($prefs['feature_newsletters'] == 'y') {
    $sections2['newsletters'] = tra('Newsletters');
}
if ($prefs['feature_forums'] == 'y') {
    $sections2['forums'] = tra('Forums');
}
if ($prefs['feature_sheet'] == 'y') {
    $sections2['sheet'] = tra('Spreadsheets');
}
if ($prefs['wikiplugin_wysiwyg'] == 'y') {
    $sections2['wysiwyg_plugin'] = tra('WYSIWYG Plugin');
}

asort($sections2);
$sections = array_merge($sections, $sections2);


if (isset($_REQUEST['section']) && in_array($_REQUEST['section'], array_keys($sections))) {
    $section = $_REQUEST['section'];
} else {
    $keys = array_keys($sections);
    $section = reset($keys);
}
if (isset($_REQUEST['comments']) && $_REQUEST['comments'] == 'on') {
    $show_comments = true;
} else {
    $show_comments = false;
}

foreach ($sections as $skey => $sval) {
    if (
        isset($prefs['toolbar_' . $skey . ($show_comments ? '_comments' : '') . 'modified'])
        && $prefs['toolbar_' . $skey . ($show_comments ? '_comments' : '') . 'modified'] == 'y'
    ) {
        $sections[$skey] = $sval . ' *';
    }
}

$view_mode = isset($_REQUEST['view_mode']) ? $_REQUEST['view_mode'] : '';
if ($view_mode === 'sheet' && $section !== 'sheet') {
    $view_mode = '';
    $_REQUEST['view_mode'] = '';
}
$smarty->assign('view_mode', $view_mode);

if (! empty($_REQUEST['reset_all_custom_tools']) && $access->checkCsrf()) {
    ToolbarItem::deleteAllCustomTools();
    $access->redirect('tiki-admin_toolbars.php');
}

if (isset($_REQUEST['save'], $_REQUEST['pref'])) {
    $prefName = 'toolbar_' . $section . ($show_comments ? '_comments' : '');
    $tikilib->set_preference($prefName, $_REQUEST['pref']);
    $tikilib->set_preference($prefName . 'modified', 'y');
}

if ((isset($_REQUEST['reset']) && $section != 'global') || (isset($_REQUEST['reset_global']) && $section == 'global')) {
    $prefName = 'toolbar_' . $section . ($show_comments ? '_comments' : '');
    $tikilib->delete_preference($prefName);
    $tikilib->set_preference($prefName . 'modified', 'n');
    header('location: ?' . smarty_function_query(['_urlencode' => 'n'], $smarty->getEmptyInternalTemplate()));
}

if (! empty($_REQUEST['save_tool']) && ! empty($_REQUEST['tool_name'])) {   // input from the tool edit form
    ToolbarItem::saveTool(
        $_REQUEST['tool_name'],
        $_REQUEST['tool_label'],
        $_REQUEST['tool_icon'],
        $_REQUEST['tool_token'],
        $_REQUEST['tool_syntax'],
        $_REQUEST['tool_type'],
        $_REQUEST['tool_plugin']
    );

    header('location: ?' . smarty_function_query(['_urlencode' => 'n'], $smarty->getEmptyInternalTemplate()));
}

$current = $tikilib->get_preference('toolbar_' . $section . ($show_comments ? '_comments' : ''));
if (empty($current)) {
    $current = $tikilib->get_preference('toolbar_global' . ($show_comments ? '_comments' : ''));
    $smarty->assign('not_global', false);
} else {
    $smarty->assign('not_global', true);
}
$smarty->assign('not_default', false);
if ($section == 'global') {
    $cachelib = TikiLib::lib('cache');
    if ($defprefs = $cachelib->getSerialized("tiki_default_preferences_cache")) {
        if ($defprefs['toolbar_global' . ($show_comments ? '_comments' : '')] != $current) {
            $smarty->assign('not_default', true);
        }
    }
}

if (! empty($_REQUEST['delete_tool']) && ! empty($_REQUEST['tool_name'])) { // input from the tool edit form
    ToolbarItem::deleteTool($_REQUEST['tool_name']);
    if (strpos($_REQUEST['tool_name'], $current) !== false) {
        $current = str_replace($_REQUEST['tool_name'], '', $current);
        $current = str_replace(',,', ',', $current);
        $prefName = 'toolbar_' . $section . ($show_comments ? '_comments' : '');
        $tikilib->set_preference($prefName, $current);
    }
}

if (! empty($current)) {
    $current = preg_replace('/\s+/', '', $current);
    $current = trim($current, '/');
    $current = explode('/', $current);
    $loadedRows = count($current);
    foreach ($current as &$line) {
        $line = str_replace('|', ',|,', $line);
        $line = [explode(',', $line)];
    }
    $rowCount = max($loadedRows, 1) + 1;
} else {
    $rowCount = 1;
}

$init = '';
$setup = '';
$map = [];

$qtlist = ToolbarItem::getList();
$usedqt = [];
$qt_p_list = [];
$qt_w_list = [];

if (is_array($current) || is_object($current) || is_iterable($current)) {
    foreach ($current as &$line) {
        foreach ($line as $bit) {
            $usedqt = array_merge($usedqt, $bit);
        }
    }
}

$customqt = ToolbarItem::getCustomList();

$view_mode = ! empty($_REQUEST['view_mode']) ? $_REQUEST['view_mode'] : '';

foreach ($qtlist as $name) {
    $tag = ToolbarItem::getTag($name);
    if (! $tag) {
        $tag = ToolbarItem::getTag($name, true);
        if (! $tag) {
            $tag = ToolbarItem::getTag($name, true, true);
            continue;
        }
    }
    $tag->setDomElementId('dummy');

    $wys = strlen($tag->getWysiwygToken(false)) ? 'qt-wys' : '';
    $wyswik = strlen($tag->getWysiwygWikiToken('dummy', false)) ? 'qt-wyswik' : '';
    $test_html = $tag->getWikiHtml();
    $wiki = strlen($test_html) > 0 ? 'qt-wiki' : '';
    $wiki = strpos($test_html, 'qt-sheet') !== false ? 'qt-sheet' : $wiki;
    $cust = ToolbarItem::isCustomTool($name) ? 'qt-custom' : '';
    $avail = $tag->isAccessible() ? '' : 'qt-noaccess';
    $icon = $tag->getIconHtml();
    $margins = $test_html === '||' ? 'mx-auto' : '';

    if (strpos($name, 'wikiplugin_') !== false) {
        $plug = 'qt-plugin';
        $label = substr($name, 11);
        $qt_p_list[] = $name;
    } else {
        $plug = '';
        $label = $name;
        if (empty($cust)) {
            $qt_w_list[] = $name;
        }
    }

    $label = htmlspecialchars($label);
    $label .= '<input type="hidden" name="token" value="' . $tag->getWysiwygToken(false) . '" />';
    $label .= '<input type="hidden" name="syntax" value="' . htmlspecialchars($tag->getSyntax('dummy')) . '" />';
    $label .= '<input type="hidden" name="type" value="' . $tag->getType() . '" />';
    $label .= '<input type="hidden" name="label" value="' . $tag->getLabel() . '" />';

    if ($tag->getType() == 'Wikiplugin') {
        $label .= '<input type="hidden" name="plugin" value="' . $tag->getPluginName() . '" />';
    }

    $visible = true;
    if ($view_mode === 'both') {
        $visible = (! empty($wys) || ! empty($wiki));
    } elseif ($view_mode === 'wiki') {
        $visible = ! empty($wiki);
    } elseif ($view_mode === 'wysiwyg') {
        $visible = ! empty($wys);
    } elseif ($view_mode === 'wysiwyg_wiki') {
        $visible = ! empty($wyswik);
    } elseif ($view_mode === 'sheet') {
        $visible = (strpos($wiki, 'qt-sheet') !== false);
    }

    $text_label = $tag->getLabel($name);

    $qtelement[$name] = [
        'name' => $name,
        'class' => "toolbar qt-$name $wys $wiki $wyswik $plug $cust $avail $margins",
        'html' => "$icon<span>$label</span>",
        'label' => $text_label,
        'visible' => $visible,
    ];
}

$headerlib->add_jsfile('lib/toolbars/tiki-admin_toolbars.js');

$display_w = array_diff($qt_w_list, $usedqt);

if (! in_array('-', $display_w)) {
    array_unshift($display_w, '-');
}

$display_p = array_diff($qt_p_list, $usedqt);
$display_c = array_diff($customqt, $usedqt);

sort($display_c);
sort($display_p);
sort($display_w);

$headerlib->add_cssfile('themes/base_files/feature_css/admin.css');

$plugins = [];

$parserlib = TikiLib::lib('parser');

foreach ($parserlib->plugin_get_list() as $name) {
    $info = $parserlib->plugin_info($name);
    if (isset($info['prefs']) && is_array($info['prefs']) && count($info['prefs']) > 0) {
        $plugins[$name] = $info;
    }
}

$smarty->assign('plugins', $plugins);

$smarty->assign('show_comments', $show_comments);
$smarty->assign('loaded', $section);
$smarty->assign('rows', range(0, $rowCount - 1));
$smarty->assign('rowCount', $rowCount);
$smarty->assign('sections', $sections);
$smarty->assign_by_ref('qtelement', $qtelement);
$smarty->assign_by_ref('display_w', $display_w);
$smarty->assign_by_ref('display_p', $display_p);
$smarty->assign_by_ref('display_c', $display_c);

$smarty->assign_by_ref('current', $current);
$smarty->assign('mid', 'tiki-admin_toolbars.tpl');
$smarty->display('tiki.tpl');
