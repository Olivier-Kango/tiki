<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
interface Search_Formatter_ValueFormatter_Interface
{
    public function render($name, $value, array $entry);

    public function canCache();
}
