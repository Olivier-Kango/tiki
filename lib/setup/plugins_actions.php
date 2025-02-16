<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

// Handle actions of plugins (smarty plugins, wiki-plugins, modules, ...)

$plugins_actions = [];
$matches = [];

foreach ($_REQUEST as $k => $v) {
    if (preg_match('/^(\w_\w_)([a-zA-Z0-9_-]+)-(.*)$/', $k, $matches)) {
        $plugin_type =& $matches[1];
        $plugin_name =& $matches[2];
        $plugin_argument =& $matches[3];
        if (! isset($plugins_actions[$plugin_type])) {
            $plugins_actions[$plugin_type] = [];
        }
        if (! isset($plugins_actions[$plugin_type][$plugin_name])) {
            $plugins_actions[$plugin_type][$plugin_name] = [];
        }
        $plugins_actions[$plugin_type][$plugin_name][$plugin_argument] =& $_REQUEST[$k];
    }
}

foreach ($plugins_actions as $plugin_type => $v) {
    foreach ($v as $plugin_name => $params) {
        switch ($plugin_type) {
            case 's_f_': // Smarty Function
                @include_once('lib/smarty_tiki/FunctionHandler/' . ucfirst($plugin_name) . '.php');
                $func = '\\SmartyTiki\\FunctionHandler\\s_f_' . $plugin_name . '_actionshandler';
                if (! function_exists($func) || ! call_user_func($func, $params)) {
                    TikiLib::lib('access')->display_error('', sprintf(tra('Handling actions of plugin "%s" failed.'), $plugin_name));
                }
                break;
        }
    }
}

unset($matches);
unset($plugins_actions);
