<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_StandardDeviation extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $elements = [];

        foreach ($element as $child) {
            $evaluatedChild = $this->evaluateChild($child);
            $elements[] = ! empty($evaluatedChild) ? $evaluatedChild : 0;
        }

        if (count($elements) == 1 && is_array($elements[0])) {
            $elements = $elements[0];
        }

        foreach ($elements as $child) {
            if (! is_numeric($child)) {
                $this->error(tr('Trying to execute standard-deviation on a non-numeric value "%0"', $child));
            }
        }

        if (count($elements) < 1) {
            $this->error(tr('Trying to execute standard-deviation on an empty list of values.'));
        }

        if (count($elements) == 1) {
            return 0;
        }

        $mean = array_sum($elements) / count($elements);
        $sum = 0;
        foreach ($elements as $elem) {
            $sum += pow($mean - $elem, 2);
        }

        return sqrt($sum / (count($elements) - 1));
    }
}
