<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @group gui
 */
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class AcceptanceTests_SearchTest extends TikiSeleniumTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped("These tests are still too experimental, so skipping it.");
        // Set up the browser URL
        $this->webDriver = RemoteWebDriver::create('http://localhost:4444/wd/hub', DesiredCapabilities::chrome());
        $this->webDriver->get('http://localhost/');

        $this->current_test_db = "searchTestDump.sql";
        $this->restoreDBforThisTest();
    }

    public function ___testRememberToReactivateAllTestsInSearchTest()
    {
        $this->fail("Don't forget to do this");
    }

    /**
     * @group gui
     */
    public function testSearchFormIsWellFormed()
    {
        $this->openTikiPage('tiki-index.php');
        $this->logInIfNecessaryAs('admin');
        $this->assertSearchFormIsWellFormed();
    }

    /**
     * @group gui
     */
    public function testFillSearchFormAndSubmit()
    {
        $this->openTikiPage('tiki-index.php');
        $this->logInIfNecessaryAs('admin');
        $query = 'feature';
        //      echo $this->getBodyText();
        $this->searchFor($query);

        $this->assertSearchResultsWere(
            [
                0 => "HomePage",
                1 => 'Multilingual Test Page 1',
                2 => 'Another page containing the word feature'
            ],
            $query,
            ""
        );
    }


    /**
     * @group gui
     */
    public function testSearchIsCaseInsensitive()
    {
        $this->openTikiPage('tiki-index.php');
        $this->logInIfNecessaryAs('admin');
        $query = 'hello';
        $this->searchFor($query);
        $this->assertSearchResultsWere(
            [
                0 => "test page for search 1",
                1 => 'test page for search 2'
            ],
            $query,
            "Bad list of search results for query '$query'. Search should have been case insensitive."
        );
    }

    /**
     * @group gui
     */
    public function testByDefaultSearchLooksForAnyOfTheQueryTerms()
    {
        $this->openTikiPage('tiki-index.php');
        $this->logInIfNecessaryAs('admin');
        $query = 'hello world';
        $this->searchFor($query);
        $this->assertSearchResultsWere(
            [
                0 => "test page for search 1",
                1 => "test page for search 2",
                2 => 'test page for search 3'
            ],
            $query,
            "Bad list of search results for multi word query '$query'. Could be that the search engine did not use an OR to combine the search words."
        );
    }

    /**************************************
     * Helper methods
     **************************************/

     /**
     * @param $query
     */

    private function searchFor($query)
    {
        $highlightInput = $this->webDriver->findElement(WebDriverBy::id('highlight'));
        $searchButton = $this->webDriver->findElement(WebDriverBy::id('search'));

        $highlightInput->sendKeys($query);
        $searchButton->click();
    }

    private function assertSearchFormIsWellFormed()
    {
        $searchForm = $this->webDriver->findElement(WebDriverBy::id('search-form'));
        $highlightInput = $this->webDriver->findElement(WebDriverBy::id('highlight'));
        $siteSearchBar = $this->webDriver->findElement(WebDriverBy::id('sitesearchbar'));

        $this->assertTrue($searchForm !== null, "Search form was not present");
        $this->assertTrue($highlightInput !== null, "Search input field not present");
        $this->assertTrue($siteSearchBar !== null, "Site search bar was not present");
    }

    private function assertSearchResultsWere($listOfHits, $query, $message)
    {
        $searchResultsList = $this->webDriver->findElement(WebDriverBy::className('searchresults'));
        $this->assertNotNull($searchResultsList, "List of search results was absent for query '$query'");

        foreach ($listOfHits as $expectedHit) {
            $linkElement = $this->webDriver->findElement(WebDriverBy::linkText($expectedHit));
            $this->assertNotNull($linkElement, "$message\nLink to expected hit '$expectedHit' was missing for query '$query'");
        }
    }
}
