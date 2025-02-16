<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_change_list()
{
    return [
        'change_language' => [
            'name' => tra('Users can choose the language of their interface'),
            'description' => tra('Allow users to change the language of the menus and labels.'),
            'type' => 'flag',
            'dependencies' => [
                'feature_multilingual',
                'feature_userPreferences',
            ],
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'change_theme' => [
            'name' => tra('Users can change theme'),
            'warning' => tra('Users can override the theme with this setting.'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'change_password' => [
            'name' => tra('Users can change their password'),
            'description' => tra('Registered users can change their password from their User Preferences page. If not, passwords can be changed only by the admin.'),
            'type' => 'flag',
            'help' => 'User-Preferences',
            'default' => 'y',
            'tags' => ['basic'],
        ],
        'change_month' => [
            'name' => tra('Select month in Date Picker'),
            'description' => tra('Users can select month in the Date Picker instead of seeing month as text.'),
            'help' => 'https://api.jqueryui.com/datepicker/#option-changeMonth',
            'type' => 'list',
            'options' => [
                'n' => tra('No'),
                'y' => tra('Yes'),
            ],
            'default' => 'n',
            'tags' => ['basic']
        ],
        'change_year' => [
            'name' => tra('Select year in Date Picker'),
            'description' => tra('Users can select year in the Date Picker instead of seeing year as text.'),
            'help' => 'https://api.jqueryui.com/datepicker/#option-changeYear',
            'type' => 'list',
            'options' => [
                'n' => tra('No'),
                'y' => tra('Yes'),
            ],
            'default' => 'n',
            'tags' => ['basic']
        ],
    ];
}
