<?php

require_once(dirname(__FILE__) . '/tikiimporter_testcase.php');
require_once(dirname(__FILE__) . '/../../importer/tikiimporter_wiki_mediawiki.php');

class TikiImporter_Wiki_Mediawiki_Test extends TikiImporter_TestCase 
{

    protected function setUp()
    {
        $this->obj = new TikiImporter_Wiki_Mediawiki;
    }

    public function testImport()
    {
        $parsedData = 'Some text';

        $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('validateInput', 'parseData', 'insertData', 'configureParser'));
        $obj->expects($this->once())->method('validateInput');
        $obj->expects($this->once())->method('parseData')->will($this->returnValue($parsedData));
        $obj->expects($this->once())->method('insertData')->with($parsedData);
        $obj->expects($this->once())->method('configureParser');

        $this->expectOutputString("Loading and validating the XML file\n\nImportation completed!\n\n<b><a href=\"tiki-importer.php\">Click here</a> to finish the import process</b>");
        $obj->import(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');

        $this->assertTrue($obj->dom instanceof DOMDocument);
        $this->assertTrue($obj->dom->hasChildNodes());
    }

    public function testImportWithoutInternalMocking()
    {
        global $tikilib;
        $tikilib = $this->getMock('TikiLib', array('create_page', 'update_page', 'page_exists', 'remove_all_versions'));
        $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('saveAndDisplayLog'));
        $obj->expects($this->exactly(12))->method('saveAndDisplayLog');

        $expectedImportFeedback = array('totalPages' => 4, 'importedPages' => 4);
        
        $obj->import(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');

        $this->assertTrue($obj->dom instanceof DOMDocument);
        $this->assertTrue($obj->dom->hasChildNodes());
        $this->assertEquals($expectedImportFeedback, $_SESSION['tiki_importer_feedback']);
    }

    public function testImportShouldHandleAttachments()
    {
        $parsedData = 'Some text';

        $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('validateInput', 'parseData', 'insertData', 'checkRequirementsForAttachments', 'downloadAttachments', 'configureParser'));
        $obj->expects($this->once())->method('validateInput');
        $obj->expects($this->once())->method('parseData')->will($this->returnValue($parsedData));
        $obj->expects($this->once())->method('insertData')->with($parsedData);
        $obj->expects($this->once())->method('checkRequirementsForAttachments');
        $obj->expects($this->once())->method('downloadAttachments');
        $obj->expects($this->once())->method('configureParser');
        $_POST['importAttachments'] = 'on';

        $obj->import(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');
    }

