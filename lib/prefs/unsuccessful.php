<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_unsuccessful_list()
{
    return [
        'unsuccessful_logins' => [
            'name' => tra('Re-validate user by email after'),
            'type' => 'text',
            'size' => 5,
            'filter' => 'int',
            'units' => tra('unsuccessful login attempts'),
            'hint' => tra('Use "-1" for never'),
            'description' => tra('After a certain number of consecutive unsuccessful log-in attempts, the user will receive an email with instruction to validate his or her account. However, the user can still log in with the old password.'),
            'default' => 20,
            'keywords' => tra('brute force, brute-force, login failure, login-failure, failed logins'),
        ],
        'unsuccessful_logins_invalid' => [
            'name' => tra('Suspend/lockout account after'),
            'type' => 'text',
            'size' => 5,
            'filter' => 'int',
            'description' => tra('After a certain number of consecutive unsuccessful login attempts, the account is suspended. An admin must revalidate the account before the user can use it again.'),
            'units' => tra('unsuccessful login attempts'),
            'hint' => tra('Use "-1" for never'),
            'default' => 50,
            'keywords' => tra('brute force, brute-force, login failure, login-failure, failed logins'),
        ],
    ];
}
