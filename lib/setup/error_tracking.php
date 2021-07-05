<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class ErrorTracking
{
    private static bool $phpEnabled;
    private static bool $jsEnabled;
    private static bool $started = false;
    private static string $dsn;

    public static function init()
    {
        global $prefs, $systemConfiguration;
        $prefs = $systemConfiguration->preference->toArray() + $prefs;
        self::$phpEnabled = ($prefs['error_tracking_enabled_php'] ?? 'n') === 'y';
        self::$jsEnabled = ($prefs['error_tracking_enabled_js'] ?? 'n') === 'y';
        self::$dsn = $prefs['error_tracking_dsn'] ?? false;

        if (isset(self::$dsn) && self::$phpEnabled) {
            Sentry\init(['dsn' => self::$dsn]);
            self::$started = true;
        }
    }

    public static function isJSEnabled(): bool
    {
        return isset(self::$dsn) && self::$jsEnabled;
    }

    public static function captureException($exception)
    {
        if (self::$started) {
            \Sentry\captureException($exception);
        }
    }

    public static function getDSN(): string
    {
        return self::$dsn;
    }
}
