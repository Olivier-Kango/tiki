<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_Eval extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $out = [];

        foreach ($element as $child) {
            $out[] = $this->evaluateChild($child);
        }

        return implode(' ', $out);
    }
}
