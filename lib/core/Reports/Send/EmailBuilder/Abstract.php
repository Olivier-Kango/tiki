<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
abstract class Reports_Send_EmailBuilder_Abstract
{
    abstract protected function getTitle();
    abstract public function getOutput(array $changes);
}
