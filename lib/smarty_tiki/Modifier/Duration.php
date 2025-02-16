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
 * Name:     duration
 * Purpose:  formats a duration from seconds
 * -------------------------------------------------------------
 */
class Duration
{
    public function handle($string, $long = true, $maxLevel = false)
    {
        if (! is_numeric($string)) {
            return $string;
        }
        $values = [31536000, 2628000, 604800, 86400, 3600, 60, 1];
        $output = [tra('year'), tra('month'), tra('week'), tra('day'), tra('hour'), tra('minute'), tra('second')];
        $outputs = [tra('years'), tra('months'), tra('weeks'), tra('days'), tra('hours'), tra('minutes'), tra('seconds')];
        $result = [];

        // maxLevel defines the maximum unit to be consider (e.g. $maxLevel = 'hour')
        if (is_string($maxLevel) && $level = array_search(tra($maxLevel), $output)) {
            $values = array_slice($values, $level);
            $output = array_slice($output, $level);
            $outputs = array_slice($outputs, $level);
        }

        foreach ($values as $i => $value) {
            if ($string >= $value) {
                $nb = floor($string / $value);
                // add a zero before seconds or minutes with just one digit if $long == false
                $nb = (! $long && ! empty($result) && ($output[$i] == 'minute' || $output[$i] == 'second') && strlen($nb) == 1) ? 0 . $nb : $nb;
                $s = ($nb == 1) ? $output[$i] : $outputs[$i];
                $s = $long ? " $s" : substr($s, 0, 1);
                $string = $string % $value;
                $result[] = "$nb$s";
            }
        }
        return implode(' ', $result);
    }
}
