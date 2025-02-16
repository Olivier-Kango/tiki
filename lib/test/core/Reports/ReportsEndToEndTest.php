<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('lib/test/TikiDatabaseTestCase.php');

class Reports_EndToEndTest extends TikiDatabaseTestCase
{
    public $dt;
    public $mail;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\TikiLib
     */
    public $tikilib;
    public $overrideLibs;
    public $obj;
    protected function setUp(): void
    {
        $this->markTestSkipped('Strangely enough, this loads two different classes if TikiMail');
        $this->dt = new DateTime();
        $this->dt->setTimestamp(strtotime('2012-03-27 15:55:16'));

        $this->mail = $this->createMock('TikiMail');

        $this->tikilib = $this->createMock('TikiLib');

        $this->overrideLibs = new TestableTikiLib();
        $this->overrideLibs->overrideLibs(['calendar' => $this->createMock('MockCalendarLib')]);

        $tikiPrefs = ['short_date_format' => '%Y-%m-%d'];

        $this->obj = Reports_Factory::build('Reports_Manager', $this->dt, $this->mail, $this->tikilib, $tikiPrefs);

        parent::setUp();
    }

    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(__DIR__ . '/fixtures/end_to_end_test_dataset.xml');
    }

    public function testReportsEndToEndShouldUpdateLastReportFieldInUsersTable()
    {
        $this->obj->send();

        $expectedUserReportsTable = $this->createMySQLXmlDataSet(__DIR__ . '/fixtures/end_to_end_test_result_dataset.xml')
            ->getTable('tiki_user_reports');

        $queryUserReportsTable = $this->getConnection()->createQueryTable('tiki_user_reports', 'SELECT * FROM tiki_user_reports');
        $this->assertTablesEqual($expectedUserReportsTable, $queryUserReportsTable);
    }

    public function testReportsEndToEndShouldCleanReportsCacheAfterSendingMessages()
    {
        $this->obj->send();

        $expectedCacheTable = $this->createMySQLXmlDataSet(__DIR__ . '/fixtures/end_to_end_test_result_dataset.xml')
            ->getTable('tiki_user_reports_cache');
        $queryCacheTable = $this->getConnection()->createQueryTable('tiki_user_reports_cache', 'SELECT * FROM tiki_user_reports_cache');
        $this->assertEquals(0, $queryCacheTable->getRowCount());
    }

    public function testReportsEndToEndShouldSendEmail()
    {
        $this->mail->expects($this->once())->method('setUser')->with('test');
        $this->mail->expects($this->once())->method('setHtml')->with(file_get_contents(__DIR__ . '/fixtures/email_body.txt'));
        $this->mail->expects($this->once())->method('setSubject')->with('Report from 2012-03-27 (20 changes)');
        $this->mail->expects($this->once())->method('buildMessage');
        $this->mail->expects($this->once())->method('send')->with(['test@test.com']);

        $this->obj->send();
    }
}
