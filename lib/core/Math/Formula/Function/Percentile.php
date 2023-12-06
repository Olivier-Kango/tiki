<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * This uses linear interpolation between closest ranks similar to Excel with C=1
 */
class Math_Formula_Function_Percentile extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $allowed = ['list', 'k'];

        if ($extra = $element->getExtraValues($allowed)) {
            $this->error(tr('Unexpected values: %0', implode(', ', $extra)));
        }

        $list = $element->list;
        if (! $list || count($list) != 1) {
            $this->error(tra('Field must be provided and contain one argument: list'));
        }
        $list = $this->evaluateChild($list[0]);

        if (! $element->k || count($element->k) != 1) {
            $this->error(tra('Field must be provided and contain number.'));
        }
        $k = $this->evaluateChild($element->k[0]);

        if (! is_numeric($k)) {
            $this->error(tr('Not a numeric value for parameter k of precentile function: %0', $k));
        }

        if ($k < 0 || $k > 1) {
            $this->error(tr('Value out of bounds for parameter k of percentile function: %0', $k));
        }

        if (count($list) <= 1) {
            $this->error(tr('Percentile function expects a list of more than 1 element.'));
        }

        sort($list);
        $p = $k * (count($list) - 1) + 1;

        $i = floor($p);
        $dec = $p - $i;

        if ($i == count($list)) {
            return $list[$i];
        }

        return $list[$i] + ($list[$i + 1] - $list[$i]) * $dec;
    }
}
