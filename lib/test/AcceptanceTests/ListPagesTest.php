<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @group gui
 */
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class AcceptanceTests_ListPagesTest extends TikiSeleniumTestCase
{
    public function ___testRememberToReactivateTestsIn_AcceptanceTests_ListPagesTest()
    {
        $this->fail("don't forget!!");
    }

    /**
     * @group gui
     */
    public function testListPagesTableIsWellFormed()
    {
        $this->openTikiPage('tiki-listpages.php');
        $this->assertListPagesTableIsWellFormed();
        $this->assertListedPagesWere([0 => "HomePage", 1 => "EnglishTestPage"], "Listed pages");
        // Find the table cell text for each column
        $pageColumn = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='tiki-listpages-content']/form/table/tbody/tr/td[1]"))->getText();
        $hitsColumn = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='tiki-listpages-content']/form/table/tbody/tr/td[2]"))->getText();
        $lastModColumn = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='tiki-listpages-content']/form/table/tbody/tr/td[3]"))->getText();
        $lastAuthorColumn = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='tiki-listpages-content']/form/table/tbody/tr/td[4]"))->getText();
        $versionColumn = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='tiki-listpages-content']/form/table/tbody/tr/td[5]"))->getText();
        // Perform assertions
        $this->assertEquals("Page", $pageColumn);
        $this->assertEquals("Hits", $hitsColumn);
        $this->assertEquals("Last mod", $lastModColumn);
        $this->assertEquals("Last author", $lastAuthorColumn);
        $this->assertEquals("Vers.", $versionColumn);
    }

    /**
     * @group gui
     */
    public function testPageSortingWorks()
    {
        $this->openTikiPage('/tiki-trunk/tiki-listpages.php');
        // Click on the "Page" link to sort in ascending order
        $pageLink = $this->webDriver->findElement(WebDriverBy::linkText('Page'));
        $pageLink->click();
        $this->webDriver->wait(10)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath("//div[@id='tiki-listpages-content']/form/table/tr[2]/td[1]"))
        );
        $firstRowData = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='tiki-listpages-content']/form/table/tr[2]/td[1]"))->getText();
        $secondRowData = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='tiki-listpages-content']/form/table/tr[3]/td[1]"))->getText();
        $this->assertEquals("EnglishTestPage", $firstRowData, "Pages were not sorted out in ascending order");
        $this->assertEquals("HomePage", $secondRowData, "Pages were not sorted out in ascending order");
        // Click again to sort in descending order
        $pageLink->click();
        $this->webDriver->wait(10)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath("//div[@id='tiki-listpages-content']/form/table/tr[2]/td[1]"))
        );
        $firstRowData = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='tiki-listpages-content']/form/table/tr[2]/td[1]"))->getText();
        $secondRowData = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='tiki-listpages-content']/form/table/tr[3]/td[1]"))->getText();
        $this->assertEquals("HomePage", $firstRowData, "Pages were not sorted out in descending order");
        $this->assertEquals("EnglishTestPage", $secondRowData, "Pages were not sorted out in descending order");
    }

    /**
     * @group gui
     */
    public function testDeleteAPageFromTheList()
    {
        $this->openTikiPage('tiki-listpages.php');
        $this->logInIfNecessaryAs('admin');
        $this->assertListedPagesWere([0 => 'HomePage', 1 => 'EnglishTestPage'], "Listed pages");
        $this->assertTrue($this->isElementPresent("//img[@alt='Remove']"));
         // Find the remove button and click on it
        $this->webDriver->findElement(WebDriverBy::xpath("//img[@alt='Remove']"))->click();
        // Wait for the page to reload
        $this->webDriver->wait(10, 1000)->until(
            WebDriverExpectedCondition::urlContains('tiki-listpages.php')
        );
        // Open the page again to verify the deletion
        $this->openTikiPage('tiki-listpages.php');
        $this->assertListedPagesWere([0 => "HomePage"], "Listed pages");
    }

    /**
     * @group gui
     */
    public function testLinksInListPagesWork()
    {
        $this->openTikiPage('tiki-listpages.php');
        $this->logInIfNecessaryAs('admin');
        $this->assertTrue($this->isElementPresent("link=EnglishTestPage"), "EnglishTestPage was not there");
        $this->clickAndWait("link=EnglishTestPage");
        $this->assertTrue($this->isTextPresent("This is a test page in English"));
        $this->openTikiPage('tiki-listpages.php');
        $this->clickAndWait("link=HomePage");
        $this->assertTrue($this->isTextPresent("Thank you for installing Tiki"));
    }


    /**************************************
     * Helper methods
     **************************************/

    protected function setUp(): void
    {
        $this->markTestSkipped("These tests are still too experimental, so skipping it.");
        $capabilities = [
            WebDriverCapabilityType::BROWSER_NAME => 'chrome', // or 'firefox'
            // Add any other desired capabilities
        ];
        $this->webDriver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);
        $this->current_test_db = "listPagesTestDump.sql";
        $this->restoreDBforThisTest();
    }

    private function assertListPagesTableIsWellFormed()
    {

        try {
            $this->webDriver->findElement(WebDriverBy::id('tiki-listpages-content'));
        } catch (NoSuchElementException $exception) {
            $this->fail('List Pages content was not present');
        }

        try {
            $this->webDriver->findElement(WebDriverBy::xpath("//a[contains(@title,'Last author')]"));
        } catch (NoSuchElementException $exception) {
            $this->fail('Last Author column was not present');
        }

        try {
            $this->webDriver->findElement(WebDriverBy::xpath("//a[contains(@title,'Versions')]"));
        } catch (NoSuchElementException $exception) {
            $this->fail('Versions column was not present');
        }
    }

    private function assertListedPagesWere($listOfPages, $message)
    {
        try {
            $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='tiki-listpages-content']"));
        } catch (NoSuchElementException $exception) {
            $this->fail('List of pages was absent');
        }

        foreach ($listOfPages as $expectedPage) {
            try {
                $this->webDriver->findElement(WebDriverBy::linkText($expectedPage));
            } catch (NoSuchElementException $exception) {
                $this->fail("$message\nLink to expected page '$expectedPage' was missing");
            }
        }
    }
}
