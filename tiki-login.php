<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\TwoFactorAuth\TwoFactorAuthFactory;
use Tiki\TwoFactorAuth\Exception\TwoFactorAuthException;

$inputConfiguration = [
    [
        'staticKeyFilters'     => [
        'code'                 => 'string',         //get
        'user'                 => 'username',       //post
        'pass'                 => 'password',       //post
        'cas'                  => 'bool',           //post
        'username'             => 'username',       //post
        'su'                   => 'word',           //post
        'intertiki'            => 'string',         //post
        'twoFactorForm'        => 'string',         //post
        'twoFactorAuthCode'    => 'string',         //post
        'page'                 => 'pagename',       //post
        'url'                  => 'url',            //post
        'rme'                  => 'bool',           //post
        'ticket'               => 'string',         //post
        ],
    ],
];

$bypass_siteclose_check = 'y';

if (empty($_POST['user'])) {
    unset($_POST['user']);  // $_POST['user'] is not allowed to be empty if set in tiki-setup.php
}
require_once('tiki-setup.php');
global $prefs;

// Refresh not logged in since 30 days user's accounts list
$userlib = TikiLib::lib('user');
$userlib->refresh_locked_users_list();

$login_url_params = '';
$isOpenIdValid = false;

if (! empty($_REQUEST['code']) && $prefs['auth_method'] == 'openid_connect' && TikiLib::lib('openidconnect')->isAvailable()) {
    $_REQUEST['user'] = '';
} elseif (isset($_REQUEST['cas']) && $_REQUEST['cas'] == 'y' && $prefs['auth_method'] == 'cas') {
    $login_url_params = '?cas=y';
    $_REQUEST['user'] = '';
} elseif ($prefs['twoFactorAuth'] === 'n' && (! isset($_REQUEST['user']) or isset($_REQUEST['username']))) {
    if (! $https_mode && $prefs['https_login'] == 'required') {
        header('Location: ' . $base_url_https . 'tiki-login_scr.php');
    } else {
        header('Location: ' . $base_url . 'tiki-login_scr.php');
    }
    die;
}
$smarty->assign('errortype', 'login'); // to avoid any redirection to the login box if error
// Alert user if cookies are switched off
if (ini_get('session.use_cookies') == 1 && ! isset($_COOKIE[ session_name() ]) && $prefs['session_silent'] != 'y') {
    $smarty->assign('msg', tra('Cookies must be enabled to log in to this site'));
    $smarty->display('error.tpl');
    exit;
}

// Redirect to HTTPS if we are not in HTTPS but we require HTTPS login
if (! $https_mode && $prefs['https_login'] == 'required') {
    header('Location: ' . $base_url_https . $prefs['login_url'] . $login_url_params);
    exit;
}

if ($prefs['session_silent'] == 'y') {
    session_start();
}

if ($prefs['twoFactorAuth'] === 'y' && ! empty($_SESSION['tiki_creds_username']) && ! empty($_SESSION['tiki_creds_password'])) {
    $_REQUEST['user'] = $_SESSION['tiki_creds_username'];
    $_REQUEST['pass'] = $_SESSION['tiki_creds_password'];
    unset($_SESSION['tiki_creds_username']);
    unset($_SESSION['tiki_creds_password']);
}

