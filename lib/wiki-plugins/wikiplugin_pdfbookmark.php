<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function wikiplugin_pdfbookmark_info()
{
            return [
                'name' => tra('PluginPDF Bookmark'),
                'documentation' => 'PluginPDFBookmark',
                'description' => tra('Manual bookmark entry for a PDF file'),
                'tags' => [ 'advanced' ],
                'iconname' => 'pdf',
                'prefs' => [ 'wikiplugin_pdfbookmark' ],
                'introduced' => 18,
                'params' => [
                    'content' => [
                        'required' => false,
                        'name' => tra('Bookmark Label'),
                        'description' => tra(''),
                        'tags' => ['advanced'],
                        'type' => 'text',
                        'default' => ''
                    ],
                    'level' => [
                        'required' => false,
                        'name' => tra('Bookmark level'),
                        'description' => tra(''),
                        'tags' => ['advanced'],
                        'type' => 'text',
                        'default' => '0',
                        'options' => [
                            ['text' => '0','value' => '0'],
                            ['text' => '1','value' => '1'],
                            ['text' => '2','value' => '2'],
                        ],
                    ],
                ]
            ];
}
function wikiplugin_pdfbookmark($data, $params)
{
    if (isset($_GET['display']) && strpos($_GET['display'], 'pdf') !== false) {
        return;
    }
    $paramList = '';
    foreach ($params as $paramName => $param) {
        $paramList .= $paramName . "='" . $param . "' ";
    }
    return "<bookmark " . $paramList . " />";
}
