<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_tikimanager_list()
{
    return [
        'tikimanager_storage_path' => [
            'name' => tra('Storage path'),
            'description' => tra('The path where Tiki Manager will store internal data, backups, logs. It is recommended to be outside document root.'),
            'help' => 'Manager',
            'hint' => tr('Default: storage/tiki-manager'),
            'dependencies' => [
                'feature_tiki_manager',
            ],
            'tags' => ['advanced'],
            'type' => 'text',
            'default' => '',
        ],
    ];
}