// Remember where user is logging in from and send them back later; using session variable for those of us who use WebISO services
// Note that login from will always be a complete URL (http://...)
if (! isset($_SESSION['loginfrom']) && isset($_SERVER['HTTP_REFERER']) && ! preg_match('|/login|', $_SERVER['HTTP_REFERER']) && ! preg_match('|logout|', $_SERVER['HTTP_REFERER'])) {
    $_SESSION['loginfrom'] = $_SERVER['HTTP_REFERER'];
    if (! preg_match('/^http/', $_SESSION['loginfrom'])) {
        if (
            $_SESSION['loginfrom'] [
            0] == '/'
        ) {
            $_SESSION['loginfrom'] = $url_scheme . '://' . $url_host . (($url_port != '') ? ":$url_port" : '') . $_SESSION['loginfrom'];
        } else {
            $_SESSION['loginfrom'] = $base_url . $_SESSION['loginfrom'];
        }
    }
    // prevent redirects to external sites after login
    if (! str_starts_with($_SESSION['loginfrom'], $url_scheme . '://' . $url_host)) {
        unset($_SESSION['loginfrom']);
    }
}
if (isset($_REQUEST['su']) && $access->checkCsrf(true)) {
    $loginlib = TikiLib::lib('login');

    if ($loginlib->isSwitched() && $_REQUEST['su'] == 'revert') {
        $loginlib->revertSwitch();
        $access->redirect($_SESSION['loginfrom']);
    } elseif ($tiki_p_admin == 'y') {
        if (empty($_REQUEST['username'])) {
            $smarty->assign('msg', tra('Username field cannot be empty. Please go back and try again.'));
            $smarty->display('error.tpl');
            exit;
        }
        if ($prefs['user_show_realnames'] == 'y') {
            $finalusers = $userlib->find_best_user([$_REQUEST['username']], '', 'login');
            if (count($finalusers) === 1 && ! empty($finalusers[0])) {
                $_REQUEST['username'] = $finalusers[0];
            }
        }
        if ($userlib->user_exists($_REQUEST['username'])) {
            $loginlib->switchUser($_REQUEST['username']);
        }

        $access->redirect($_SESSION['loginfrom']);
    }
}
$requestedUser = isset($_REQUEST['user']) ? trim($_REQUEST['user']) : false;
$pass = isset($_REQUEST['pass']) ? trim($_REQUEST['pass']) : false;
$isvalid = false;
$isdue = false;
// admin is always local
if ($requestedUser == 'admin') {
    $prefs['feature_intertiki'] = 'n';
}
// Determine the intertiki domain
if ($prefs['feature_intertiki'] == 'y' && $prefs['feature_intertiki_server'] != 'y') {
    if (! empty($prefs['feature_intertiki_mymaster'])) {
        $_REQUEST['intertiki'] = $prefs['feature_intertiki_mymaster'];
    } elseif (strstr($requestedUser, '@')) {
        list($requestedUser, $intertiki_domain) = explode('@', $requestedUser);
        $_REQUEST['intertiki'] = $intertiki_domain;
    }
} else {
    unset($_REQUEST['intertiki']);
}

//Enable Two-Factor Auth Input
$twoFactorForm = 'n';
if (isset($_REQUEST["$twoFactorForm"])) {
    $twoFactorForm = 'y';
}
$smarty->assign('twoFactorForm', $twoFactorForm);

