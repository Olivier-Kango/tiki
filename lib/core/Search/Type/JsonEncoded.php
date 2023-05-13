<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Type_JsonEncoded implements Search_Type_Interface
{
    private $values;

    public function __construct($values)
    {
        $this->values = $values;
    }

    public function getValue()
    {
        return json_encode($this->values);
    }
}
