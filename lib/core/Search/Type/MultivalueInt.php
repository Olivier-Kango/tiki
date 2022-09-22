<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Type_MultivalueInt implements Search_Type_Interface
{
    private $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function getRawValue()
    {
        return $this->values;
    }

    public function getValue()
    {
        $ints = [];
        foreach ($this->values as $val) {
            $ints[] = crc32($val);

        }
        return $ints;
    }
}
