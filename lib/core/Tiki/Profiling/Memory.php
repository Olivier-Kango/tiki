<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Profiling;

class Memory
{
    /**
     * @var $var:  The variable containing the data structure to be measured
     * @return string Approximate PHP RAM used to store the variable, in kilobytes.
     */
    public static function getApproximateRAMUsageForVariable(mixed $var): int
    {
        $total_memory = memory_get_usage();
        $tmp = unserialize(serialize($var));
        return intval((memory_get_usage() - $total_memory) / 1024);
    }

    /**
     * Be carefull, this triggers garbage collection, so it may mask some types of out of memory errors.
     * @return string Approximate PHP RAM variation since last call, in kilobytes.  Will return null if never called before.
     */
    public static function getRAMUsageVariationSinceLastCall(): int
    {
        static $previousMemoryUsage = null;
        $currentMemoryUsage = memory_get_usage();
        $variation = null;
        if ($previousMemoryUsage) {
            $variation = $currentMemoryUsage - $previousMemoryUsage;
        }
        $previousMemoryUsage = $currentMemoryUsage;
        return intval($variation / 1024);
    }
}
