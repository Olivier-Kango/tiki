<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

$smarty->assign('user_is_operator', 'n');
if ($user) {
    include_once('lib/live_support/lsadminlib.php');
    if ($lsadminlib->is_operator($user)) {
        $smarty->assign('user_is_operator', 'y');
    }
}
