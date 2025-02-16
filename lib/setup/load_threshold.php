<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

// get average server load in the last minute
if (function_exists('sys_getloadavg')) {
    $load = sys_getloadavg();
    $server_load = $load[0];

    if ($prefs['use_load_threshold'] == 'y' && $tiki_p_access_closed_site != 'y' && ! isset($bypass_siteclose_check)) {
        if ($server_load > $prefs['load_threshold']) {
            TikiLib::lib('access')->showSiteClosed('busy');
        }
    } else {
        $smarty->assign('server_load', $server_load == 0 ? '?' : $server_load);
    }
}
