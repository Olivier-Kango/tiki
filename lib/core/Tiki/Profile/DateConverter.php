<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Profile_DateConverter
{
    public function convert($value)
    {
        if (is_int($value)) {
            return $value;
        }

        $time = strtotime($value);
        if ($time !== false) {
            return $time;
        }
    }
}
