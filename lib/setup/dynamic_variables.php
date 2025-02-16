<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

// Check for an update of dynamic vars
if (isset($tiki_p_edit_dynvar) && $tiki_p_edit_dynvar == 'y') {
    if (isset($_REQUEST['_dyn_update'])) {
        global $prefs;

        if ($prefs['feature_multilingual'] == 'y' && $prefs['wiki_dynvar_multilingual'] == 'y' && isset($_REQUEST['page'])) {
            $lang = $tikilib->getOne('SELECT `lang` FROM `tiki_pages` WHERE `pageName` = ?', [ $_REQUEST['page'] ]);
        } else {
            $lang = null;
        }

        foreach ($_REQUEST as $name => $value) {
            if (substr($name, 0, 4) == 'dyn_' and $name != '_dyn_update') {
                $tikilib->update_dynamic_variable(substr($name, 4), $_REQUEST[$name], $lang);
            }
        }
    }
}
