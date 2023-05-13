<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Class TikiFilter_Array
 *
 * Filters for valid array values
 */
class TikiFilter_Array implements Laminas\Filter\FilterInterface
{
    public function filter($value)
    {
        if (is_array($value)) {
            return $value;
        }

        return (array) $value;
    }
}
