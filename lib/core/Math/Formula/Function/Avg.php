<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_Avg extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $list = [];

        foreach ($element as $child) {
            $child = $this->evaluateChild($child);

            if (is_array($child)) {
                $list = array_merge($list, $child);
            } else {
                $list[] = $child;
            }
        }

        if (empty($list)) {
            return 0;
        } else {
            $initial = $this->firstOrApplicator($list);
            $sum = array_reduce($list, function ($carry, $item) {
                if ($carry instanceof Math_Formula_Applicator) {
                    return $carry->add($item);
                } else {
                    return $carry + $item;
                }
            }, $initial);
            if ($sum instanceof Math_Formula_Applicator) {
                return $sum->div(count($list) + 1);
            } else {
                return $sum / (count($list) + 1);
            }
        }
    }
}
