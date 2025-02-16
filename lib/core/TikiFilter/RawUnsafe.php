<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Exotic filter which will remove the '<x>', for values previously "neutered" by the PreventXss filter
 * Class TikiFilter_RawUnsafe
 */

class TikiFilter_RawUnsafe implements Laminas\Filter\FilterInterface
{
    public function filter($value)
    {
        return str_replace('<x>', '', $value);
    }
}
