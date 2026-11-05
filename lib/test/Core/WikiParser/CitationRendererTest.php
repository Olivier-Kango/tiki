<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\WikiParser;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Parser\MarkdownParser;
use Tiki\Process\Process;
use Tiki\WikiParser\Markdown\Node\Citation;
use Tiki\WikiParser\Markdown\Renderer\CitationRenderer;
use Tiki\WikiParser\Markdown\ZoteroCitationExtension;

/**
 * Tests the Pandoc Markdown that CitationRenderer generates from {@key, locator} citations,
 * and that Pandoc renders the labelled locators as locators.
 *
 * @group unit
 */

class CitationRendererTest extends \TikiTestCase
{
    public static function citationProvider(): array
    {
        return [
            'no locator'              => ['{@doe2020}', '[@doe2020]'],
            'bare page'               => ['{@doe2020, 42}', '[@doe2020, 42]'],
            'p.'                      => ['{@doe2020, p. 42}', '[@doe2020, 42]'],
            'pp. range'               => ['{@doe2020, pp. 42-45}', '[@doe2020, 42-45]'],
            'sec.'                    => ['{@doe2020, sec. 2}', '[@doe2020, section 2]'],
            'chap.'                   => ['{@doe2020, chap. 3}', '[@doe2020, chapter 3]'],
            'ch.'                     => ['{@doe2020, ch. 3}', '[@doe2020, chapter 3]'],
            'ch without dot'          => ['{@doe2020, ch 3}', '[@doe2020, chapter 3]'],
            'chs. range'              => ['{@doe2020, chs. 3-4}', '[@doe2020, chapter 3-4]'],
            'word starting with ch'   => ['{@doe2020, check 3}', '[@doe2020, check 3]'],
            'suppress author'         => ['{-@doe2020, ch. 3}', '[-@doe2020, chapter 3]'],
            'multiple keys'           => ['{@doe2020, ch. 3; @roe2019, p. 7}', '[@doe2020, chapter 3; @roe2019, 7]'],
        ];
    }

    /**
     * @dataProvider citationProvider
     */
    public function testCitationToPandocMarkdown(string $input, string $expected): void
    {
        $citations = $this->parseCitations($input);

        $this->assertSame($expected, (new CitationRenderer())->citationsToPandocMarkdown($citations));
    }

    public static function pandocLocatorProvider(): array
    {
        return [
            'chapter'       => ['chapter', '3', 'Doe 2020, chap. 3'],
            'chapter range' => ['chapter', '3-4', 'Doe 2020, chaps. 3–4'],
            'section'       => ['section', '2', 'Doe 2020, sec. 2'],
            'page'          => ['page', '42', 'Doe 2020, 42'],
        ];
    }

    /**
     * Pandoc keeps "[@key [chapter 3]]" as literal text; "[@key, chapter 3]" must render as a locator.
     * Runs the pandoc binary, so it is skipped when pandoc is not installed.
     *
     * @group integration
     * @dataProvider pandocLocatorProvider
     */
    public function testPandocRendersLocator(string $label, string $locator, string $expected): void
    {
        $pandoc = $this->findPandoc();
        if ($pandoc === null) {
            $this->markTestSkipped('pandoc is not installed');
        }

        $markdown = (new CitationRenderer())->citationsToPandocMarkdown([
            ['key' => 'doe2020', 'locator' => $locator, 'label' => $label, 'suppress_author' => false],
        ]);

        // Pandoc picks the bibliography format from the extension, so give the tempnam() file a .json name.
        $tempFile = tempnam(sys_get_temp_dir(), 'citation-test-');
        $bibliography = $tempFile . '.json';
        if (! rename($tempFile, $bibliography)) {
            unlink($tempFile);
            $this->fail('Could not create temporary bibliography file');
        }

        try {
            file_put_contents($bibliography, json_encode([[
                'id' => 'doe2020',
                'type' => 'book',
                'title' => 'A Book',
                'author' => [['family' => 'Doe', 'given' => 'Jane']],
                'issued' => ['date-parts' => [[2020]]],
            ]]));

            // No --csl: Pandoc's built-in Chicago author-date style.
            $process = new Process(
                [$pandoc, '--citeproc', '--bibliography=' . $bibliography, '-f', 'markdown', '-t', 'plain'],
                null,
                null,
                $markdown,
                null
            );
            $process->run();
        } finally {
            unlink($bibliography);
        }

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());

        $output = preg_replace('/\s+/u', ' ', $process->getOutput());
        $this->assertStringContainsString('(' . $expected . ')', $output);
        $this->assertStringNotContainsString('[' . $label, $output);
    }

    private function parseCitations(string $input): array
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new ZoteroCitationExtension());

        $document = (new MarkdownParser($environment))->parse($input);

        foreach ($document->iterator() as $node) {
            if ($node instanceof Citation) {
                return $node->getCitations();
            }
        }

        $this->fail('No citation node parsed from: ' . $input);
    }

    private function findPandoc(): ?string
    {
        foreach (explode(PATH_SEPARATOR, getenv('PATH') ?: '') as $dir) {
            $candidate = $dir . DIRECTORY_SEPARATOR . 'pandoc';
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
