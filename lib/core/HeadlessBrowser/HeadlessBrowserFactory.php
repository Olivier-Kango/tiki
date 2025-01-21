<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\HeadlessBrowser;

use Tiki\HeadlessBrowser\Exception\HeadlessException;

class HeadlessBrowserFactory
{
    public static function getHeadlessBrowser()
    {
        global $prefs;

        return self::getHeadlessBrowserByType($prefs['headlessbrowser_integration_type'] ?? 'chrome');
    }

    public static function getHeadlessBrowserByType($type)
    {
        $headlessBrowserType = ucfirst($type);
        $class = "\\Tiki\\HeadlessBrowser\\$headlessBrowserType";

        if (! class_exists($class)) {
            throw new HeadlessException(tr('Unsupported headless browser integration type: ' . $headlessBrowserType . ', Supported types are: chrome, casperjs'));
        }

        $headlessBrowser = new $class();

        $isPackageInstalled = $headlessBrowser->getRequiredPackage();

        foreach ($isPackageInstalled as $package => $class) {
            if (! class_exists($class)) {
                throw new HeadlessException(tr("$package not available"));
            }
        }

        return $headlessBrowser;
    }
}
