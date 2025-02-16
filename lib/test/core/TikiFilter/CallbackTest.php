<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @group unit
 *
 */

class TikiFilter_CallbackTest extends TikiTestCase
{
    public function testSimple()
    {
        $filter = new TikiFilter_Callback('strtoupper');

        $this->assertEquals('HELLO', $filter->filter('hello'));
    }
}
