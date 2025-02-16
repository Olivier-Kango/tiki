<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Sitemap\Generator;

require_once 'tiki-setup.php';

if ($prefs['sitemap_enable'] == 'y') {
    $siteMapFile = ! empty($_REQUEST['file']) ? (string)$_REQUEST['file'] : Generator::BASE_FILE_NAME . '-index.xml';

    $path = Generator::getRelativePath();

    // filter valid file names
    if (
        ! preg_match('/^.*\.(xml)$/', $siteMapFile, $matches)
        || ! file_exists($path . $siteMapFile)
    ) {
        $smarty->assign(
            'msg',
            tra(
                'The sitemap file is not available. Please check <a href="tiki-admin_sitemap.php" class="alert-link"> sitemap administration </a> to build it.'
            )
        );
        header('HTTP/1.1 404 Not Found');
        $smarty->display('error.tpl');
        die();
    }

    header('Content-Type: application/xml; charset=utf-8');

    if ($siteMapFile === Generator::BASE_FILE_NAME . '-index.xml') {
        $xml = new DOMDocument('1.0', 'UTF-8');

        $xml->load($path . $siteMapFile);
        $root = $xml->documentElement;
        $siteMap = $root->getElementsByTagName('sitemap');

        foreach ($siteMap as $item) {
            $loc = $item->getElementsByTagName('loc');
            if (strpos($loc->item(0)->nodeValue, $path) !== false) {
                if ($prefs['feature_sefurl'] === 'y') {
                    $loc->item(0)->nodeValue = str_replace($path, '', $loc->item(0)->nodeValue);
                } else {
                    $loc->item(0)->nodeValue = str_replace($path, 'tiki-sitemap.php?file=', $loc->item(0)->nodeValue);
                }
            }
        }
        echo $xml->saveXML();
    } else {
        $file = file_get_contents($path . $_REQUEST['file']);
        echo $file;
    }
} else {
    $smarty->assign(
        'msg',
        tra(
            'Required features: sitemap_enable. If you do not have permission to activate these features, ask the site administrator.'
        )
    );
    $smarty->display('error.tpl');
}
