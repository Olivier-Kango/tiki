<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_Pow extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $elements = [];

        foreach ($element as $child) {
            $evaluatedChild = $this->evaluateChild($child);
            $elements[] = ! empty($evaluatedChild) ? $evaluatedChild : 0;
        }

        if (count($elements) != 2) {
            $this->error(tr('Incorrect number of arguments supplied to pow function. Found %0 but exactly 2 expected.', count($elements)));
        }

        foreach ($elements as $child) {
            if (! is_numeric($child)) {
                $this->error(tr('Trying to execute pow on a non-numeric value "%0"', $child));
            }
        }

        return pow($elements[0], $elements[1]);
    }
}
