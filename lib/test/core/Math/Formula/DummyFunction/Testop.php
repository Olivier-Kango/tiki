<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_DummyFunction_Testop extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $object = $element->object;
        $concat = $element->concat;

        if ($object && $concat && count($object) == 2 && count($concat) == 1) {
            $type = $this->evaluateChild($object[0]);
            $id = $this->evaluateChild($object[1]);
            $other = $this->evaluateChild($concat[0]);

            return $type . $id . $other;
        }

        $this->error('Wrong argument count for testop');
    }
}
