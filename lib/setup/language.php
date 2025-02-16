<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

// Indicates whether a locale identifier is valid
// $localeIdentifier: A locale identifier, such as "en"
// Returns a boolean, true if and only if the given locale identifier is valid and allowed.
function isValidLocale($localeIdentifier = '')
{
    global $prefs;
    return preg_match("/[a-zA-Z-_]+$/", $localeIdentifier) && file_exists('lang/' . $localeIdentifier . '/language.php')
        && ($prefs['restrict_language'] === 'n' || empty($prefs['available_languages']) || in_array($localeIdentifier, $prefs['available_languages']));
}

// Sets the language
// $localeIdentifier: the identifier of the locale to set
// Returns true on success, false on failure (if $localeIdentifier is not a valid and allowed locale identifier)
function setLanguage($localeIdentifier = '', $languageAdmin = false)
{
    $smarty = TikiLib::lib('smarty');
    $tikilib = TikiLib::lib('tiki');
    global $prefs, $user;

    $prefName = $languageAdmin ? 'language_admin' : 'language';

    if (isValidLocale($localeIdentifier)) {
        $prefs[$prefName] = $localeIdentifier;
        $smarty->refreshLanguage();
        return $tikilib->set_user_preference($user, $prefName, $localeIdentifier);
    } else {
        return false;
    }
}

if ($prefs['feature_multilingual'] != 'y') { // change_language depends on feature_multilingual.
    $prefs['change_language'] = 'n';
}

if ($prefs['change_language'] == 'y') {
    // $noSwitchLang = true; // Uncomment to disable switchLang
    if (isset($_GET['switchLang']) && ! isset($noSwitchLang)) {
        // Special feature to allow creating Tiki links that also permanently switch the language of the user following the link.
        // Tiki does not create such links. See http://doc.tiki.org/i18n+Admin#Goodies
        setLanguage($_GET['switchLang']);
    } elseif ($prefs['feature_detect_language'] == 'y' and ! $tikilib->userHasPreference('language')) {
        // Detect browser language
        $browser_language = detect_browser_language();
        if (isValidLocale($browser_language)) {
            $prefs['language'] = $browser_language;
        }
    }
} elseif (! empty($section) && $section == 'admin' && ! empty($prefs['language_admin'])) {
    $prefs['language'] = $prefs['language_admin'];
} else {
    $prefs['language'] = $prefs['site_language'];
}

if (! isValidLocale($prefs['language'])) {
    // Override broken user locales
    setLanguage($prefs['site_language']);
}
