<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Exception_NotFound extends Services_Exception
{
    public function __construct($message = '')
    {
        if (empty($message)) {
            $message = tr('Not found');
        }
        parent::__construct($message, 404);
    }
}