    public function testImportShouldRaiseExceptionForInvalidMimeType()
    {
        require_once(dirname(__FILE__) . '/../../init/tra.php');
        $_FILES['importFile']['type'] = 'invalid/type';
        $this->setExpectedException('UnexpectedValueException');
        $this->obj->import(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');
    }

    public function testConfigureParser()
    {
        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');
        $this->obj->configureParser();
        $this->assertEquals('Text_Wiki_Mediawiki', get_class($this->obj->parser));
    }

    public function testValidateInput()
    {
        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');
        $this->assertNull($this->obj->validateInput());
    }

    public function testValidateInputShouldRaiseExceptionForInvalidXmlFile()
    {
        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(dirname(__FILE__) . '/fixtures/mediawiki_invalid.xml');
        $this->setExpectedException('DOMException');
        $this->obj->validateInput();
    }

    public function testParseData()
    {
        $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('extractInfo', 'downloadAttachment'));
        $obj->dom = new DOMDocument;
        $obj->dom->load(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');
        $obj->expects($this->exactly(4))->method('extractInfo')->will($this->returnValue(array()));
        $this->expectOutputString("\nStarting to parse pages:\n");
        $this->assertEquals(4, count($obj->parseData()));
    }

    public function testParseDataShouldPrintMessageIfErrorToParseAPageWhenExtractInfoReturnException()
    {
        $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('extractInfo', 'saveAndDisplayLog', 'downloadAttachment'));
        $obj->expects($this->exactly(4))->method('extractInfo')->will($this->throwException(new ImporterParserException('')));
        $obj->expects($this->exactly(5))->method('saveAndDisplayLog')->will($this->returnValue(''));

        $obj->dom = new DOMDocument;
        $obj->dom->load(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');

        $this->assertEquals(array(), $obj->parseData());
    }

    public function testParseDataHandleDifferentlyPagesAndFilePages()
    {
        $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('extractInfo', 'saveAndDisplayLog'));
        $obj->expects($this->exactly(4))->method('extractInfo')->will($this->returnValue(array()));
        $obj->importAttachments = true;

        $obj->dom = new DOMDocument;
        $obj->dom->load(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');
        $this->assertEquals(4, count($obj->parseData()));
   }

    public function testDownloadAttachment()
    {
        $this->obj->attachmentsDestDir = dirname(__FILE__) . '/fixtures/';
        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');
        $attachments = array('Qlandkartegt-0.11.1.tar.gz', 'Passelivre.jpg');
        $this->obj->downloadAttachments();

        $this->expectOutputString("\n\nStarting to import attachments:\nFile Qlandkartegt-0.11.1.tar.gz sucessfully imported!\nFile Passelivre.jpg sucessfully imported!\n");

        foreach ($attachments as $attachment) {
            $filePath = $this->obj->attachmentsDestDir . $attachment;
            $this->assertFileExists($filePath);
            unlink($filePath);
        }
    }

    public function testDownloadAttachmentShouldNotImportIfFileAlreadyExist()
    {
        $this->obj->attachmentsDestDir = dirname(__FILE__) . '/fixtures/';
        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(dirname(__FILE__) . '/fixtures/mediawiki_sample.xml');
        $attachments = array('Qlandkartegt-0.11.1.tar.gz', 'Passelivre.jpg');

        foreach ($attachments as $attachment) {
            $filePath = $this->obj->attachmentsDestDir . $attachment;
            fopen($filePath, 'w');
        }

        $this->obj->downloadAttachments();
        $this->expectOutputString("\n\nStarting to import attachments:\nNOT importing file Qlandkartegt-0.11.1.tar.gz as there is already a file with the same name in the destination directory (" . $this->obj->attachmentsDestDir . ")\nNOT importing file Passelivre.jpg as there is already a file with the same name in the destination directory (" . $this->obj->attachmentsDestDir . ")\n");
       
        foreach ($attachments as $attachment) {
            $filePath = $this->obj->attachmentsDestDir . $attachment;
            unlink($filePath);
        }
    }

    public function testDownloadAttachmentsShouldDisplayMessageIfNoAttachments()
    {
        $this->obj->dom = new DOMDocument;
        $this->expectOutputString("\n\nNo attachments found to import! Make sure you have created your XML file with the dumpDump.php script and with the option --uploads. This is the only way to import attachment.\n");
        $this->obj->downloadAttachments(); 
    }

    public function testDownloadAttachmentsShouldDisplayMessageIfUnableToDownloadFile()
    {
        $this->obj->attachmentsDestDir = dirname(__FILE__) . '/fixtures/';
        $this->obj->dom = new DOMDocument;
        $this->obj->dom->load(dirname(__FILE__) . '/fixtures/mediawiki_invalid_upload.xml');
        $this->obj->downloadAttachments();

        $this->expectOutputString("\n\nStarting to import attachments:\nUnable to download file Qlandkartegt-0.11.1.tar.gz. Error message was: file_get_contents(): php_network_getaddresses: getaddrinfo failed: Name or service not known\nUnable to download file Passelivre.jpg. Error message was: file_get_contents(): php_network_getaddresses: getaddrinfo failed: Name or service not known\n");
    }

    public function testExtractInfo()
    {
        $dom = new DOMDocument;
        $dom->load(dirname(__FILE__) . '/fixtures/mediawiki_page.xml');
        $expectedNames = array('Redes de ensino', 'Academia Colarossi');

        $pages = $dom->getElementsByTagName('page');

        $this->expectOutputString("Page \"Redes de ensino\" successfully parsed with 8 revisions (from a total of 8 revisions).\nPage \"Academia Colarossi\" successfully parsed with 2 revisions (from a total of 2 revisions).\n");

        $i = 0;
        foreach ($pages as $page) {
            $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('extractRevision'));
            $obj->revisionsNumber = 0;
            $obj->expects($this->atLeastOnce())->method('extractRevision')->will($this->returnValue('revision'));

            $return = $obj->extractInfo($page);
            $this->assertEquals($expectedNames[$i++], $return['name']);
            $this->assertGreaterThan(1, count($return['revisions']));
        }
    }

