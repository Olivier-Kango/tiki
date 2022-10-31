<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class TikiLib_LibTest extends PHPUnit\Framework\TestCase
{
    public function testLibShouldReturnInstanceOfTikiLib(): void
    {
        $this->assertInstanceOf(TikiLib::class, TikiLib::lib('tiki'));
    }

    public function testLibShouldReturnInstanceOfCalendar(): void
    {
        $this->assertInstanceOf(CalendarLib::class, TikiLib::lib('calendar'));
    }

    public function testLibShouldReturnNullForInvalidClass(): void
    {
        $this->expectError();
        $this->expectErrorMessage(tr("%0 library not found. This may be due to a typo or caused by a recent update.", 'invalidClass'));
        $this->assertNull(TikiLib::lib('invalidClass'));

    }
}
