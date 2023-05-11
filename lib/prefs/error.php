<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_error_list()
{
    return [
        'error_reporting_adminonly'  => [
            'name'        => tra('PHP errors visible to admin only'),
            'hint'        => tra(
                'During site development, it\'s better to display errors to all users. However, in production settings, errors should only be displayed to administrators.'
            ),
            'type'        => 'flag',
            'description' => tr('PHP Errors will be shown to only the Admin user.'),
            'default'     => 'y',
        ],
        'error_reporting_level'      => [
            'name'        => tra('PHP Error reporting level'),
            'description' => tra('Level of errors to be reported. Errors can be seen in a collapsible box at the bottom of the page, if any exist.'),
            'type'        => 'list',
            'options'     => [
                0    => tra('No error reporting'),
                2047 => tra('Report all PHP errors except strict'),
                -1   => tra('Report all PHP errors'),
                2039 => tra('Report all PHP errors except notices'),
                1    => tra('According to the PHP configuration'),
            ],
            'default'     => 2039,    //    E_ALL & ~E_NOTICE
        ],
        'error_tracking_enabled_php' => [
            'name'        => tra('Track PHP errors'),
            'description' => tra('Enable integration with error tracking service(ex: Sentry, GlitchTip) for PHP.'),
            'type'        => 'flag',
            'default'     => 'n',
            'dependencies' => [
                'error_tracking_dsn'
            ],
        ],
        'error_tracking_enabled_js'  => [
            'name'        => tra('Track JavaScript errors'),
            'description' => tra('Enable integration with error tracking service(ex: Sentry, GlitchTip) for JavaScript.'),
            'type'        => 'flag',
            'default'     => 'n',
            'dependencies' => [
                'error_tracking_dsn'
            ],
        ],
        'error_tracking_dsn'         => [
            'name'        => tra('Data Source Name (DSN)'),
            'description' => tra('DSN used for connect to the error tracking service.'),
            'type'        => 'text',
            'filter'      => 'url',
            'default'     => '',
        ],
        'error_tracking_sample_rate' => [
            'name'        => tra('Sample rate'),
            'description' => tra('Sampling allows you to better manage the number of reported events, so you can tailor the volume of data needed. Use a value between 0 and 1. E.g.: 0.25 (will report 25% of the events)'),
            'type'        => 'text',
            'default'     => '1',
            'tags'        => ['advanced'],
        ],
        'error_generic_non_admins'   => [
            'name'        => tra('Show generic error message'),
            'description' => tra('Show a generic error message for non admins users. The error is logged to the database.'),
            'type'        => 'flag',
            'default'     => 'n',
        ],
        'error_generic_message'      => [
            'name'        => tra('Generic error message'),
            'description' => tra('Message to display to non admin users when an error occurs.'),
            'type'        => 'text',
            'filter'      => 'text',
            'default'     => 'There was an issue with your request, please try again later.',
        ],
    ];
}
