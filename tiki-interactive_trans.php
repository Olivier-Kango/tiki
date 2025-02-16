<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'                => [
        'interactive_translation_mode'    => 'word',              //get
        'source'                          => 'word',              //get
        'trans'                           => 'word',              //get
        ],
    ],
];
require_once('tiki-setup.php');
require_once('lib/language/LanguageTranslations.php');

$access->check_feature('lang_use_db');
$access->check_permission('tiki_p_edit_languages');

// start interactive translation session
if (! empty($_REQUEST['interactive_translation_mode'])) {
    $_SESSION['interactive_translation_mode'] = $_REQUEST['interactive_translation_mode'];
    if ($_REQUEST['interactive_translation_mode'] == 'off') {
        $cachelib->empty_cache('templates_c');
    }

    header('Location: index.php');
    exit;
}

/* Called by the JQuery ajax request. No response expected.
 * Save strings translated using interactive translation to database.
 */
if (isset($_REQUEST['source'], $_REQUEST['trans']) && count($_REQUEST['source']) == count($_REQUEST['trans'])) {
    $translations = new LanguageTranslations();

    foreach ($_REQUEST['trans'] as $k => $translation) {
        $source = $_REQUEST['source'][$k];

        $translations->updateTrans($source, $translation);
    }

    exit;
}
