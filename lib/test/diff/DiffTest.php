<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Tests\diff;

use TikiLib;
use TikiTestCase;

require_once __DIR__ . '/../../diff/difflib.php';

class DiffTest extends TikiTestCase
{
    /**
     * @dataProvider providerDiffByType
     *
     * @param $type
     * @param $page1
     * @param $page2
     * @param $expected
     */
    public function testDiffByType($type, $page1, $page2, $expected): void
    {
        $result = diff2($page1, $page2, $type);

        $this->assertEquals($expected, $result);
    }

    public static function providerDiffByType(): array
    {
        return [
            [
                'htmldiff',
                self::loadAndParseFile(__DIR__ . '/fixtures/page1.wiki'),
                self::loadAndParseFile(__DIR__ . '/fixtures/page2.wiki'),
                file_get_contents(__DIR__ . '/fixtures/htmldiff.html'),
            ],
            [
                'sidediff',
                file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
                file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
                file_get_contents(__DIR__ . '/fixtures/sidediff.html'),
            ],
            [
                'sidediff-char',
                file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
                file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
                file_get_contents(__DIR__ . '/fixtures/sidediff-char.html'),
            ],
            [
                'inlinediff',
                file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
                file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
                file_get_contents(__DIR__ . '/fixtures/inlinediff.html'),
            ],
            [
                'inlinediff-char',
                file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
                file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
                file_get_contents(__DIR__ . '/fixtures/inlinediff-char.html'),
            ],
            [
                'sidediff-full',
                file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
                file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
                file_get_contents(__DIR__ . '/fixtures/sidediff-full.html'),
            ],
            [
                'sidediff-full-char',
                file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
                file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
                file_get_contents(__DIR__ . '/fixtures/sidediff-full-char.html'),
            ],
            [
                'inlinediff-full',
                file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
                file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
                file_get_contents(__DIR__ . '/fixtures/inlinediff-full.html'),
            ],
            [
                'inlinediff-full-char',
                file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
                file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
                file_get_contents(__DIR__ . '/fixtures/inlinediff-full-char.html'),
            ],
            [
                'unidiff',
                file_get_contents(__DIR__ . '/fixtures/page1.wiki'),
                file_get_contents(__DIR__ . '/fixtures/page2.wiki'),
                include __DIR__ . '/fixtures/unidiff.php',
            ],
        ];
    }

    /**
     * @dataProvider providerDiffChar
     *
     * @param $orig
     * @param $final
     * @param $words
     * @param $expected
     */
    public function testDiffChar($orig, $final, $words, $expected): void
    {
        $result = diffChar($orig, $final, $words);

        $this->assertEquals($expected, $result);
    }

    public static function providerDiffChar(): array
    {
        return [
            'zeros in unchanged text' => [
                ['Released in 2020'],
                ['Released in March 2020'],
                0,
                [
                    'Released in 2020',
                    'Released in<ins class="diffchar inserted"><strong> March</strong></ins> 2020',
                ],
            ],
            'zero added' => [
                ['Tiki 2.1'],
                ['Tiki 2.10'],
                0,
                [
                    'Tiki 2.1',
                    'Tiki 2.1<ins class="diffchar inserted"><strong>0</strong></ins>',
                ],
            ],
            'zero removed' => [
                ['Tiki 2.10'],
                ['Tiki 2.1'],
                0,
                [
                    'Tiki 2.1<del class="diffchar deleted"><strong>0</strong></del>',
                    'Tiki 2.1',
                ],
            ],
            'line containing only a zero' => [
                ['0'],
                ['10'],
                0,
                [
                    '0',
                    '<ins class="diffchar inserted"><strong>1</strong></ins>0',
                ],
            ],
            'line breaks between words' => [
                ['one two', 'three'],
                ['one two', 'four'],
                1,
                [
                    'one two<br><del class="diffchar deleted"><strong>three</strong></del>',
                    'one two<br><ins class="diffchar inserted"><strong>four</strong></ins>',
                ],
            ],
        ];
    }

    public function testUnidiffKeepsZerosInChangedLines(): void
    {
        $result = diff2(
            "Tiki 31.0 is planned for October 2026

It is a Standard Term Support (STS) version.",
            "Tiki 31.0 is planned for --October 2026-- December 2026

It is a Standard Term Support (STS) version.",
            'unidiff'
        );

        $expected = [
            [
                'type' => 'diffheader',
                'old' => '1-3',
                'new' => '1-3',
            ],
            [
                'type' => 'diffdeleted',
                'data' => ['Tiki 31.0 is planned for October 2026'],
            ],
            [
                'type' => 'diffadded',
                'data' => ['Tiki 31.0 is planned for <ins class="diffchar inserted"><strong>--</strong></ins>Octo<ins class="diffchar inserted"><strong>ber 2026-- Decem</strong></ins>ber 2026'],
            ],
            [
                'type' => 'diffbody',
                'data' => ['', 'It is a Standard Term Support (STS) version.'],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    protected static function loadAndParseFile($file)
    {
        $parserLib = TikiLib::lib('parser');

        $data = file_get_contents($file);

        $parse_options = ['is_html' => false, 'noheadinc' => true, 'suppress_icons' => true, 'noparseplugins' => true];
        return $parserLib->parse_data($data, $parse_options);
    }
}
