<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Dummy filter (identity function)
class TikiFilter_None implements Laminas\Filter\FilterInterface
{
    public function filter($value)
    {
        return $value;
    }
}
