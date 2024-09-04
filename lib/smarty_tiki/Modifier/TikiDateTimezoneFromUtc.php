<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     tiki_date_timezone_from_utc
 * Purpose:  Format a UTC timestamp by applying a specific timezone.
 * Input:    string: UTC timestamp (seconds since Unix epoch)
 *           format: DateTime::format() string for output
 *           tz: optional timezone to apply. If not specified, uses server's default timezone.
 * -------------------------------------------------------------
 */
class TikiDateTimezoneFromUtc
{
    public function handle($string, $format, $tz = null)
    {
        global $prefs;

        $timezone = $this->getValidTimezone($tz ?: $prefs['server_timezone']);

        $datetime = new \DateTime("@$string", new \DateTimeZone('UTC'));
        $datetime->setTimezone($timezone);

        return $datetime->format($format);
    }

    private function getValidTimezone($tz)
    {
        try {
            return new \DateTimeZone($tz);
        } catch (\Exception $e) {
            return new \DateTimeZone('UTC');
        }
    }
}
