<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\TikiInit;

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}
require_once('tiki-setup.php');

if (isset($_POST['site_mautic_url']) && $access->checkCsrf()) {
    simple_set_value('site_mautic_url');
}

if (isset($_POST['site_mautic_username']) && $access->checkCsrf()) {
    simple_set_value('site_mautic_username');
}

if (isset($_POST['site_mautic_password']) && $access->checkCsrf()) {
    simple_set_value('site_mautic_password');
}
