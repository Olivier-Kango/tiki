<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\WikiPlugin\Options\BooleanInteger;
use Tiki\WikiPlugin\Options\BooleanNormalizer;

function wikiplugin_html_info()
{
    return [
        'name' => tra('HTML'),
        'documentation' => 'PluginHTML',
        'description' => tra('Add HTML to a page'),
        'prefs' => ['wikiplugin_html'],
        'body' => tra('HTML code'),
        'validate' => 'all',
        'filter' => 'rawhtml_unsafe',
        'iconname' => 'file-code',
        'tags' => [ 'basic' ],
        'introduced' => 3,
        'params' => [
            'tohead' => [
                'required' => false,
                'name' => tra('Move to HTML head'),
                'description' => tra('Insert the code in the HTML head section rather than in the body.'),
                'since' => '17.0',
                'options' => BooleanInteger::options(),
                'filter' => 'digits',
                'default' => BooleanInteger::No->value,
            ],
            'wiki' => [
                'required' => false,
                'name' => tra('Wiki Syntax'),
                'description' => tra('Parse wiki syntax within the HTML code.'),
                'since' => '3.0',
                'options' => BooleanInteger::options(),
                'filter' => 'digits',
                'default' => BooleanInteger::No->value,
            ],
        ],
    ];
}

function wikiplugin_html($data, $params)
{
    // strip out sanitation which may have occurred when using nested plugins
    $html = str_replace('<x>', '', $data);

    // parse using is_html if wiki param set, or just decode html entities
    if (BooleanNormalizer::isTruthy($params['wiki'])) {
        $html = TikiLib::lib('parser')->parse_data($html, ['is_html' => true, 'parse_wiki' => true, 'is_plugin_html' => true]);
    } else {
        $html  = html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
    }
    // exit($html);
    if (BooleanNormalizer::isTruthy($params['tohead'])) {
        // Insert in HTML head rather than in body
        TikiLib::lib('header')->add_rawhtml($html);
    } else {
        return '~np~' . $html . '~/np~';
    }
}
