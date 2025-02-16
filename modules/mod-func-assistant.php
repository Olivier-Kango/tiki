<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @return array
 */
function module_assistant_info()
{
    return [
        'name' => tra('Tiki Assistant'),
        'description' => tra('Display an assistant to guide new Tiki admins.'),
        'prefs' => [],
        'params' => []
    ];
}