// Go through the intertiki process
if (
    isset($_REQUEST['intertiki']) and in_array($_REQUEST['intertiki'], array_keys($prefs['interlist']))
    && $access->checkCsrf(null, null, null, null, null, 'page')
) {
    $rpcauth = $userlib->intervalidate($prefs['interlist'][$_REQUEST['intertiki']], $requestedUser, $pass, ! empty($prefs['feature_intertiki_mymaster']) ? true : false);
    if (! $rpcauth) {
        $logslib->add_log('login', 'intertiki : ' . $requestedUser . '@' . $_REQUEST['intertiki'] . ': Failed');
        $smarty->assign('msg', tra('Unable to contact remote server.'));
        $smarty->display('error.tpl');
        exit;
    } else {
        if ($faultCode = $rpcauth->faultCode()) {
            if ($faultCode == 102) {
                $faultCode = 101; // disguise inexistent user
                $userlib->remove_user($requestedUser);
            }
            $user_msg = tra('XMLRPC Error: ') . $faultCode . ' - ' . tra($rpcauth->faultString());
            $log_msg = tra('XMLRPC Error: ') . $rpcauth->faultCode() . ' - ' . tra($rpcauth->faultString());
            $logslib->add_log('login', 'intertiki : ' . $requestedUser . '@' . $_REQUEST['intertiki'] . ': ' . $log_msg);
            $smarty->assign('msg', $user_msg);
            $smarty->display('error.tpl');
            exit;
        } else {
            $isvalid = true;
            $isdue = false;
            $logslib->add_log('login', 'intertiki : ' . $requestedUser . '@' . $_REQUEST['intertiki']);
            if (! empty($prefs['feature_intertiki_mymaster'])) {
                // this is slave intertiki site
                $response_value = $rpcauth->value();
                $avatarData = '';
                if ($rpcauth->valueType() == 'xmlrpcvals') {
                    if ($response_value->kindOf() == 'struct') {
                        foreach ($response_value as $key => $value) {
                            if ($key == '') {
                                break;
                            } elseif ($key == 'user_details') {
                                $user_details = unserialize($value->scalarval());
                            } elseif ($key == 'avatarData') {
                                $avatarData = $value->scalarval();
                            }
                        }
                    } else {
                        $user_details = unserialize($response_value->scalarval());
                    }
                }
                $requestedUser = $user_details['info']['login']; // use the correct capitalization
                if (! $userlib->user_exists($requestedUser)) {
                    if (! $userlib->add_user($requestedUser, '', $user_details['info']['email'])) {
                        $logslib->add_log('login', 'intertiki : login creation failed');
                        $smarty->assign('msg', tra('Unable to create login'));
                        $smarty->display('error.tpl');
                        die;
                    }
                } else {
                    $userlib->update_lastlogin($requestedUser);
                }
                $userlib->set_user_fields($user_details['info']);
                $user = $requestedUser;
                if ($prefs['feature_userPreferences'] == 'y' && $prefs['feature_intertiki_import_preferences'] == 'y') {
                    $userprefslib = TikiLib::lib('userprefs');
                    if (! empty($avatarData)) {
                        $userprefslib->set_user_avatar($user, 'u', '', $user_details['info']['avatarName'], $user_details['info']['avatarSize'], $user_details['info']['avatarFileType'], $avatarData, false);
                    }
                    $userlib->set_user_preferences($user, $user_details['preferences']);
                }
                if ($prefs['feature_intertiki_import_groups'] == 'y') {
                    if ($prefs['feature_intertiki_imported_groups']) {
                        $groups = preg_split('/\s*,\s*/', $prefs['feature_intertiki_imported_groups']);
                        foreach ($groups as $group) {
                            if (in_array(trim($group), $user_details['groups']) && $userlib->group_exists(trim($group))) {
                                $userlib->assign_user_to_group($user, trim($group));
                            }
                        }
                    } else {
                        $filteredGroups = array_filter(
                            $user_details['groups'],
                            function ($group) use ($userlib) {
                                return $userlib->group_exists($group);
                            }
                        );
                        $userlib->assign_user_to_groups($user, $filteredGroups);
                    }
                } else {
                    $groups = preg_split('/\s*,\s*/', $prefs['interlist'][$prefs['feature_intertiki_mymaster']]['groups']);
                    if (empty($groups) || empty($groups[0])) {
                        $smarty->assign('msg', tra('No groups set on Intertiki client.'));
                        $smarty->display('error.tpl');
                        exit;
                    }
                    foreach ($groups as $group) {
                        $userlib->assign_user_to_group($user, trim($group));
                    }
                }
            } else {
                $user = $requestedUser . '@' . $_REQUEST['intertiki'];
                $prefs['feature_userPreferences'] = 'n';
            }
        }
    }
} elseif ($prefs['auth_method'] == 'openid_connect' && isset($_GET['code'])) {
    try {
        $oicLib = TikiLib::lib('openidconnect');
        $token = $oicLib->getAccessToken($_GET['code']);
        $idToken = $token->getIdToken();
        $name = $idToken->claims()->get('preferred_username', false) ?: $idToken->claims()->get('name', false);
        $email = $idToken->claims()->get('email', false);

        $username = $userlib->get_user_by_email($email);

        if (! $username && $email && $oicLib->canCreateUserTiki()) {
            // Remove invalid characters, based on username_pattern pref
            $username = preg_replace('/[^ \'\-_a-zA-Z0-9@\.]/', '_', $name);
            $user = $userlib->add_user($username, '', $email);

            if (! $user) {
                $logslib->add_log(
                    'login',
                    'openid_connect : login creation failed'
                );
                $smarty->assign('msg', tra('Unable to create login'));
                $smarty->display('error.tpl');
                exit;
            }

            $userlib->disable_tiki_auth($username); //disable that user's password in tiki - since we use OpenIdConnect
        }

        if ($username) {
            $userlib->update_lastlogin($username);
            $isvalid = true;
            $isOpenIdValid = true;
            $user = $username;
        } else {
            $error = USER_NOT_FOUND;
            $isvalid = false;
        }
    } catch (Exception $e) {
        $logslib->add_log('login', 'openid_connect : ' . $e->getMessage());
        $smarty->assign('msg', tra('An error occurred trying to login. Please contact the administrator.'));
        $smarty->display('error.tpl');
        exit;
    }
} else {
    // Verify user is valid
    $ret = $userlib->validate_user($requestedUser, $pass);
    if (count($ret) == 3) {
        $ret[] = null;
    }
    list($isvalid, $requestedUser, $error, $method) = $ret;
    // If the password is valid but it is due then force the user to change the password by
    // sending the user to the new password change screen without letting him use tiki
    // The user must re-enter the old password so no security risk here
    if (! $isvalid && $error === ACCOUNT_WAITING_USER && $access->checkCsrf(null, null, null, null, null, 'page')) {
        if ($requestedUser != 'admin') { // admin has not necessarely an email
            if ($userlib->is_email_due($requestedUser)) {
                $userlib->send_confirm_email($requestedUser);
                $userlib->change_user_waiting($requestedUser, 'u');
                $user = '';
                $smarty->assign('user', '');
                $msg = $smarty->fetch('tiki-login_confirm_email.tpl');
                $smarty->assign('msg', explode("\n", $msg));
                $smarty->assign('mid', 'tiki-information.tpl');
                $smarty->display("tiki.tpl");
                die;
            }
        }
    } elseif ($isvalid) {
        try {
            $twoFactorAuth = TwoFactorAuthFactory::getTwoFactorAuth();
            $requireMfa = TwoFactorAuthFactory::isMFARequired($requestedUser);

            if ($prefs['twoFactorAuth'] == 'y' && isset($_REQUEST['login_mode']) && $_REQUEST['login_mode'] == 'popup') {
                $_SESSION['tiki_creds_username'] = $_REQUEST['user'];
                $_SESSION['tiki_creds_password'] = $_REQUEST['pass'];
                $params = '&create2FaCodeNormalLogin&tiki_username=' . urlencode($_REQUEST['user']);
                header('Location: ' . $base_url . 'tiki-login_scr.php?twoFactorForm' . $params);
                exit;
            }

            if ($prefs['twoFactorAuth'] == 'y' && $requireMfa && ! $twoFactorAuth->validateCode($requestedUser, $_REQUEST['twoFactorAuthCode'])) {
                $error = TWO_FA_INCORRECT;
                $isvalid = false;
                $smarty->assign('twoFactorForm', 'y');
            } else {
                if ($requireMfa) {
                    $userlib->updateLastMFADate($requestedUser);
                }
                $isdue = $userlib->is_due($requestedUser, $method);
                $user = $requestedUser;
            }
        } catch (TwoFactorAuthException $e) {
            $smarty->assign('msg', $e->getMessage());
            $smarty->display('error.tpl');
            exit;
        }
    }
}

