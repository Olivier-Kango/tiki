<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\TwoFactorAuth\TwoFactorAuthFactory;

$section_class = 'tiki_login';  // This will be body class instead of $section
$inputConfiguration = [
    [
        'staticKeyFilters'     => [
        'twoFactorForm'        => 'string',         //post
        'clearmenucache'       => 'bool',           //post
        'user'                 => 'username',       //post
        ],
    ],
];
include_once("tiki-setup.php");


//Enable Two-Factor Auth Input
$twoFactorForm = 'n';
if (isset($_REQUEST["twoFactorForm"])) {
    $twoFactorForm = 'y';
}
$smarty->assign('twoFactorForm', $twoFactorForm);

$create2FaCodeNormalLogin = isset($_REQUEST["create2FaCodeNormalLogin"]) ? 'y' : 'n';
if ($prefs['twoFactorAuth'] == 'y' && $create2FaCodeNormalLogin === 'y' && ! empty($_REQUEST['tiki_username'])) {
    $tikiUserName = urldecode($_REQUEST['tiki_username']);
    $twoFactorAuth = TwoFactorAuthFactory::getTwoFactorAuth();
    $isTokenGenerated = $twoFactorAuth->generateCode($tikiUserName);
    if ($prefs['twoFactorAuthType'] == 'email2FA' && ! empty($isTokenGenerated)) {
        $message = tr("2FA token generated successfully and sent to your email");
        Feedback::success($message);
    }
}
$smarty->assign('create2FaCodeNormalLogin', $create2FaCodeNormalLogin);

if ($prefs['login_autologin'] == 'y' && $prefs['login_autologin_redirectlogin'] == 'y' && ! empty($prefs['login_autologin_redirectlogin_url'])) {
    $access->redirect($prefs['login_autologin_redirectlogin_url']);
}

if (isset($_REQUEST['clearmenucache'])) {
    TikiLib::lib('menu')->empty_menu_cache();
}
if (isset($_REQUEST['user'])) {
    if ($_REQUEST['user'] == 'admin' && (! isset($_SESSION["groups_are_emulated"]) || $_SESSION["groups_are_emulated"] != "y")) {
        $smarty->assign('showloginboxes', 'y');
        $smarty->assign('adminuser', $_REQUEST['user']);
    } else {
        $smarty->assign('loginuser', $_REQUEST['user']);
    }
}
if (($prefs['useGroupHome'] != 'y' || $prefs['limitedGoGroupHome'] == 'y') && ! isset($_SESSION['loginfrom'])) {
    if (isset($_SERVER['HTTP_REFERER']) && str_starts_with($_SERVER['HTTP_REFERER'], $url_scheme . '://' . $url_host)) {
        $_SESSION['loginfrom'] = $_SERVER['HTTP_REFERER'];
    } else {
        $_SESSION['loginfrom'] = $prefs['tikiIndex'];
    }
}

$headerlib->add_js('$(function() {
    $("#login-user").trigger("focus").trigger("select");
})');

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('headtitle', tra('Log In'));
$smarty->assign('mid', 'tiki-login.tpl');

$smarty->display("tiki.tpl");
