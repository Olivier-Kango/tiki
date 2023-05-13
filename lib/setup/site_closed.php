<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

// Check to see if admin has closed the site
if ($tiki_p_access_closed_site !== 'y' && ! isset($bypass_siteclose_check)) {
    if ($user && $tiki_p_access_closed_site !== 'y') {
        $error_login = tr('You do not have permission to log in when the site is closed.');
        TikiLib::lib('user')->user_logout($user);
    }
    TikiLib::lib('access')->showSiteClosed('closed');
}