if ($isvalid && ($isOpenIdValid || $access->checkCsrf(null, null, null, null, null, 'page'))) {
    $userlib->set_unsuccessful_logins($requestedUser, 0);
    if ($prefs['feature_invite'] == 'y') {
        // tiki-invite, this part is just here to add groups to users which just registered after received an
        // invitation via tiki-invite.php and set the redirect to wiki page if required by the invitation
        $res = $tikilib->query("SELECT `id`,`id_invite` FROM `tiki_invited` WHERE `used_on_user`=? AND used=?", [$user, "registered"]);
        $inviterow = $res->fetchRow();
        if (is_array($inviterow)) {
            $id_invited = $inviterow['id'];
            $id_invite = $inviterow['id_invite'];
            // set groups

            $groups = $tikilib->getOne("SELECT `groups` FROM `tiki_invite` WHERE `id` = ?", [(int)$id_invite]);
            $groups = explode(',', $groups);
            foreach ($groups as $group) {
                $userlib->assign_user_to_group($user, trim($group));
            }
            $tikilib->query("UPDATE `tiki_invited` SET `used`=? WHERE id_invite=?", ["logged", (int)$id_invited]);

            // set wiki page required by invitation
            if (! empty($inviterow['wikipageafter'])) {
                $_REQUEST['page'] = $inviterow['wikipageafter'];
            }
        }
    }

    if ($isdue) {
        // Redirect the user to the screen where he must change his password.
        // Note that the user is not logged in he's just validated to change his password
        // The user must re-enter his old password so no security risk involved
        $url = 'tiki-change_password.php?user=' . urlencode($user);
    } else {
        // User is valid and not due to change pass.. start session
        $userlib->update_expired_groups();
        TikiLib::lib('login')->activateSession($user);

        $url = $_SESSION['loginfrom'];

        // When logging into a multi-lingual Tiki, $_SESSION['loginfrom'] contains the main-language page, and not the translated one
        //  This only applies if feature_best_language and only seems to affect SEFURL
        if (($prefs['feature_best_language'] == 'y') && ($prefs['feature_sefurl'] == 'y')) {
            // If the URL contains the 'main' home page, remove the page name and let Tiki choose the correct home page upon reload
            $homePageUrl = urlencode($prefs['wikiHomePage']);
            if (strpos($url, 'page=' . $homePageUrl) !== false) {
                $url = str_replace('page=' . $homePageUrl, '', $url);
            } elseif (strpos($url, $homePageUrl) !== false) {
                // Strip away the page name from the URL
                $parts = parse_url($url);
                $url = '';
                if (! empty($parts['scheme'])) {
                    $url = $parts['scheme'] . '://';
                }
                if (! empty($parts['host'])) {
                    $url .= $parts['host'];
                }
                if (! empty($parts['path'])) {
                    $pathParts = explode('/', $parts['path']);
                    $cnt = count($pathParts);
                    if ($cnt > 0) {
                        $pathParts[$cnt - 1] = null;    // Drop the page name
                    }
                    $newPath .= implode('/', $pathParts);
                    $url .= $newPath;
                }
            }
        }

        $logslib->add_log('login', 'logged from ' . $url);
        // Special '?page=...' case. Accept only some values to avoid security problems
        if (isset($_REQUEST['page']) and $_REQUEST['page'] === 'tikiIndex') {
            $url = ${$_REQUEST['page']};
        } else {
            if (! empty($_REQUEST['url'])) {
                $cachelib = TikiLib::lib('cache');
                preg_match('/(.*)\?cache=(.*)/', $_REQUEST['url'], $matches);
                if (! empty($matches[2]) && $cdata = $cachelib->getCached($matches[2], 'edit')) {
                    if (! empty($matches[1])) {
                        $url = $matches[1] . '?' . $cdata;
                    }
                    $cachelib->invalidate($matches[2], 'edit');
                }
            } elseif ($prefs['useGroupHome'] == 'y') { // Go to the group page ?
                if ($prefs['limitedGoGroupHome'] == 'y') {
                    // Handle spaces (that could be written as '%20' in referer, but are generated as '+' with urlencode)
                    $url = str_replace('%20', '+', $url);
                    $url_vars = parse_url($url);
                    $url_path = $url_vars['path'];
                    if ($url_vars['query'] != '') {
                        $url_path .= '?' . $url_vars['query'];
                    }
                    // Get a valid URL for anonymous group homepage
                    // It has to be rewritten when the following two syntaxes are used :
                    //  - http:tiki-something.php => tiki-something.php
                    //  - pageName => tiki-index.php?page=pageName
                    $anonymous_homepage = $userlib->get_group_home('Anonymous');
                    if (! preg_match('#^https?://#', $anonymous_homepage)) {
                        if (substr($anonymous_homepage, 0, 5) == 'http:') {
                            $anonymous_homepage = substr($anonymous_homepage, 5);
                        } else {
                            $anonymous_homepage = 'tiki-index.php?page=' . urlencode($anonymous_homepage);
                        }
                    }
                    // Determine the complete tikiIndex URL for not logged users
                    // when tikiIndex's page has not been explicitely specified
                    //   (this only handles wiki default page for the moment)
                    if (preg_match('/tiki-index.php$/', $prefs['site_tikiIndex']) || preg_match('/tiki-index.php$/', $anonymous_homepage)) {
                        $tikiIndex_full = 'tiki-index.php?page=' . urlencode($prefs['site_wikiHomePage']);
                    } else {
                        $tikiIndex_full = '';
                    }
                }
                // Go to the group page instead of the referer url if we are in one of those cases :
                //   - pref 'Go to the group homepage only if logging in from the default homepage' (limitedGoGroupHome) is disabled,
                //   - referer url (e.g. http://example.com/tiki/tiki-index.php?page=Homepage ) is the homepage (tikiIndex),
                //   - referer url complete path ( e.g. /tiki/tiki-index.php?page=Homepage ) is the homepage,
                //   - referer url relative path ( e.g. tiki-index.php?page=Homepage ) is the homepage
                //   - referer url SEF page ( e.g. /tiki/Homepage ) is the homepage
                //   - one of the three cases listed above, but compared to anonymous page instead of global homepage
                //   - first login after registration
                //   - last case ($tikiIndex_full != '') :
                //       wiki homepage could have been saved as 'tiki-index.php' instead of 'tiki-index.php?page=Homepage'.
                //       ... so we also need to check against : homepage + '?page=' + default wiki pagename
                //
                include_once('tiki-sefurl.php');
                if (
                    $url == '' || preg_match('/(tiki-register|tiki-login_validate|tiki-login_scr)\.php/', $url) || $prefs['limitedGoGroupHome'] == 'n'
                    || $url == $prefs['site_tikiIndex'] || $url_path == $prefs['site_tikiIndex'] || basename($url_path) == $prefs['site_tikiIndex']
                    || ($anonymous_homepage != '' && ($url == $anonymous_homepage || $url_path == $anonymous_homepage || basename($url_path) == $anonymous_homepage))
                    || filter_out_sefurl($anonymous_homepage) == basename($url_path)
                    || ($tikiIndex_full != '' && ( basename($url_path) == $tikiIndex_full || basename($url_path) == filter_out_sefurl($tikiIndex_full) ))
                ) {
                    $groupHome = $userlib->get_user_default_homepage($user);
                    if ($groupHome != '') {
                        $url = (preg_match('/^(\/|https?:)/', $groupHome)) ? $groupHome : filter_out_sefurl('tiki-index.php?page=' . urlencode($groupHome));
                    }
                }
            }
            // Unset session variable in case user su's
            unset($_SESSION['loginfrom']);
            // No sense in sending user to registration page or no page at all
            // This happens if the user has just registered and it's first login
            if ($url == '' || preg_match('/(tiki-register|tiki-login_validate|tiki-login_scr)\.php/', $url)) {
                $url = $prefs['tikiIndex'];
            }
            // Now if the remember me feature is on and the user checked the rememberme checkbox then ...
            if ($prefs['rememberme'] == 'always' || $prefs['rememberme'] != 'disabled' && isset($_REQUEST['rme']) && $_REQUEST['rme'] == 'on') {
                $userInfo = $userlib->get_user_info($user);
                $userId = $userInfo['userId'];
                $secret = $userlib->create_user_cookie($userId);
                setcookie($user_cookie_site, $secret . '.' . $userId, $tikilib->now + $prefs['remembertime'], $prefs['feature_intertiki_sharedcookie'] == 'y' ? '/' : $prefs['cookie_path'], $prefs['cookie_domain']);
                $logslib->add_log('login', 'got a cookie for ' . $prefs['remembertime'] . ' seconds');
            }
        }
    }
} else {    // if ($isvalid) = false
    // check if site is closed
    if ($prefs['site_closed'] === 'y') {
        unset($bypass_siteclose_check);
        if (isset($_REQUEST['ticket'])) {
            unset($_SESSION['tickets'][$_REQUEST['ticket']]);
        }
        switch ($error) {
            case PASSWORD_INCORRECT:
                http_response_code(401);
                $error = tra('Invalid username or password');
                break;
            case TWO_FA_INCORRECT:
                http_response_code(401);
                $error = tra('Invalid two-factor code ');
                break;
            case USER_NOT_FOUND:
                http_response_code(401);
                $error = tra('Invalid username or password');
                break;
            case ACCOUNT_DISABLED:
                http_response_code(403);
                $error = tra('Account requires administrator approval.');
                break;
            case ACCOUNT_WAITING_USER:
                http_response_code(403);
                $error = tra('You did not validate your account.');
                break;
            case USER_AMBIGOUS:
                http_response_code(400);
                $error = tra('You must use the right case for your username.');
                break;
            case USER_NOT_VALIDATED:
                http_response_code(403);
                $error = tra('You are not yet validated.');
                break;
            case USER_ALREADY_LOGGED:
                http_response_code(400);
                $error = tra('You are already logged in.');
                break;
            case EMAIL_AMBIGUOUS:
                http_response_code(400);
                $error = tra("There is more than one user account with this email. Please contact the administrator.");
                break;
            default:
                http_response_code(401);
                $error = tra('Authentication error');
        }
        $error_login = $error;
        include 'lib/setup/site_closed.php';
    }

    if (isset($_REQUEST['url'])) {
        $smarty->assign('url', $_REQUEST['url']);
    }
    $module_params['show_forgot'] = ($prefs['forgotPass'] == 'y' && $prefs['change_password'] == 'y') ? 'y' : 'n';
    $module_params['show_register'] = ($prefs['allowRegister'] === 'y') ? 'y' : 'n';
    $smarty->assign('module_params', $module_params);
    if (
        ($error == PASSWORD_INCORRECT || $error == TWO_FA_INCORRECT)
        && ($prefs['unsuccessful_logins'] >= 0 || $prefs['unsuccessful_logins_invalid'] >= 0)
    ) {
        $nb_bad_logins = $userlib->unsuccessful_logins($requestedUser);
        if ($prefs['unsuccessful_logins_invalid'] > 0 && ($nb_bad_logins >= $prefs['unsuccessful_logins_invalid'])) {
            $smarty->assign('mid', 'tiki-information.tpl');
            $smarty->display('tiki.tpl');
            die;
        } elseif ($prefs['unsuccessful_logins'] > 0 && ($nb_bad_logins >= $prefs['unsuccessful_logins'])) {
            $show_history_back_link = 'y';
            $smarty->assign_by_ref('show_history_back_link', $show_history_back_link);
            $smarty->assign('mid', 'tiki-information.tpl');
            $smarty->display("tiki.tpl");
            die;
        }
    }
    switch ($error) {
        case PASSWORD_INCORRECT:
            http_response_code(401);
            $error = tra('Invalid username or password');
            break;
        case TWO_FA_INCORRECT:
            http_response_code(401);
            $error = tra('Invalid two-factor code');
            break;
        case USER_NOT_FOUND:
            http_response_code(401);
            $smarty->assign('error_login', $error);
            $smarty->assign('mid', 'tiki-login.tpl');
            $smarty->assign('error_user', $_REQUEST["user"]);
            $smarty->display('tiki.tpl');
            exit;

        case ACCOUNT_DISABLED:
            http_response_code(403);
            $error = tra('Account requires administrator approval.');
            break;

        case ACCOUNT_WAITING_USER:
            http_response_code(403);
            $error = tra('You did not validate your account.');
            $extraButton = ['href' => 'tiki-send_mail.php?user=' . urlencode($_REQUEST['user']), 'text' => tra('Resend'), 'comment' => tra('You should have received an email. Check your mailbox and your spam box. Otherwise click on the button to resend the email')];
            break;

        case USER_AMBIGOUS:
            http_response_code(400);
            $error = tra('You must use the right case for your username.');
            break;

        case USER_NOT_VALIDATED:
            http_response_code(403);
            $error = tra('You are not yet validated.');
            break;

        case USER_ALREADY_LOGGED:
            http_response_code(400);
            $error = tra('You are already logged in.');
            break;

        case EMAIL_AMBIGUOUS:
            http_response_code(400);
            $error = tra("There is more than one user account with this email. Please contact the administrator.");
            break;

        case ACCOUNT_LOCKED:
            http_response_code(403);
            $error = tra("Account is locked. Please contact the administrator.");
            break;

        default:
            http_response_code(401);
            $error = tra('Authentication error');
    }
    if (isset($extraButton)) {
        $smarty->assign_by_ref('extraButton', $extraButton);
    }

    //  Report error "inline" with the login module
    $smarty->assign('error_login', $error);
    $smarty->assign('mid', 'tiki-login.tpl');
    $smarty->display('tiki.tpl');
    exit;
}
if (isset($user) && ($isOpenIdValid || $access->checkCsrf(null, null, null, null, null, 'page'))) {
    TikiLib::events()->trigger(
        'tiki.user.login',
        [
            'type' => 'user',
            'object' => $user,
            'user' => $user,
        ]
    );
}

