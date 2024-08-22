<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function validator_cardinality($input, $parameter = '', $message = '')
{
    parse_str($parameter, $arr);

    if (empty($arr) || (! isset($arr['minimum']) && ! isset($arr['maximum']))) {
        return tra("Edit field: (Parameter needs to have at least of one minimum and maximum specified).");
    }

    $count = count(explode(',', $input));

    $minimum = $arr['minimum'] ?? 0;
    $maximum = $arr['maximum'] ?? PHP_INT_MAX;

    if ($count < $minimum || $count > $maximum) {
        if ($message) {
            return tra($message);
        }

        if (isset($minimum) && isset($maximum)) {
            return tra("Number of values must be between {$minimum} and {$maximum}");
        }

        if (isset($minimum)) {
            return tra("Number of values must be at least {$minimum}");
        }

        if (isset($maximum)) {
            return tra("Number of values must be at most {$maximum}");
        }
    }

    return true;
}
