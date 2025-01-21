<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\HeadlessBrowser\Exception\HeadlessException;
use Tiki\HeadlessBrowser\HeadlessBrowserFactory;

function prefs_headlessbrowser_list()
{
    global $prefs;

    $prefsArray = [
        'headlessbrowser_integration_type' => [
            'name' => tra('Headless Browser Integration Type'),
            'description' => tra('Type of headless browser integration to be used.'),
            'type' => 'list',
            'options' => [
                'chrome' => tra('Direct Chrome Integration'),
                'casperjs' => tra('CasperJS'),
            ],
            'tags' => ['experimental'],
            'default' => 'chrome',
        ],
        'headlessbrowser_chrome_path' => [
            'name' => tra('Chrome Binary Path'),
            'description' => tra('This ensures that the ChromePHP library can locate and execute the Chrome browser for headless operations'),
            'type' => 'text',
            'tags' => ['experimental'],
            'default' => '',
        ],
    ];

    $packagesRequired = [];
    $browserType = $prefs['headlessbrowser_integration_type'] ?? 'chrome';
    try {
        $browser = HeadlessBrowserFactory::getHeadlessBrowserByType($browserType);
        if ($browser && $browserType !== 'chrome') {
            $packagesRequired = $browser->getRequiredPackage();
            $prefsArray['headlessbrowser_integration_type']['packages_required'] = $packagesRequired;
        }
    } catch (HeadlessException $e) {
        Feedback::error(tr('Headless Browser Error: ' . $e->getMessage()));
    }

    return $prefsArray;
}
