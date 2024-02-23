<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/*************************************************************
 * Automated acceptance tests for Multilingual Features.
 *************************************************************/

/**
 * @group gui
 */

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class AcceptanceTests_MultilingualTest extends TikiSeleniumTestCase
{
    public function disabledTestRememberToReactivateAllTestsInMultilingualTest()
    {
        $this->fail("Don't forget to do this");
    }

    /**
     * @group gui
     */
    public function testHomePageIsMultilingual()
    {
        $this->openTikiPage('tiki-index.php');
        $this->logInIfNecessaryAs('admin');
        print "\n" . $this->webDriver->getPageSource() . "\n";
        $this->assertLanguagePicklistHasLanguages(['English' => 'HomePage']);
    }

    /**
     * @group gui
     */
    public function testMultilingualPageDisplaysLanguagePicklist()
    {
        $this->openTikiPage('tiki-index.php?page=Multilingual+Test+Page+1');
        $this->logInIfNecessaryAs('admin');
        $this->assertLanguagePicklistHasLanguages(
            ['English' => 'Multilingual Test Page 1', 'Français' => 'Page de test multilingue 1']
        );
    }

    /**
     * @group gui
     */
    public function testSwitchBetweenLanguages()
    {
        $this->openTikiPage('tiki-index.php?page=Multilingual+Test+Page+1');
        $this->logInIfNecessaryAs('admin');
        $this->doSwitchLanguageTo('Français');
        $this->assertEquals(1, preg_match('/page=Page\+de\+test\+multilingue\+1/', $this->webDriver->getCurrentURL()));
    }


    /**
     * @group gui
     */
    public function testLanguageLinkLeadsToTranslatedPageInThatLanguage()
    {
        $this->openTikiPage('tiki-index.php?page=Multilingual+Test+Page+1');
        $this->logInIfNecessaryAs('admin');
        $this->doSwitchLanguageTo('Français');
        $this->assertEquals(1, preg_match('/page=Page\+de\+test\+multilingue\+1/', $this->webDriver->getCurrentURL()));
        $this->assertTrue($this->webDriver->findElement(WebDriverBy::linkText('Page de test multilingue 1'))->isDisplayed());
        $this->assertLanguagePicklistHasLanguages(
            ['Français' => 'Page de test multilingue 1', 'English' => 'Multilingual Test Page 1']
        );
    }

    /**
     * @group gui
     */
    public function testTranslateOptionAppearsOnlyWhenLoggedIn()
    {
        $this->openTikiPage('tiki-index.php');
        $this->logOutIfNecessary();
        //          print "\n".$this->getHtmlSource()."\n";
        $this->assertLanguagePicklistHasNoTranslateOption();
        $this->logInIfNecessaryAs('admin');
        $this->assertLanguagePicklistHasTranslateOption();
    }

    /**
     * @group gui
     */
    public function testClickOnTranslateShowsTranslatePage()
    {
        $this->openTikiPage('tiki-index.php');
        $this->logInIfNecessaryAs('admin');
        // Find the select element and select the option with label "Translate"
        $selectElement = $this->webDriver->findElement(WebDriverBy::id('page'));
        $selectElement->selectByVisibleText('Translate');

        $this->webDriver->wait(30)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::linkText('Translate: HomePage (English, en)'))
        );
        // Assert the presence of the link
        $this->assertTrue($this->webDriver->findElement(WebDriverBy::linkText('Translate: HomePage (English, en)'))->isDisplayed());
    }


    /**
     * @group gui
     */
    public function testListOfLanguagesOnTranslatePageDoesNotContainAlreadyTranslatedLanguages()
    {
        $this->openTikiPage('tiki-index.php?page=Multilingual+Test+Page+1');
        $this->logInIfNecessaryAs('admin');
       // $this->select("page", "label=Translate");
        $selectElement = $this->webDriver->findElement(WebDriverBy::id('page'));
        $selectElement->selectByVisibleText('Translate');

       // $this->waitForPageToLoad("30000");
       // $selectLanguagesElement = $this->webDriver->findElement(WebDriverBy::xpath("//select[@name='lang']"));
        $selectLanguagesElement = $this->webDriver->findElement(WebDriverBy::xpath("//form[@id='tiki-center']/p/select[@name='lang']"));

       // $this->assertSelectElementDoesNotContainItems(
       //     "xpath=id('tiki-center')/form[1]/p/select[@name='lang']",
       //     ['English' => 'en'],
       //     "English should not have been present in the list of languages."
       // );

        $this->assertSelectElementDoesNotContainItems(
            $selectLanguagesElement,
            ['English' => 'en'],
            "English should not have been present in the list of languages."
        );
    }

    /**
     * @group gui
     */
    public function testCannotGiveATranslationTheNameOfAnExistingPage()
    {
        //NB. This is in fact wrong. If you have similar languages, say English and British English,
        //or Serbian (latin alphabet) and Croatian, the title of the page is bound to be the same. Here we force
        //the translator to add a language tag to the title only to have the unique page name
        //A multilingual system should add a language ID to the page name without the user's
        //intervention.
        $this->openTikiPage('tiki-index.php?page=Multilingual+Test+Page+1');
        $this->logInIfNecessaryAs('admin');
        $this->doSwitchLanguageTo('Français');
         // Locate the "Translate" link element
         $translateLink = $this->webDriver->findElement(WebDriverBy::linkText('Translate'));
         // Click on the "Translate" link
         $translateLink->click();
         // Explicitly wait for the new page to load
         // $this->webDriver->wait(10)->until(
         //     WebDriverExpectedCondition::titleContains('Translate')
         // );
         // Use a more specific XPath expression to locate the elements
         $languageListSelect = $this->webDriver->findElement(WebDriverBy::id('language_list'));
         $translationNameInput = $this->webDriver->findElement(WebDriverBy::id('translation_name'));

         // Select a language and type a translation name
         $languageListSelect->selectByVisibleText('English British (en-uk)');
         $translationNameInput->sendKeys('Multilingual Test Page 1');
         // Click the "Create translation" button
         $createTranslationButton = $this->webDriver->findElement(WebDriverBy::xpath("//input[@value='Create translation']"));
         $createTranslationButton->click();

         // Get the page source
         $pageSource = $this->webDriver->getPageSource();
         // Check if the expected text is present in the page source
         $textPresent = strpos($pageSource, "That page already exists. Go back and choose a different name.") !== false;
         $this->assertTrue($textPresent);
    }

    /**
     * @group gui
     */
    public function testShouldNotChangeLanguageOfThePageInCaseCreateTranslationFails()
    {
        //In case when a page already exists create translation gives an error message which is ok.
        //But it shouldn't change the language of the existing page to the language chosen for translation.
        $this->openTikiPage('tiki-index.php?page=Multilingual+Test+Page+1');
        $this->logInIfNecessaryAs('admin');
        $this->doSwitchLanguageTo('Français');
        // $this->clickAndWait("link=Translate");
        $this->webDriver->findElement(WebDriverBy::linkText('Translate'))->click();

       // Select language and provide translation name
        $languageListSelect = $this->webDriver->findElement(WebDriverBy::id('language_list'));
        $translationNameInput = $this->webDriver->findElement(WebDriverBy::id('translation_name'));
        $createTranslationButton = $this->webDriver->findElement(WebDriverBy::xpath("//input[@value='Create translation']"));

        $languageListSelect->selectByVisibleText('English British (en-uk)');
        $translationNameInput->sendKeys('Multilingual Test Page 1');
        $createTranslationButton->click();

        // Explicitly wait for the error message to appear
        $this->webDriver->wait(10)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::xpath("//*[contains(text(),'That page already exists. Go back and choose a different name.')]"))
        );

        // Go back and check language picklist
        $this->webDriver->findElement(WebDriverBy::linkText('Go back'))->click();
        $this->webDriver->findElement(WebDriverBy::linkText('View Page'))->click();
        //A bug: instead of English it shows English British although the page was not created
        $this->assertLanguagePicklistHasLanguages(
            ['Français' => 'Page de test multilingue 1', 'English' => 'Multilingual Test Page 1']
        );
        $this->assertLanguagePicklistDoesNotHaveLanguages(['English British' => 'Multilingual Test Page 1']);
    }


    /**
     * @group gui
     */
    public function testUpToDatenessIs100percentForTheCompletelyTranslatedPages()
    {
        $this->openTikiPage('tiki-index.php?page=Page+de+test+multilingue+1');
        $this->logInIfNecessaryAs('admin');
        // Check for the presence of elements containing the expected text
        $upToDateElement = $this->webDriver->findElement(WebDriverBy::xpath("//*[contains(text(),'Up-to-date-ness: 100%')]"));
        $equivalentTranslationsElement = $this->webDriver->findElement(WebDriverBy::xpath("//*[contains(text(),'Equivalent translations: Multilingual Test Page 1 (en)')]"));
        // Assert that the elements are present
        $this->assertTrue($upToDateElement->isDisplayed(), "Up-to-date-ness: 100% not found");
        $this->assertTrue($equivalentTranslationsElement->isDisplayed(), "Equivalent translations: Multilingual Test Page 1 (en) not found");
    }


    /**
     * @group gui
     */
    public function testUponAddingNewContentTranslationsThatNeedImprovementAppears()
    {
        $this->openTikiPage('tiki-index.php?page=Multilingual+Test+Page+1');
        $this->logInIfNecessaryAs('admin');
        $this->webDriver->findElement(WebDriverBy::linkText('Edit'))->click();
        $editWikiInput = $this->webDriver->findElement(WebDriverBy::id('editwiki'));
        $editWikiInput->sendKeys("This is the first multilingual test page.\n\nAdding some text yet to be translated.");
        $this->webDriver->findElement(WebDriverBy::id('save'))->click();
        $this->webDriver->findElement(WebDriverBy::linkText('More...'))->click();
        $this->assertTextPresent("Translations that need improvement: None match your preferred languages.\n More... Page de test multilingue 1 (fr)");
        $this->assertTrue($this->webDriver->findElement(WebDriverBy::linkText('Page de test multilingue 1'))->isDisplayed());
        $this->webDriver->findElement(WebDriverBy::linkText('Page de test multilingue 1'))->click();
        // Assert that up-to-dateness is now less than 100%
        $upToDateDiv = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='mod-translationr10']/div[1]"));
        $this->assertMatchesRegularExpression("/Up-to-date-ness: [0-9]{2}%/", $upToDateDiv->getText(), "Up-to-dateness should have been less than 100%.");
        $this->assertTextPresent("Better translations: Multilingual Test Page 1 (en)");
    }

    /**
     * @group gui
     */
    public function testCompleteTranslationBringsBackUpToDatenessTo100()
    {
        $this->openTikiPage('tiki-index.php?page=Multilingual+Test+Page+1');
        $this->logInIfNecessaryAs('admin');
        // Click on the "Edit" link
        $editLink = $this->webDriver->findElement(WebDriverBy::linkText('Edit'));
        $editLink->click();

        // Type the new content in the edit field and save
        $editWikiInput = $this->webDriver->findElement(WebDriverBy::id('editwiki'));
        $editWikiInput->clear();
        $editWikiInput->sendKeys("This is the first multilingual test page.\n\nAdding some text yet to be translated.");

        $saveButton = $this->webDriver->findElement(WebDriverBy::id('save'));
        $saveButton->click();

        // Click on "More..." link
        $moreLink = $this->webDriver->findElement(WebDriverBy::linkText('More...'));
        $moreLink->click();

        // Click on "Update it" link
        $updateLink = $this->webDriver->findElement(WebDriverBy::xpath("//img[@alt='update it']"));
        $updateLink->click();

        // Type the updated content in the edit field and save
        $editWikiInput->clear();
        $editWikiInput->sendKeys("Ceci est la première page multilingue de test.\n\nAjout du texte à traduire.");

        $saveButton->click();

        // Assert the presence of expected text
        $this->assertTextPresent("Up-to-date-ness: 100%");
        $this->assertTextPresent("Equivalent translations: Multilingual Test Page 1 (en)");

        // Click on "Multilingual Test Page 1" link
        $pageLink = $this->webDriver->findElement(WebDriverBy::linkText('Multilingual Test Page 1'));
        $pageLink->click();

        // Assert the presence of expected text
        $this->assertTextPresent("Up-to-date-ness: 100%");
    }

    /**
     * @group gui
     */
    public function testPartialTranslationBringsUpUpToDatenessPourcentage()
    {
        $this->openTikiPage('tiki-index.php?page=Multilingual+Test+Page+1');
        $this->logInIfNecessaryAs('admin');

        // Click on the "Edit" link
        $editLink = $this->webDriver->findElement(WebDriverBy::linkText('Edit'));
        $editLink->click();

        // Type the initial content in the edit field and save
        $editWikiInput = $this->webDriver->findElement(WebDriverBy::id('editwiki'));
        $editWikiInput->clear();
        $editWikiInput->sendKeys("This is the first multilingual test page.\n\nAdding some text yet to be translated.");

        $saveButton = $this->webDriver->findElement(WebDriverBy::id('save'));
        $saveButton->click();

        // Click on "More..." link
        $moreLink = $this->webDriver->findElement(WebDriverBy::linkText('More...'));
        $moreLink->click();

        // Click on "Page de test multilingue 1" link
        $pageLink = $this->webDriver->findElement(WebDriverBy::linkText('Page de test multilingue 1'));
        $pageLink->click();

        // Assert the up-to-date-ness percentage
        $upToDateDiv = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='mod-translationr10']/div[1]"));
        $upToDateText = $upToDateDiv->getText();
        $this->assertMatchesRegularExpression("/Up-to-date-ness: ([0-9]{2})%/", $upToDateText);
        preg_match("/Up-to-date-ness: ([0-9]{2})%/", $upToDateText, $matches);
        $first_percentage = $matches[1];

        // Click on "update from it" link
        $updateLink = $this->webDriver->findElement(WebDriverBy::xpath("//img[@alt='update from it']"));
        $updateLink->click();

        // Type the updated content in the edit field and save partially
        $editWikiInput->clear();
        $editWikiInput->sendKeys("Ceci est la première page multilingue de test.\n\nAjout du texte à traduire.");

        $partialSaveButton = $this->webDriver->findElement(WebDriverBy::id('partial_save'));
        $partialSaveButton->click();

        // Assert the up-to-date-ness percentage after partial save
        $upToDateDiv = $this->webDriver->findElement(WebDriverBy::xpath("//div[@id='mod-translationr10']/div[1]"));
        $upToDateText = $upToDateDiv->getText();
        $this->assertMatchesRegularExpression("/Up-to-date-ness: ([0-9]{2})%/", $upToDateText);
        preg_match("/Up-to-date-ness: ([0-9]{2})%/", $upToDateText, $matches);
        $second_percentage = $matches[1];

        // Assert that up-to-dateness increased after partial save
        $this->assertTrue($second_percentage > $first_percentage, "Up-to-dateness should have been higher than $first_percentage. It was $second_percentage.");
    }



    /**
     * @group gui
     */
    public function testMachineTranslationOfAPageCausesErrorMessageIfNotEnabled()
    {
        $this->logInIfNecessaryAs('admin');
        $this->setMachineTranslationFeatureTo('n');
        $this->openTikiPage('tiki-index.php?page=HomePage&machine_translate_to_lang=fr');
      // Get the page source
        $pageSource = $this->webDriver->getPageSource();
      // Check if the expected text is present in the page source
        $textPresent = strpos($pageSource, 'Machine Translation feature is not enabled.') !== false;
      // Assert that the text is present
        $this->assertTrue($textPresent, "System should have known that MT features are not enabled.");
    }


    /**************************************
     * Helper methods
     **************************************/

    protected function setUp(): void
    {
        $this->markTestSkipped("These tests are still too experimental, so skipping it.");
        $this->webDriver->get('http://localhost/');
        $this->current_test_db = "multilingualTestDump.sql";
        $this->restoreDBforThisTest();
    }

    public function assertLanguagePicklistHasLanguages($expAvailableLanguages)
    {
        $this->assertSelectElementContainsItems(
            "xpath=//select[@name='page' and @onchange='quick_switch_language( this )']",
            $expAvailableLanguages,
            "Language picklist was wrong. It should have contained " . $this->implodeWithKey(",", $expAvailableLanguages) . " but didn't."
        );
    }

    public function doSwitchLanguageTo($language)
    {
        // Locate the language select element
        $languageSelect = $this->webDriver->findElement(WebDriverBy::name('page'));
        // Select the desired language by its label
        $languageSelect->selectByVisibleText($language);
        // Wait for the page to load after the language switch
        $this->webDriver->wait(30)->until(
            WebDriverExpectedCondition::titleContains($language)
        );
    }

    public function assertLanguagePicklistDoesNotHaveLanguages($expAvailableLanguages)
    {
        $this->assertSelectElementDoesNotContainItems(
            "xpath=//select[@name='page' and @onchange='quick_switch_language( this )']",
            $expAvailableLanguages,
            "Language picklist was wrong. It contained " . $this->implodeWithKey(",", $expAvailableLanguages) . " but shouldn't."
        );
    }

    public function assertLanguagePicklistHasTranslateOption()
    {
        $xpathExpression = "//select[@name='page' and @onchange='quick_switch_language( this )']/option[@value='_translate_']";
        $this->assertTrue($this->webDriver->findElement(WebDriverBy::xpath($xpathExpression))->isEnabled(), "Translate option was not present.");
    }

    public function assertLanguagePicklistHasNoTranslateOption()
    {
        $xpathExpression = "//select[@name='page' and @onchange='quick_switch_language( this )']/option[@value='_translate_']";
        $this->assertFalse(
            $this->webDriver->findElement(WebDriverBy::xpath($xpathExpression))->isEnabled(),
            "Translate option was present."
        );
    }

    public function setMachineTranslationFeatureTo($y_or_n)
    {
        global $tikilib, $prefs;
        $tikilib->set_preference('feature_machine_translation', $y_or_n);
        if ($prefs['feature_machine_translation'] === 'y') {
            print "\nfeature_machine_translation ENABLED\n";
        }
    }
}
