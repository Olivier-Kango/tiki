<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function prefs_markdown_list()
{
    return [
        'markdown_enabled' => [
            'name' => tr('Markdown'),
            'description' => tr('Support markdown syntax.'),
            'type' => 'flag',
            'default' => 'n',
            'help' => 'Markdown',
            'keywords' => 'Markdown',
            'tags' => ['advanced'],
        ],
        'markdown_gfm' => [
            'name' => tr('Github-flavored markdown'),
            'description' => tra('Enable Github-flavored markdown.'),
            'type' => 'flag',
            'default' => 'y',
            'dependencies' => [
                'markdown_enabled',
            ],
        ],
        'markdown_default' => [
            'name' => tr('Default syntax'),
            'description' => tr('Which syntax to use as the default wiki syntax when a new content block is created.'),
            'type' => 'list',
            'options' => [
                'tiki' => tra('Tiki-style wiki syntax'),
                'markdown' => tra('Markdown'),
            ],
            'default' => 'tiki',
            'dependencies' => [
                'markdown_enabled',
            ],
        ],
    ];
}
