<?php

/**
 * Tiki's Installation script.
 *
 * Used to install a fresh Tiki instance, to upgrade an existing Tiki to a newer version and to test sendmail.
 *
 * @package TikiWiki
 * @copyright (c) Copyright by authors of the Tiki Wiki CMS Groupware Project. All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */

// $Id$

use Tiki\TikiInit;

$in_installer = 1;
define('TIKI_IN_INSTALLER', 1);
if (! isset($title)) {
    $title = 'Tiki Installer';
}
if (! isset($dberror)) {
    $dberror = false;
}

// Show all errors
error_reporting(-1);
ini_set('display_errors', 1);

require_once('lib/init/initlib.php');
$tikipath = __DIR__ . '/';
TikiInit::appendIncludePath($tikipath);
define('TIKI_PATH', $tikipath);

require_once('db/tiki-db.php'); // to set up multitiki etc if there

$lockFile = 'db/' . $tikidomainslash . 'lock';
$authAttemptsFile = 'db/' . $tikidomainslash . 'installer_auth_attempts';


if (! isset($content)) {
    $content = tr('No content specified. Something went wrong.<br/>Please tell your administrator.<br/>If you are the administrator, you may want to check for / file a bug report.');
}

// Check that PHP version is sufficient or if the PHP version is too recent, i.e. higher than the required version.
if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '<') || version_compare(PHP_VERSION, TOO_RECENT_PHP_VERSION, '>=')) {
    $title = 'PHP ' . MIN_PHP_VERSION . ' is required';
    $content = '<p>' . tr("Please contact your system administrator ( if you are not the one ;) ). Your version: ") . PHP_VERSION . ' <br /> <br /> ' . '</p>';
    createPage($title, $content);
}

// if tiki installer is locked (probably after previous installation) display notice
if (file_exists($lockFile)) {
    $title = 'Tiki Installer Disabled';
    $td = empty($tikidomain) ? '' : '/' . $tikidomain;
    $content = '
                            <p class="under-text">' . tr("As a security precaution, the Tiki Installer has been disabled. To re-enable the installer:") . '</p>
                                <ol class="installer-ordered-list-style">
                                    <li class="installer-ordered-list"><p>' . tr('Use your file manager application to find the directory where you have unpacked your Tiki and remove the <span class="text-danger font-weight-bold">lock</span> file which was created in the <span class="text-danger font-weight-bold">db</span> folder') . '.</p></li>
                                    <li class="installer-ordered-list"><p>' . tr('Re-run') . ' <strong ><a class="text-yellow-inst" href="tiki-install.php' . (empty($tikidomain) ? '' : "?multi=$tikidomain") . '" title="Tiki Installer">tiki-install.php' . (empty($tikidomain) ? '' : "?multi=$tikidomain") . '</a></strong>.</p></li>
                                </ol>
                            ';
    createPage($title, $content);
}

if (! empty($db) && ! $db->getOne("SELECT COUNT(*) FROM `information_schema`.`character_sets` WHERE `character_set_name` = 'utf8mb4';")) {
    die(tr('Your database does not support the utf8mb4 character set required in Tiki19 and above. You need to upgrade your mysql or mariadb installation.'));
}

$tikiroot = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
$session_params = session_get_cookie_params();
session_set_cookie_params($session_params['lifetime'], $tikiroot);
unset($session_params);
session_start();

$rootcheck = empty($tikiroot) || $tikiroot === '/' ? '' : $tikiroot;
$refered = isset($_SERVER['HTTP_REFERER']) ? strpos($_SERVER['HTTP_REFERER'], $rootcheck . '/tiki-install.php') : false;
if (! $refered || ($refered && ! isset($_POST['install_step']))) {
    unset($_SESSION['accessible']);
}
// Were database details defined before? If so, load them
if (file_exists('db/' . $tikidomainslash . 'local.php')) {
    include 'db/' . $tikidomainslash . 'local.php';

    // In case of replication, ignore it during installer.
    unset($shadow_dbs, $shadow_user, $shadow_pass, $shadow_host);

    // check for provided login details and check against the old, saved details that they're correct
    if (isset($_POST['dbuser'], $_POST['dbpass'])) {
        if (($_POST['dbuser'] == $user_tiki) && ($_POST['dbpass'] == $pass_tiki)) {
            $_SESSION['accessible'] = true;
            unset($_POST['dbuser']);
            unset($_POST['dbpass']);

            if (file_exists($authAttemptsFile)) {
                unlink($authAttemptsFile);
            }
        } else {
            $attempts = (int) @file_get_contents($authAttemptsFile);

            if (++$attempts >= 10) {
                touch($lockFile);
                unlink($authAttemptsFile);
            } else {
                file_put_contents($authAttemptsFile, $attempts);
            }
        }
    }
} else {
    // No database info found, so it's a first-install and thus installer is accessible
    $_SESSION['accessible'] = true;
}

if (isset($_SESSION['accessible'])) {
    // allowed to access installer, include it
    $logged = true;
    $admin_acc = 'y';
    include_once 'installer/tiki-installer.php';
} else {
    // Installer knows db details but no login details were received for this script.
    // Thus, display a form.
    $title = tr('Tiki Installer Security Precaution');
    $content = '
                            <p class="text-info mt-lg-3 mx-3">' . tr('You are attempting to run the Tiki Installer. For your protection, this installer can be used only by a site administrator.To verify that you are a site administrator, enter your <strong><em>database</em></strong> credentials (database username and password) here.') . '</p>

                            <p class="text-info mx-3">' . tr('If you have forgotten your database credentials, find the directory where you have unpacked your Tiki and have a look inside the <strong class="text-yellow-inst">db</strong> folder into the <strong class="text-yellow-inst">local.php</strong> file.') . '</p>
                            <form method="post" action="tiki-install.php" class="text-center">
                                <input type="hidden" name="enterinstall" value="1">
                                <p><label for="dbuser" class="sr-only">' . tr("Database username") . '</label> <input type="text" id="dbuser" name="dbuser" class="col-6 offset-3 form-control text-center" placeholder="' . tr('Database username') . '"/></p>
                                <p><label for="dbpass" class="sr-only">' . tr("Database password") . '</label> <input type="password" id="dbpass" name="dbpass" class="col-6 offset-3 form-control text-center" placeholder="' . tr('Database password') . '"/></p>
                                <p><input type="submit" class="btn btn-primary" value=" ' . tr("Validate and Continue ") . '" /></p>
                            </form>
                            <p>&nbsp;</p>';
    createPage($title, $content);
}
