<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\HeadlessBrowser;

use Tiki\HeadlessBrowser\Exception\HeadlessException;

class HeadlessBrowserFactory
{
    public const CHROME = 'chrome';
    public const CASPERJS = 'casperjs';

    public static function getHeadlessBrowser()
    {
        global $prefs;

        return self::getHeadlessBrowserByType($prefs['headlessbrowser_integration_type'] ?? self::CHROME);
    }

    public static function getHeadlessBrowserByType($type)
    {
        $headlessBrowserType = ucfirst($type);
        $class = "\\Tiki\\HeadlessBrowser\\$headlessBrowserType";

        if (! class_exists($class)) {
            throw new HeadlessException(tr('Unsupported headless browser integration type: %0, Supported types are: %1, %2', $headlessBrowserType, self::CHROME, self::CASPERJS));
        }

        return new $class();
    }
}
