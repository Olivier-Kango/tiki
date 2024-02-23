<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @group gui
 */

 use Facebook\WebDriver\Remote\RemoteWebDriver;
 use Facebook\WebDriver\Remote\DesiredCapabilities;

class AcceptanceTests_MultilinguallibTest extends TikiSeleniumTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped("These tests are still too experimental, so skipping it.");
        $this->webDriver = RemoteWebDriver::create('http://localhost:4444/wd/hub', DesiredCapabilities::chrome());
        $this->webDriver->get('http://localhost/');
        $this->current_test_db = "multilingualTestDump.sql";
        $this->restoreDBforThisTest();
    }

    /**
     * @group gui
     */
}
