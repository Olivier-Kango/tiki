<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @group unit
 *
 */

use org\bovigo\vfs\vfsStream;
use Tiki\FileGallery\File;
use Tiki\FileGallery\FileWrapper;
use Tiki\Lib\Image\ImageFileHandler;

class Tiki_FileGallery_FileTest extends TikiTestCase
{
    public $oldPrefs;
    protected function setUp(): void
    {
        global $prefs;
        $this->oldPrefs = $prefs;
        parent::setUp();
        TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();
        $this->ensureDefaultGalleryExists();
    }

    protected function tearDown(): void
    {
        global $prefs;
        $prefs = $this->oldPrefs;
        TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();
    }

    public function testInstantiation()
    {
        $file = new File();
        $this->assertEquals(0, $file->fileId);
        $this->assertFalse($file->exists());
    }

    public function testInitialization()
    {
        $file = new File(['filename' => 'test.zip']);
        $this->assertEquals('test.zip', $file->filename);
    }

    public function testLoading()
    {
        $file = new File(['filename' => 'test.zip']);
        $filesTable = TikiLib::lib('filegal')->table('tiki_files');
        $fileId = $filesTable->insert($file->getParamsForDb());

        $file = File::id($fileId);
        $this->assertEquals('test.zip', $file->filename);

        $filesTable->delete(['fileId' => $fileId]);
    }

    public function testCorrectWrapper()
    {
        global $prefs;

        $file = new File(['filename' => 'test.txt', 'data' => 'test content', 'path' => '']);
        $prefs['fgal_use_db'] = 'y';

        $this->assertInstanceOf(FileWrapper\PreloadedContent::class, $file->getWrapper());

        TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();

        $file = new File(['filename' => 'test.txt', 'data' => 'test content', 'path' => 'abcdtest']);
        $prefs['fgal_use_db'] = 'n';
        $prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();

        $this->assertInstanceOf(FileWrapper\PhysicalFile::class, $file->getWrapper());
        $this->assertEmpty($file->data);
    }

    public function testReplaceContents()
    {
        global $prefs;

        $file = new File(['filename' => 'test.txt', 'data' => 'test content', 'path' => '']);
        $prefs['fgal_use_db'] = 'y';

        $file->replaceContents('updated content');
        $this->assertEquals('updated content', $file->data);
        $this->assertEmpty($file->path);

        TikiLib::lib('filegal')->clearLoadedGalleryDefinitions();

        $file = new File(['filename' => 'test.txt', 'data' => '', 'path' => 'abcdtest']);
        $prefs['fgal_use_db'] = 'n';
        $prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();
        file_put_contents($prefs['fgal_use_dir'] . '/' . $file->path, 'test content');

        $file->replaceContents('updated content');
        $this->assertEquals('updated content', file_get_contents($prefs['fgal_use_dir'] . '/' . $file->path));
        $this->assertEmpty($file->data);
    }

    private function testImageConversion($format, $method)
    {
        global $prefs;

        $prefs['fgal_use_db'] = 'n';
        $prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();
        $imagepath = $prefs['fgal_use_dir'] . '/testfile.' . $format;

        file_put_contents($imagepath, 'valid ' . $format . ' content');

        $imageHandlerMock = $this->createMock(ImageFileHandler::class);
        $imageHandlerMock->method($method)
            ->with($imagepath)
            ->willReturn('converted jpeg content');

        try {
            $convertedContent = $imageHandlerMock->$method($imagepath);
            $this->assertNotEmpty($convertedContent);

            $finfo = $this->createMock(finfo::class);
            $finfo->method('buffer')->willReturn('image/jpeg');
            $mimeType = $finfo->buffer($convertedContent);
            $this->assertEquals('image/jpeg', $mimeType, 'The converted file is not a valid JPG.');
        } catch (Exception $e) {
            $this->fail('Error during ' . strtoupper($format) . ' conversion: ' . $e->getMessage());
        }
    }

    public function testHeicConversion()
    {
        $this->testImageConversion('heic', 'convertHeicToJpg');
    }

    public function testJxlConversion()
    {
        $this->testImageConversion('jxl', 'convertJxlToJpg');
    }

    public function testProcessFile()
    {
        global $prefs;

        $prefs['fgal_use_db'] = 'n';
        $prefs['fgal_use_dir'] = vfsStream::setup(uniqid('', true), null)->url();

        $this->processFileTestHelper('testfile.heic', 'valid heic content', 'image/jpeg');
        $this->processFileTestHelper('testfile.jpg', 'valid jpg content', 'valid jpg content');
    }

    private function processFileTestHelper($filename, $fileContent, $expectedMimeType)
    {
        global $prefs;

        $imagepath = $prefs['fgal_use_dir'] . '/' . $filename;
        file_put_contents($imagepath, $fileContent);

        $wrapperMock = $this->createMock(FileWrapper\PhysicalFile::class);
        $wrapperMock->method('getReadableFile')->willReturn($imagepath);
        $wrapperMock->method('getContents')->willReturn($fileContent);

        $imageHandler = new ImageFileHandler();
        $convertedContent = $imageHandler->processFile(['filename' => $filename], $wrapperMock);
        $this->assertNotEmpty($convertedContent);

        $finfo = $this->createMock(finfo::class);
        $finfo->method('buffer')->willReturn($expectedMimeType);
        $mimeType = $finfo->buffer($convertedContent);
        $this->assertEquals($expectedMimeType, $mimeType, 'The converted file is not a valid JPG.');
    }
}
