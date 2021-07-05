<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\PSR12Migration;

class Report
{
    public const OFF = 0;
    public const STDOUT = 1;
    public const HTML = 2;

    protected static int $mode = 0; // default OFF

    /**
     * @param int $mode can be "Report::OFF" or any combination of the constants  STDOUT|HTML
     */
    public static function setMode($mode)
    {
        self::$mode = $mode;
    }

    public static function getMode(): int
    {
        return self::$mode;
    }

    public static function classAlias(string $class, string $realClass)
    {
        if (self::getMode() === self::OFF) {
            return; // avoid extra processing
        }

        $backTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        array_shift($backTrace);
        array_shift($backTrace);
        $info = reset($backTrace);

        $message = sprintf(
            'Request to autoload a legacy class "%s" in %s:%s, use "%s" instead',
            $class,
            str_replace(TIKI_PATH . '/', '', $info['file']),
            $info['line'],
            $realClass
        );

        self::printMessage($message);
    }

    public static function methodShim(string $realMethod)
    {
        if (self::getMode() === self::OFF) {
            return; // avoid extra processing
        }

        $backTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        array_shift($backTrace);
        $info = reset($backTrace);

        $message = sprintf(
            "Call to shim %s%s%s in %s:%s, use %s%s%s instead",
            $info['class'],
            $info['type'],
            $info['function'],
            str_replace(TIKI_PATH . '/', '', $info['file']),
            $info['line'],
            $info['class'],
            $info['type'],
            $realMethod
        );

        self::printMessage($message);
    }

    protected static function printMessage(string $message)
    {
        $mode = self::getMode();

        if ($mode & self::STDOUT) {
            echo $message;
        }
        if ($mode & self::HTML) {
            echo '<pre>' . $message . '</pre>';
        }
    }
}
