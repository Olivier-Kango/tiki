<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Math_Formula_InternalString
{
    private $content;
    private $type;
    private $children;

    public function __construct($content)
    {
        $this->content = trim($content, '"');
    }

    public function getContent()
    {
        return $this->content;
    }
}
