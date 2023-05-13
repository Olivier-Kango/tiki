<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_notificationlink_info()
{
    return [
        'name' => tra('Notifications Link'),
        'description' => tra('Shows an icon with the number of and a link to user notifications'),
        'prefs' => ['monitor_enabled'],
        'params' => [],
    ];
}
