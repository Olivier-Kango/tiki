<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_indent_info()
{
    return [
        'name' => tra('Indentation'),
        'documentation' => 'PluginIndent',
        'description' => tra('Indent a block of wiki content by one level.'),
        'format' => 'wiki',
        'prefs' => [ 'wikiplugin_indent' ],
        'body' => tra('Wiki content (text) that is to be indented.'),
        'introduced' => 24,
        'filter' => 'wikicontent',
        'tags' => [ 'basic' ],
        'params' => [ ],
    ];
}

function wikiplugin_indent($data, $params)
{
    return "<ul><li style=\"list-style-type:none\">$data</li></ul>";
}
