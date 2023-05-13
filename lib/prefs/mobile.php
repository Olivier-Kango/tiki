<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_mobile_list()
{

    return [
        'mobile_perspectives' => [
            'name' => tra('Mobile Perspectives'),
            'help' => 'Mobile',
            'type' => 'text',
            'separator' => ',',
            'filter' => 'int',
            'tags' => ['experimental'],
            'default' => [],
            'profile_reference' => 'perspective',
        ],
    ];
}
