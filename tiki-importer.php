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
        'importerClassName'               => 'word',              //post
        ],
    ],
];
require_once('tiki-setup.php');
require_once('lib/importer/tikiimporter.php');
require_once('lib/importer/tikiimporter_wiki.php');
require_once('lib/wiki/editlib.php');

$access->check_permission('tiki_p_admin_importer');

if (! empty($_POST['importerClassName'])) {
    $importerClassName = filter_input(INPUT_POST, 'importerClassName', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    switch ($importerClassName) {
        case 'TikiImporter_Wiki_Mediawiki':
            require_once('lib/importer/tikiimporter_wiki_mediawiki.php');
            break;
        case 'TikiImporter_Blog_Wordpress':
            require_once('lib/importer/tikiimporter_blog_wordpress.php');
            break;
        case 'default':
            break;
    }

    $importer = new $importerClassName();
    $smarty->assign('softwareName', $importer->softwareName);

    TikiImporter::changePhpSettings();
}

if (isset($_SESSION['tiki_importer_feedback'])) {
    $smarty->assign('importFeedback', $_SESSION['tiki_importer_feedback']);
    $smarty->assign('importLog', $_SESSION['tiki_importer_log']);
    $smarty->assign('importErrors', $_SESSION['tiki_importer_errors']);
    unset($_SESSION['tiki_importer_feedback']);
    unset($_SESSION['tiki_importer_log']);
    unset($_SESSION['tiki_importer_errors']);

    // wordpress specific
    if (isset($_SESSION['tiki_importer_wordpress_urls'])) {
        $smarty->assign('wordpressUrls', $_SESSION['tiki_importer_wordpress_urls']);
        unset($_SESSION['tiki_importer_wordpress_urls']);
    }
} elseif (! empty($_FILES['importFile']) && $access->checkCsrf()) {
    // third step: start the importing process

    if ($_FILES['importFile']['error'] === UPLOAD_ERR_OK) {
        try {
            $importer->import($_FILES['importFile']['tmp_name']);
        } catch (Exception $e) {
            $smarty->assign('msg', $e->getMessage());
            $smarty->display('error.tpl');
            die;
        }
    } else {
        $msg = TikiImporter::displayPhpUploadError($_FILES['importFile']['error']);
        $smarty->assign('msg', $msg);
        $smarty->display('error.tpl');
        die;
    }

    die;
} elseif (! empty($_POST['importerClassName'])) {
    // second step: display import options for the software previously chosen
    if (! class_exists($importerClassName)) {
        $smarty->assign('msg', tra("Invalid software name"));
        $smarty->display("error.tpl");
        die;
    }

    try {
        $importer->checkRequirements();
    } catch (Exception $e) {
        $smarty->assign('msg', $e->getMessage());
        $smarty->display('error.tpl');
        die;
    }

    $importerOptions = $importer->getOptions();

    $smarty->assign('importerOptions', $importerOptions);
    $smarty->assign('softwareSpecificOptions', true);
    $smarty->assign('importerClassName', $importerClassName);
} else {
    // first step: display the list of available software importers

    // $availableSoftwares is an array that control the list of available software importers.
    // The array key is the name of the importer class and the value is the name of the software
    $availableSoftwares = [
        'TikiImporter_Wiki_Mediawiki' => 'MediaWiki',
        'TikiImporter_Blog_Wordpress' => 'WordPress',
    ];

    $smarty->assign('availableSoftwares', $availableSoftwares);
    $smarty->assign('chooseSoftware', true);
}

$smarty->assign('mid', 'tiki-importer.tpl');
$smarty->display('tiki.tpl');
