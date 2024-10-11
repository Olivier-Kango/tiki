<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Errors$

namespace Tiki;

class Errors
{
    /**
     * Get php error reporting level configured
     *
     * @return int
     */
    public static function getErrorReportingLevel()
    {
        global $prefs;

        if ($prefs['error_reporting_level'] == 2047) {
            $errorReportingLevel = E_ALL;
        } elseif ($prefs['error_reporting_level'] == 2039) {
            $errorReportingLevel = E_ALL & ~E_NOTICE & ~E_USER_NOTICE;
        } elseif ($prefs['error_reporting_level'] == -1) {
            $errorReportingLevel = E_ALL;
        } elseif ($prefs['error_reporting_level'] == 1) {
            $errorReportingLevel = error_reporting();
        } else {
            $errorReportingLevel = $prefs['error_reporting_level'];
        }

        return (int)$errorReportingLevel;
    }
}
