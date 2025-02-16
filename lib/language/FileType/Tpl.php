<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Define properties to collect translatable
 * strings from Tpl files (Smarty templates).
 */
class Language_FileType_Tpl extends Language_FileType
{
    protected $regexes = [
        // Only extract {tr} ... {/tr} in .tpl-files
        // Also match {tr [args]} ...{/tr}
        '/\{tr(?:\s+[^\}]*)?\}(.+?)\{\/tr\}/s',
    ];

    protected $extensions = ['.tpl'];

    protected $cleanupRegexes = [
        // Do not translate text in Smarty comments: {* Smarty comment *}
        // except if it is an string marked {*get_strings {tr}string{/tr} *}
        '/\{\*get_strings(.*?)\*\}/s' => '$1',
        '/\{\*.*?\*\}/s' => '', // Smarty comment
    ];
}
