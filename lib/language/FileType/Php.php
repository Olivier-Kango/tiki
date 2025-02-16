<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @package   Tiki
 * @subpackage    Language
 * Define properties to collect translatable
 * strings from PHP files.
 */
class Language_FileType_Php extends Language_FileType
{
    protected $regexes = [
        'singleQuoted' => '|\Wtra?\s*\(\s*\'(.+?)\'\s*[\),]|s', // strings encapsulated with single quotes
        'doubleQuoted' => '|\Wtra?\s*\(\s*"(.+?)"\s*[\),]|s' // strings encapsulated with double quotes
    ];

    protected $extensions = ['.php'];

    protected $cleanupRegexes = [
        "!/\*.*?\*/!s" => '',  // C comments
        "!^\s*//get_strings(.*)\$!m" => '$1', // the strings that will be translated later through a variable are marked with //get_strings tra("string")
        "!^\s*//.*\$!m" => '', // C++ comments
        "!^\s*\#.*\$!m" => '', // shell comments
        '/\Wtra?\s*\((["\'])\1\)/' => '', // remove empty calls to tra() (tra('') or tra(""))
    ];

    /**
     * Post process method for regex that collect single quoted
     * strings.
     *
     * @param array $strings
     * @return array modified $strings array
     */
    public function singleQuoted(array $strings)
    {
        return str_replace("\'", "'", $strings);
    }

    /**
     * Post process method for regex that collect double quoted
     * strings.
     *
     * @param array $strings
     * @return array modified $strings array
     */
    public function doubleQuoted(array $strings)
    {
        // Strip the extracted strings from escapes
        // (these will be reinserted during generation)
        foreach ($strings as $key => $string) {
            $strings[$key] = Language::removePhpSlashes($string);
        }

        return $strings;
    }
}
