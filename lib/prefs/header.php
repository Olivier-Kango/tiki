<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_header_list()
{
    return [
        'header_shadow_start' => [
            'name' => tra('HHeader shadow div start'),
            'type' => 'textarea',
            'size' => '2',
            'default' => '',
        ],
        'header_shadow_end' => [
            'name' => tra('Header shadow div end'),
            'type' => 'textarea',
            'size' => '2',
            'default' => '',
        ],
        'header_custom_css' => [
            'name' => tra('Custom CSS'),
            'description' => tra('Additional CSS rules can be entered here and will apply to all pages, or the CSS ID of a page can be used to limit the scope of the rule (check the HTML source of the particular page to find its body ID tag.)

'),
            'type' => 'textarea',
            'size' => 5,
            'default' => '',
            'filter' => 'none',
        ],
        'header_custom_js' => [
            'name' => tra('Custom JavaScript'),
            'description' => tra('Includes a block of inline JavaScript after the inclusion of jQuery and other JavaScript libs in all pages.'),
            'type' => 'textarea',
            'size' => 5,
            'hint' => tr('Use [https://doc.tiki.org/PluginJS|PluginJS] to include Javascript on a single wiki page.'),
            'default' => '',
            'shorthint' => tra('Do not include the < script > tags.'),
        ],
    ];
}
