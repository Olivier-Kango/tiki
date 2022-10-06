<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_tikimanager_info()
{
    return [
        'name' => tra('Tiki Manager'),
        'documentation' => 'PluginTikiManager',
        'description' => tra('Embed partial or full Tiki Manager instance management functionality in a wiki page.'),
        'prefs' => [ 'feature_tiki_manager', 'wikiplugin_tikimanager' ],
        'iconname' => 'tikimanager',
        'introduced' => 25,
        'format' => 'html',
        'params' => [
            'instanceIds' => [
                'required' => false,
                'name' => tra('Instance IDs'),
                'description' => tra('Comma-separted list of instance IDs available to manage. For a full list, use Tiki Manager admin page.'),
                'since' => '25.0',
                'default' => '',
                'separator' => ',',
            ],
            'showactions' => [
                'required' => false,
                'name' => tra('Show actions'),
                'description' => tra('Comma-separted list of actions shown in the interface. If none are listed, all actions will be available by default.'),
                'since' => '25.0',
                'default' => '',
                'separator' => ',',
            ],
            'hideactions' => [
                'required' => false,
                'name' => tra('Hide actions'),
                'description' => tra('Comma-separted list of actions hidden from the interface. If none are listed, all actions will be available by default.'),
                'since' => '25.0',
                'default' => '',
                'separator' => ',',
            ],
        ]
    ];
}

function wikiplugin_tikimanager($data, $params)
{
    global $prefs;

    static $id = 0;
    $id++;

    if ($prefs['feature_tiki_manager'] !== 'y') {
        return WikiParser_PluginOutput::error(tra('Error'), tra('Tiki Manager feature not enabled.'));
    }

    if (! class_exists('TikiManager\Config\Environment')) {
        return WikiParser_PluginOutput::error(tr('Tiki Manager not found. Please check if it is installed from Admin->Packages.'));
    }

    try {
        $utilities = new Services_Manager_Utilities;
        $utilities->loadEnv();
    } catch (Exception $e) {
        return WikiParser_PluginOutput::error($e->getMessage());
    }

    $manager_output = $utilities->getManagerOutput();

    extract($params, EXTR_SKIP);

    $instanceIds ??= [];
    $showactions ??= [];
    $hideactions ??= [];

    $instances = TikiManager\Application\Instance::getInstances(true);
    $instances = array_filter($instances, function($i) use ($instanceIds) {
        return empty($instanceIds) || in_array($i->getId(), $instanceIds);
    });

    // taken from Tiki manager available commands, TODO: hook these up with the interface
    $available_actions = ['access', 'backup', 'blank', 'check', 'clone', 'cloneandupgrade', 'console', 'copysshkey', 'create', 'delete', 'detect', 'edit', 'fixpermissions', 'import', 'list', 'maintenance', 'patch_apply', 'patch_delete', 'patch_list', 'profile_apply', 'restore', 'revert', 'setup-scheduler-cron', 'stats', 'update', 'upgrade', 'watch', 'info', 'tiki_versions', 'test_send_email', 'setup_watch', 'checkout', 'clear_cache','setup_backup','manager_backup','manager_update'];

    if ($showactions) {
        $available_actions = array_filter($available_actions, function($action) use ($showactions) {
            return in_array($action, $showactions);
        });
    }
    if ($hideactions) {
        $available_actions = array_filter($available_actions, function($action) use ($hideactions) {
            return ! in_array($action, $hideactions);
        });
    }

    $smarty = TikiLib::lib('smarty');
    $smarty->assign('id', $id);
    $smarty->assign('instances', $instances);
    $smarty->assign('available_actions', $available_actions);
    $smarty->assign('manager_output', $manager_output->fetch());
    return $smarty->fetch('wiki-plugins/wikiplugin_tikimanager.tpl');
}
