<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [ 'staticKeyFilters' => [
        'data' => 'none',
    ]],
];

include_once("tiki-setup.php");
$csslib = TikiLib::lib('css');
$themelib = TikiLib::lib('theme');
$access->check_feature('feature_editcss');
$access->check_permission('tiki_p_create_css');

//selecting the theme
if (! empty($_SESSION['try_theme']) && ! isset($_REQUEST['theme'])) {
    $theme = $_SESSION['try_theme'];
} elseif (! isset($_REQUEST['theme'])) {
    $theme = '';
} else {
    $theme = $_REQUEST['theme'];
}
$themeOptionName = ThemeLib::extractThemeAndOptionFromString($theme);
$theme_name = $themeOptionName[0];
$theme_option_name = $themeOptionName[1];
$file = ThemeLib::getThemeCssFilePath($theme_name, $theme_option_name);
$smarty->assign('file', $file);

if (! empty($_REQUEST['edit'])) {
    $access->checkCsrf();
    if (($data = file_get_contents($file)) === false) {
        $smarty->assign('msg', tra('The specified file does not exist'));
        $smarty->display('error.tpl');
        die;
    }
    $action = 'edit';
} elseif (! empty($_REQUEST['save']) || ! empty($_REQUEST['save_and_view'])) {
    $access->checkCsrf();
    if (file_exists($file)) {
        $stat = stat($file);
        $mod = $stat['mode'] & 0666;
    } else {
        $mod = null;
    }
    $fp = fopen($file, "w");
    if (! $fp) {
        $smarty->assign('msg', tra("You do not have permission to write the css file") . " $file");
        $smarty->display("error.tpl");
        die;
    }

    fwrite($fp, $_REQUEST['data']);
    fclose($fp);
    if ($mod !== null) {
        chmod($file, $mod);
    }

    if (! empty($_REQUEST['save_and_view'])) {
        $action = 'view';
        header("location: tiki-edit_css.php?theme=$theme");
    } else {
        $action = 'edit';
        header("location: tiki-edit_css.php?theme=$theme&edit=" . tra('Edit') . "");
    }
    $data = '';
} else {
    $action = 'view';
    $data = '';
}

$smarty->assign('action', $action);
$smarty->assign('data', $data);

if (! empty($theme)) {
    $cssfile = ThemeLib::getThemeCssFilePath($theme_name, $theme_option_name);
    $smarty->assign('writable', file_exists($cssfile) ? is_writable($cssfile) : is_writable(dirname($cssfile)));
    $cssdata = $csslib->browse_css($cssfile);
    if ((! $cssdata["error"]) and is_array($cssdata["content"])) {
        $parsedcss = $csslib->parse_css($cssdata["content"]);
    } else {
        $parsedcss = $cssdata["error"];
    }
    $smarty->assign('css', $parsedcss);
}

if (! empty($_REQUEST['try'])) {
    $_SESSION['try_theme'] = $theme;
    header("location: tiki-edit_css.php?theme=$theme");
}

if (! empty($_SESSION['try_theme'])) {
    $try_active = true;
    $smarty->assign('try_active', $try_active);
    list($try_theme, $try_theme_option) = ThemeLib::extractThemeAndOptionFromString($_SESSION['try_theme']);
    $smarty->assign('try_theme', $try_theme);
    $smarty->assign('try_theme_option', $try_theme_option);
}

if (! empty($_REQUEST['cancel_try'])) {
    $_SESSION['try_theme'] = '';
    header("location: tiki-edit_css.php?theme=$theme");
}
$smarty->assign('theme', $theme);
$themes = $themelib->list_themes_and_options();
$smarty->assign('themes', $themes);


// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

$smarty->assign('mid', 'tiki-edit_css.tpl');
$smarty->display("tiki.tpl");
