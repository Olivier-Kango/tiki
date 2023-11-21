<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 *  Test wiki page rendering options
 */

namespace test\Tiki\Lib\wiki;

use PHPUnit\Framework\TestCase;
use TikiLib;

class EditLibTest extends TestCase
{
    public function testConvertWikiSyntaxCreatesTikiHeadingFromMarkdownWithContentNextLine()
    {
        $editlib = TikiLib::lib('edit');

        $input = "{tikiheading level=3 options=#}the heading goes here{/tikiheading}"
            . "\nAnd this comes after the heading";

        $expected = "###$ the heading goes here"
            . "\nAnd this comes after the heading";

        $converted = $editlib->convertWikiSyntax($input, "markdown");

        $this->assertSame($expected, $converted);
    }

    public function testConvertWikiSyntaxCreatesTikiHeadingFromMarkdownWithContentAfterIt()
    {
        $editlib = TikiLib::lib('edit');

        $input = "{tikiheading level=3 options=#}the heading goes here{/tikiheading}"
            . " And this comes after the heading";

        $expected = "###$ the heading goes here"
            . " And this comes after the heading";

        $converted = $editlib->convertWikiSyntax($input, "markdown");

        $this->assertSame($expected, $converted);
    }
}
