<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Tracker_Field_TikiManager extends Tracker_Field_Abstract
{
    public static function getTypes()
    {
        return [
            'TM' => [
                'name'        => tr('Tiki Manager'),
                'description' => tr('Integrate Tiki Manager commands in tracker items.'),
                'prefs'       => ['trackerfield_tikimanager'],
                'tags'        => ['experimental'],
                'help'        => 'Manager',
                'default'     => 'n',
                'params'      => [
                    'instanceIds' => [
                        'name' => tra('Instance IDs'),
                        'description' => tra('Comma-separted list of instance IDs available to manage. For a full list, use Tiki Manager admin page.'),
                        'default' => '',
                        'filter' => 'text',
                        'separator' => ',',
                    ],
                    'showactions' => [
                        'name' => tra('Show actions'),
                        'description' => tra('Comma-separted list of actions shown in the interface. If none are listed, all actions will be available by default.'),
                        'default' => '',
                        'filter' => 'text',
                        'separator' => ',',
                    ],
                    'hideactions' => [
                        'name' => tra('Hide actions'),
                        'description' => tra('Comma-separted list of actions hidden from the interface. If none are listed, all actions will be available by default.'),
                        'default' => '',
                        'filter' => 'text',
                        'separator' => ',',
                    ],
                    'newInstanceType' => [
                        'name' => tra('New Instance Type'),
                        'description' => tra('Type of instance when creating a new one.'),
                        'default' => '',
                        'filter' => 'alpha',
                        'options'      => [
                            'ftp' => tr('FTP'),
                            'local' => tr('Local'),
                            'ssh' => tr('SSH'),
                        ],
                    ],
                    'newInstanceHost' => [
                        'name' => tra('New Instance Host'),
                        'description' => tra('SSH or FTP host'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstancePort' => [
                        'name' => tra('New Instance Port'),
                        'description' => tra('SSH or FTP host, e.g. 22 or 21.'),
                        'default' => '',
                        'filter' => 'int',
                    ],
                    'newInstanceUser' => [
                        'name' => tra('New Instance User'),
                        'description' => tra('SSH or FTP user'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstancePass' => [
                        'name' => tra('New Instance Pass'),
                        'description' => tra('FTP password'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceTemplateUrl' => [
                        'name' => tra('New Instance Template Url'),
                        'description' => tra('This will be used as a template for created instances web url. {slug} string will be replaced with the instance unique identifier. Possible formats: https://{slug}.domain.com or https://domain.com/{slug}/'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceWebroot' => [
                        'name' => tra('New Instance Web Root'),
                        'description' => tra('Local filesystem path to the instance webroot. {slug} string will be replaced with the instance unique identifier. E.g. /var/www/virtual/{slug}.domain.com/html/'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceTempdir' => [
                        'name' => tra('New Instance Work Dir'),
                        'description' => tra('Local filesystem path to the instance temp directory. {slug} string will be replaced with the instance unique identifier. E.g. /tmp/tiki_mgr_{slug}'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceBackupUser' => [
                        'name' => tra('New Instance Backup User'),
                        'description' => tra('Owner of the backup files for this instance.'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceBackupGroup' => [
                        'name' => tra('New Instance Backup Group'),
                        'description' => tra('Group owner of the backup files for this instance.'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceBackupPerms' => [
                        'name' => tra('New Instance Backup Perms'),
                        'description' => tra('Backup files permission mask. E.g. 755'),
                        'default' => '',
                        'filter' => 'int',
                    ],
                    'newInstanceDbHost' => [
                        'name' => tra('New Instance DB Host'),
                        'description' => tra('Database host'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceDbUser' => [
                        'name' => tra('New Instance DB User'),
                        'description' => tra('Database user with permissions to create other users and databases'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceDbPass' => [
                        'name' => tra('New Instance DB Pass'),
                        'description' => tra('Password for database user'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceDbPrefix' => [
                        'name' => tra('New Instance DB Prefix'),
                        'description' => tra('Newly created databases and users will be prefixed by this string.'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        global $prefs;

        $ret = [];

        if ($prefs['feature_tiki_manager'] !== 'y') {
            $ret['error'] = tra('Tiki Manager feature not enabled.');
            return $ret;
        }

        if (! class_exists('TikiManager\Config\Environment')) {
            $ret['error'] = tr('Tiki Manager not found. Please check if it is installed from Admin->Packages.');
            return $ret;
        }

        try {
            $utilities = new Services_Manager_Utilities;
            $utilities->loadEnv();
        } catch (Exception $e) {
            $ret['error'] = $e->getMessage();
            return $ret;
        }

        $manager_output = $utilities->getManagerOutput();

        $instanceIds = array_filter($this->getOption('instanceIds', []));
        $showactions = array_filter($this->getOption('showactions', []));
        $hideactions = array_filter($this->getOption('hideactions', []));
        $privateName = 'Item ' . $this->getItemId() . ' Field ' . $this->getFieldId() . ' Instance';

        $instances = TikiManager\Application\Instance::getInstances(false);
        $instances = array_filter($instances, function($i) use ($instanceIds, $privateName) {
            return empty($instanceIds) || in_array($i->getId(), $instanceIds) || $i->name == $privateName;
        });

        // taken from Tiki manager available commands, TODO: hook these up with the interface
        $available_actions = ['access', 'backup', 'blank', 'check', 'clone', 'cloneandupgrade', 'console', 'copysshkey', 'create', 'delete', 'detect', 'edit', 'fixpermissions', 'import', 'list', 'maintenance', 'patch_apply', 'patch_delete', 'patch_list', 'profile_apply', 'restore', 'revert', 'setup-scheduler-cron', 'stats', 'update', 'upgrade', 'watch', 'info'];
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

        // TODO: once we figure out how to get the available versions based on field instance config without creating an instance
        // (e.g. use $instance->getCompatibleVersions without saving the instance), we can replace these hard-coded versions
        $versions = ['22.x', '23.x', '24.x', 'master'];

        $ret = [
            'id' => $this->getItemId(),
            'error' => '',
            'instances' => $instances,
            'available_actions' => $available_actions,
            'manager_output' => $manager_output->fetch(),
            'versions' => $versions,
            'has_created_one' => array_filter($instances, function($i) use ($privateName) { return $i->name == $privateName; }),
            'value' => 'none', // this is required to show the field, otherwise it gets hidden if tracker is set to doNotShowEmptyField
        ];

        return $ret;
    }

    public function renderInput($context = [])
    {
        TikiLib::lib('header')->add_cssfile('themes/base_files/feature_css/tiki-manager.css');
        return $this->renderTemplate('trackerinput/tikimanager.tpl', $context);
    }

    public function renderOutput($context = [])
    {
        TikiLib::lib('header')->add_cssfile('themes/base_files/feature_css/tiki-manager.css');
        return $this->renderTemplate('trackerinput/tikimanager.tpl', $context);
    }
}
