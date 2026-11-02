<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

global $prefs, $tikilib, $user;

// Handle explicit switchLang choices separately from automatic selection.
if (
    $prefs['feature_multilingual'] === 'y'
    && $prefs['change_language'] === 'y'
    && isset($_GET['switchLang'])
    && is_string($_GET['switchLang'])
    && Language::isValidLocale($_GET['switchLang'])
    && $tikilib->get_user_preference($user, 'language') !== $_GET['switchLang']
) {
    Language::setCurrentLanguage($_GET['switchLang']);
}
