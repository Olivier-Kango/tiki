<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Function_Sqrt extends Math_Formula_Function
{
    public function evaluate($element)
    {
        $child = $this->evaluateChild($element[0]);

        if (! is_numeric($child)) {
            if (! $this->suppress_error) {
                $this->error(tr('Trying to execute sqrt on a non-numeric value "%0"', $child));
            }
        } elseif ($child < 0) {
            if (! $this->suppress_error) {
                $this->error(tr('Trying to execute sqrt on a negative value "%0"', $child));
            }
        } else {
            return sqrt($child);
        }
    }
}