// RFC 2616 defines that the 'Location' HTTP headerconsists of an absolute URI
if (! preg_match('/^https?\:/i', $url)) {
    $url = (preg_match('/^\//', $url) ? $url_scheme . '://' . $url_host . (($url_port != '') ? ":$url_port" : '') : $base_url) . $url;
}
// Force HTTP mode if needed
if ($stay_in_ssl_mode != 'y' || ! $https_mode) {
    $url = str_replace('https://', 'http://', $url);
}

/*
 * If user logged in with HTTPS after requesting a non-TLS URL but "stay"ing in TLS mode was requested, redirect to HTTPS equivalent of the original URL requested.
 * Not sure why we don't just use the initial URI requested. Chealer 2017-04-19
 */
if ($stay_in_ssl_mode == 'y' && $https_mode) {
    $url = str_replace('http://', 'https://', $url);
}

if (defined('SID') && SID != '') {
    $url .= ((strpos($url, '?') === false) ? '?' : '&') . SID;
}

// Check if a wizard should be run.
// If a wizard is run, it will return to the $url location when it has completed. Thus no code after $wizardlib->onLogin will be executed
// The user must be actually logged in before onLogin is called. If $isdue is set, then: "Note that the user is not logged in he's just validated to change his password"
if (! $isdue && ($isOpenIdValid || $access->checkCsrf(null, null, null, null, null, 'page'))) {
    if ($prefs['feature_user_encryption'] === 'y') {
        // Notify CryptLib about the login
        $cryptlib = TikiLib::lib('crypt');
        $cryptlib->onUserLogin($pass);
    }

    // Process wizard
    $wizardlib = TikiLib::lib('wizard');
    $wizardlib->onLogin($user, $url);
}

header('Location: ' . $url);
exit;
