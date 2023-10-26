<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once(__DIR__ . '/CollaborativeMultilingualTerminologyTest.php');
require_once(__DIR__ . '/ListPagesTest.php');
require_once(__DIR__ . '/MultilingualTest.php');
require_once(__DIR__ . '/MultilinguallibTest.php');
require_once(__DIR__ . '/SearchTest.php');
require_once(__DIR__ . '/TikiLibrariesAccessTest.php');


class AcceptanceTests_AllTests
{
    public static function main()
    {
        PHPUnit\TextUI\TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit\Framework\TestSuite('AcceptanceTestsSuite');

        $suite->addTestSuite('AcceptanceTests_CollaborativeMultilingualTerminologyTest');
        $suite->addTestSuite('AcceptanceTests_ListPagesTest');
        $suite->addTestSuite('AcceptanceTests_MultilingualTest');
        $suite->addTestSuite('AcceptanceTests_MultilinguallibTest');
        $suite->addTestSuite('AcceptanceTests_SearchTest');
        $suite->addTestSuite('AcceptanceTests_TikiLibrariesAccessTest');
        return $suite;
    }
}
