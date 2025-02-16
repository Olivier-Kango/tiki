<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_Function_Hash extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $parts = [];

        foreach ($element as $child) {
            $component = $this->evaluateChild($child);
            $parts = array_merge($parts, (array) $component);
        }

        return sha1(implode('/', $parts));
    }
}
