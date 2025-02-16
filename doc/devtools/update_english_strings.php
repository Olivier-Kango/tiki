<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Used to automatically update all lang/*/language.php files when a English
// string is changed in Tiki source code
// This script is experimental. Always review the changes to language.php file before
// committing.
//
// Also see: doc/devtools/mass_wording_corrections.pl
//


if ($argc < 3) {
    die("\nUsage: php doc/devtools/update_english_strings.php \"oldString\" \"newString\"\n\n");
}

set_include_path(get_include_path() . PATH_SEPARATOR . '../../');

require_once('lib/language/Language.php');

$oldString = Language::addPhpSlashes($argv[1]);
$newString = Language::addPhpSlashes($argv[2]);

$dirHandle = opendir('lang/');

while (($dir = readdir($dirHandle)) !== false) {
    if ($dir == '.' || $dir == '..') {
        continue;
    }

    $dir = 'lang/' . $dir;
    if (is_dir($dir)) {
        $filePath = $dir . '/language.php';

        if (! file_exists($filePath)) {
            continue;
        }

        $langFile = file_get_contents($filePath);
        $fileHandle = fopen($filePath, 'w');
        $langFile = str_replace("\"$oldString\" => ", "\n\"$newString\" => ", $langFile);
        fwrite($fileHandle, $langFile);
        fclose($fileHandle);
    }
}