    public function testExtractInfoShouldNotParseMoreThanFiveRevisions()
    {
        $dom = new DOMDocument;
        $dom->load(dirname(__FILE__) . '/fixtures/mediawiki_page.xml');
        $expectedNames = array('Redes de ensino', 'Academia Colarossi');
        $expectedCalls = array(5, 2);

        $pages = $dom->getElementsByTagName('page');

        $this->expectOutputString("Page \"Redes de ensino\" successfully parsed with 5 revisions (from a total of 8 revisions).\nPage \"Academia Colarossi\" successfully parsed with 2 revisions (from a total of 2 revisions).\n");

        $i = 0;
        foreach ($pages as $page) {
            $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('extractRevision'));
            $obj->revisionsNumber = 5;
            $obj->expects($this->exactly($expectedCalls[$i]))->method('extractRevision')->will($this->returnValue('revision'));

            $return = $obj->extractInfo($page);
            $this->assertEquals($expectedNames[$i], $return['name']);
            $this->assertEquals($expectedCalls[$i], count($return['revisions']));
            $i++;
        }
    }

    public function testExtractInfoShouldParseAllRevisions()
    {
        $dom = new DOMDocument;
        $dom->load(dirname(__FILE__) . '/fixtures/mediawiki_page.xml');
        $expectedNames = array('Redes de ensino', 'Academia Colarossi');
        $expectedCalls = array(8, 2);

        $pages = $dom->getElementsByTagName('page');

        $this->expectOutputString("Page \"Redes de ensino\" successfully parsed with 8 revisions (from a total of 8 revisions).\nPage \"Academia Colarossi\" successfully parsed with 2 revisions (from a total of 2 revisions).\n");

        $i = 0;
        foreach ($pages as $page) {
            $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('extractRevision'));
            $obj->revisionsNumber = 0;
            $obj->expects($this->exactly($expectedCalls[$i]))->method('extractRevision')->will($this->returnValue('revision'));

            $return = $obj->extractInfo($page);
            $this->assertEquals($expectedNames[$i], $return['name']);
            $this->assertEquals($expectedCalls[$i], count($return['revisions']));
            $i++;
        }
    }

    public function testExtractInfoShouldAlsoParseAllRevisions()
    {
        $dom = new DOMDocument;
        $dom->load(dirname(__FILE__) . '/fixtures/mediawiki_page.xml');
        $expectedNames = array('Redes de ensino', 'Academia Colarossi');
        $expectedCalls = array(8, 2);

        $pages = $dom->getElementsByTagName('page');

        $this->expectOutputString("Page \"Redes de ensino\" successfully parsed with 8 revisions (from a total of 8 revisions).\nPage \"Academia Colarossi\" successfully parsed with 2 revisions (from a total of 2 revisions).\n");

        $i = 0;
        foreach ($pages as $page) {
            $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('extractRevision'));
            $obj->revisionsNumber = 15;
            $obj->expects($this->exactly($expectedCalls[$i]))->method('extractRevision')->will($this->returnValue('revision'));

            $return = $obj->extractInfo($page);
            $this->assertEquals($expectedNames[$i], $return['name']);
            $this->assertEquals($expectedCalls[$i], count($return['revisions']));
            $i++;
        }
    }

    public function testExtractInfoShouldPrintErrorMessageIfProblemWithRevision()
    {
        $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('extractRevision'));
        $obj->revisionsNumber = 0;
        $obj->expects($this->exactly(10))->method('extractRevision')->will($this->onConsecutiveCalls(array(), array(), $this->throwException(new ImporterParserException)));

        $dom = new DOMDocument;
        $dom->load(dirname(__FILE__) . '/fixtures/mediawiki_page.xml');
        $pages = $dom->getElementsByTagName('page');

        $this->expectOutputString("Error while parsing revision 3 of the page \"Redes de ensino\". Or there is a problem on the page syntax or on the Text_Wiki parser (the parser used by the importer).\nPage \"Redes de ensino\" successfully parsed with 7 revisions (from a total of 8 revisions).\nPage \"Academia Colarossi\" successfully parsed with 2 revisions (from a total of 2 revisions).\n");

        foreach ($pages as $page) {
            $obj->extractInfo($page);
        }
    }

    public function testExtractInfoShouldThrowExceptionIfUnableToParseAllRevisionsOfPage()
    {
        $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('extractRevision', 'saveAndDisplayLog'));
        $obj->revisionsNumber = 0;
        $obj->expects($this->exactly(8))->method('extractRevision')->will($this->throwException(new ImporterParserException));
        $obj->expects($this->exactly(8))->method('saveAndDisplayLog')->will($this->returnValue(''));

        $dom = new DOMDocument;
        $dom->load(dirname(__FILE__) . '/fixtures/mediawiki_page.xml');
        $pages = $dom->getElementsByTagName('page');

        foreach ($pages as $page) {
            $this->setExpectedException('Exception');
            $this->assertNull($obj->extractInfo($page));
        }
    }

    public function testExtractRevision()
    {
        $dom = new DOMDocument;
        $dom->load(dirname(__FILE__) . '/fixtures/mediawiki_revision.xml');
        $expectedResult = array(
            array('minor' => false, 'lastModif' => 1139119907, 'ip' => '201.6.123.86', 'user' => 'anonymous', 'comment' => 'fim da tradução', 'data' => 'Some text'),
            array('minor' => false, 'lastModif' => 1176517303, 'user' => 'Girino', 'ip' => '0.0.0.0', 'comment' => 'Revert to revision 5661385', 'data' => 'Some text'));
        $extractContributorReturn = array(
            array('ip' => '201.6.123.86', 'user' => 'anonymous'),
            array('user' => 'Girino', 'ip' => '0.0.0.0'));

        $revisions = $dom->getElementsByTagName('revision');

        $i = 0;
        foreach ($revisions as $revision) {
            $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('convertMarkup', 'extractContributor'));
            $obj->expects($this->once())->method('convertMarkup')->will($this->returnValue('Some text'));
            $obj->expects($this->once())->method('extractContributor')->will($this->returnValue($extractContributorReturn[$i]));

            $this->assertEquals($expectedResult[$i++], $obj->extractRevision($revision));
       }
    }

    public function testExtractRevisionShouldRaiseExceptionForInvalidSyntax()
    {
        // for PEAR_Error
        require_once('PEAR.php');

        $obj = $this->getMock('TikiImporter_Wiki_Mediawiki', array('convertMarkup', 'extractContributor'));
        $obj->expects($this->once())->method('convertMarkup')->will($this->returnValue(new PEAR_Error('some message')));
        $obj->expects($this->once())->method('extractContributor')->will($this->returnValue(array()));

        $dom = new DOMDocument;
        $dom->load(dirname(__FILE__) . '/fixtures/mediawiki_revision_invalid_syntax.xml');
        $revisions = $dom->getElementsByTagName('revision');

        foreach ($revisions as $revision) {
            $this->setExpectedException('ImporterParserException');
            $this->assertNull($obj->extractRevision($revision));
        }
    }

    public function testExtractContributor()
    {
        $dom = new DOMDocument;
        $dom->load(dirname(__FILE__) . '/fixtures/mediawiki_contributor.xml');
        $expectedResult = array(
            array('user' => 'SomeUserName', 'ip' => '0.0.0.0'),
            array('ip' => '163.117.200.166', 'user' => 'anonymous'),
            array('user' => 'OtherUserName', 'ip' => '0.0.0.0')
        );
        $contributors = $dom->getElementsByTagName('contributor');

        $i = 0;
        foreach ($contributors as $contributor) {
            $this->assertEquals($expectedResult[$i++], $this->obj->extractContributor($contributor));
        }
    }

    // TODO: find a way to mock the Text_Wiki object inside convertMakup()
    public function testConvertMarkup()
    {
        $this->obj->dom = new DOMDocument;
        $this->obj->configureParser();
        $mediawikiText = '[[someWikiLink]]';
        $expectedResult = "((someWikiLink))\n\n";
        $this->assertEquals($expectedResult, $this->obj->convertMarkup($mediawikiText));
    }

    public function testConvertMarkupShouldReturnNullIfEmptyMediawikiText()
    {
        $this->obj->dom = new DOMDocument;
        $this->obj->configureParser();
        $mediawikiText = '';
        $this->assertNull($this->obj->convertMarkup($mediawikiText));
    }
}

?>
