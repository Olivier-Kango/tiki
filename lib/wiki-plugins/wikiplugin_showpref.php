<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_showpref_info()
{
    return [
        'name' => tra('Show Preference'),
        'documentation' => 'PluginShowpref',
        'description' => tra('Display the value of public global preferences'),
        'prefs' => ['wikiplugin_showpref'],
        'filter' => 'wikicontent',
        'iconname' => 'cog',
        'introduced' => 13,
        'params' => [
            'pref' => [
                'required' => true,
                'name' => tra('Preference Name'),
                'description' => tra('Name of preference to be displayed.'),
                'since' => '13.0',
                'filter' => 'text',
            ],
        ],
    ];
}

function wikiplugin_showpref($data, $params)
{
    global $prefs;
    $tikilib = TikiLib::lib('tiki');
    global $tikipath;

    $name = $params['pref'];
    $file = 'global';
    $lib_path = 'lib/prefs';
    $extension_path = '';
    if (substr($name, 0, 3) == 'tp_') {
        $midpos = strpos($name, '_', 3);
        if ($midpos) {
            $paths = \Tiki\Package\ExtensionManager::getPaths();
            $package = str_replace('__', '/', substr($name, 3));
            if (isset($paths[$package])) {
                $lib_path = $paths[$package] . '/prefs';
                $file = $name;
            }
        }
    } elseif (false !== $pos = strpos($name, '_')) {
        $file = substr($name, 0, $pos);
    }

    $inc_file = "{$lib_path}/{$file}.php";
    $preffile = [];
    $realpath = realpath(dirname($inc_file));
    if (file_exists($inc_file) && $realpath == $tikipath . $lib_path) {
        require_once $inc_file;
        $function = "prefs_{$file}_list";
        if (function_exists($function)) {
            $preffile = $function();
        }
    }

    // Security public prefs only, you would not want all prefs to be displayed via wiki syntax

    if (isset($preffile[$name]['public']) && $preffile[$name]['public']) {
        return $tikilib->get_preference($name);
    } else {
        return '';
    }
}
