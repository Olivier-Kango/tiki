<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Seo;

use PHPUnit\Framework\TestCase;
use Smarty\Filter\FilterInterface;
use Smarty\Smarty;
use Smarty\Template;
use SmartyTiki\Extension\SmartyTikiExtension;
use Tiki\Seo\SocialDescriptionFallback;
use Tiki\Seo\SocialMetaTagOverride;

class SocialDescriptionFallbackTest extends TestCase
{
    private function head(string $description, string $social = ''): string
    {
        return '<html><head><meta name="description" content="' . $description . '">'
            . $social . '</head><body>Unchanged</body></html>';
    }

    private function social(string $og, string $twitter): string
    {
        return '<meta property="og:description" content="' . $og . '">'
            . '<meta name="twitter:description" content="' . $twitter . '">';
    }

    private function assertSocial(string $html, string $og, string $twitter): void
    {
        $this->assertStringContainsString('<meta property="og:description" content="' . $og . '">', $html);
        $this->assertStringContainsString('<meta name="twitter:description" content="' . $twitter . '">', $html);
        $this->assertSame(1, substr_count($html, 'property="og:description"'));
        $this->assertSame(1, substr_count($html, 'name="twitter:description"'));
    }

    public function testMatchingFallbacksFollowTheChangedDescription(): void
    {
        $social = $this->social('Site', 'Site');
        $after = $this->head('Page', $social);
        $html = SocialDescriptionFallback::synchronize($this->head('Site', $social), $after);
        $this->assertSocial($html, 'Page', 'Page');
        $this->assertStringContainsString('<meta name="description" content="Page">', $html);
        $this->assertStringContainsString('<body>Unchanged</body>', $html);
    }

    public function testMissingAndEmptySocialDescriptionsUseTheNewDescription(): void
    {
        foreach (['', $this->social('', '  ')] as $social) {
            $html = SocialDescriptionFallback::synchronize($this->head('Site', $social), $this->head('Page', $social));
            $this->assertSocial($html, 'Page', 'Page');
        }
    }

    public function testDistinctSocialDescriptionsArePreservedIndependently(): void
    {
        foreach (['og', 'twitter', 'both'] as $custom) {
            $og = $custom !== 'twitter' ? 'Custom OG' : 'Site';
            $twitter = $custom !== 'og' ? 'Custom Twitter' : 'Site';
            $social = $this->social($og, $twitter);
            $html = SocialDescriptionFallback::synchronize($this->head('Site', $social), $this->head('Page', $social));
            $this->assertSocial($html, $og === 'Site' ? 'Page' : $og, $twitter === 'Site' ? 'Page' : $twitter);
        }
    }

    public function testInheritedDuplicatesAreRemovedAlongsideCustomSocialValues(): void
    {
        $social = $this->social('Site', 'Site') . $this->social('Custom OG', 'Custom Twitter');
        $html = SocialDescriptionFallback::synchronize($this->head('Site', $social), $this->head('Page', $social));
        $this->assertSocial($html, 'Custom OG', 'Custom Twitter');
    }

    public function testGeneratedDuplicatesAreReplacedWithOneTagPerPlatform(): void
    {
        $social = $this->social('Site', 'Site') . $this->social('Site', 'Site');
        $html = SocialDescriptionFallback::synchronize($this->head('Site', $social), $this->head('Page', $social));
        $this->assertSocial($html, 'Page', 'Page');
    }

    public function testTranslationAndEntitiesAreEscapedOnce(): void
    {
        $social = "<META content='Site &#38; company' PROPERTY='OG:DESCRIPTION' />"
            . "<meta content='Site &amp; company' NAME='twitter:description'>";
        $description = 'Nous contacter : équipe &quot;Tiki&quot; &amp; données &lt; &gt;';
        $html = SocialDescriptionFallback::synchronize($this->head('Site &amp; company', $social), $this->head($description, $social));
        $this->assertSocial($html, $description, $description);
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }

    public function testScriptsCommentsAndOtherHeadMetadataRemainUnchanged(): void
    {
        $protected = '<!-- <meta name="description" content="Fake"> -->'
            . '<script type="application/ld+json">{"description":"Schema","example":"<meta property=\'og:description\' content=\'Site\'>"}</script>'
            . '<script>const tag = "<meta name=\'description\' content=\'Fake\'>";</script>'
            . '<style>/* <meta property="og:description" content="Fake"> */</style>'
            . '<title>Keep title</title><link rel="canonical" href="https://example.test/page">'
            . '<meta name="robots" content="noindex"><meta name="keywords" content="existing">';
        $social = $this->social('Site', 'Site');
        $html = SocialDescriptionFallback::synchronize($this->head('Site', $protected . $social), $this->head('Page', $protected . $social));
        $this->assertStringContainsString($protected, $html);
        $this->assertStringContainsString('<meta property="og:description" content="Page">', $html);
        $this->assertStringContainsString('<meta name="twitter:description" content="Page">', $html);
    }

    public function testUnquotedSocialAttributesAreRecognized(): void
    {
        $social = '<meta content=Site property=og:description><meta name=twitter:description content=Custom>';
        $html = SocialDescriptionFallback::synchronize($this->head('Site', $social), $this->head('Page', $social));
        $this->assertStringContainsString('<meta property="og:description" content="Page">', $html);
        $this->assertStringContainsString('<meta name=twitter:description content=Custom>', $html);
        $this->assertSame(1, substr_count($html, 'twitter:description'));
    }

    public function testUnchangedDescriptionLeavesOutputByteForByte(): void
    {
        $before = $this->head('Site', $this->social('Site', 'Site'));
        $after = str_replace('</head>', '<meta name="keywords" content="New keywords"></head>', $before);
        $this->assertSame($after, SocialDescriptionFallback::synchronize($before, $after));
        $this->assertSame($before, SocialDescriptionFallback::synchronize($before, $before));
    }

    public function testMissingOrAmbiguousNewDescriptionIsNotGuessed(): void
    {
        foreach (
            ['<head></head>', '<head><meta name="description" content=" "></head>',
            '<head><meta name="description" content="One"><meta name="description" content="Two"></head>',
            '{"description":"JSON response"}'] as $after
        ) {
            $this->assertSame($after, SocialDescriptionFallback::synchronize($this->head('Site'), $after));
        }
    }

    public function testAbsentOldDescriptionDoesNotEraseExistingSocialValues(): void
    {
        $social = $this->social('Custom OG', 'Custom Twitter');
        $html = SocialDescriptionFallback::synchronize('<head>' . $social . '</head>', $this->head('Page', $social));
        $this->assertSocial($html, 'Custom OG', 'Custom Twitter');
    }

    public function testExtensionRegistersTheExtendedFilterOnlyWhenItsDependencyExists(): void
    {
        $extension = new SmartyTikiExtension();
        foreach ([1, 2] as $call) {
            $filters = $extension->getOutputFilters();
            $overrideClass = 'Tiki\\Seo\\MetaTagOverride';
            $expected = class_exists($overrideClass) && is_subclass_of($overrideClass, FilterInterface::class) ? 1 : 0;
            $this->assertCount($expected, array_filter($filters, static function ($filter) {
                return $filter instanceof SocialMetaTagOverride;
            }));
            $this->assertCount(0, array_filter($filters, static function ($filter) {
                return get_class($filter) === 'Tiki\\Seo\\MetaTagOverride';
            }));
        }
    }

    public function testAdapterRunsItsDependencyOnceAndPreservesUnchangedOutput(): void
    {
        $social = $this->social('Site', 'Site');
        $before = $this->head('Site', $social);
        $underlying = new class implements FilterInterface {
            public int $calls = 0;
            public string $output = '';

            public function filter($code, Template $template)
            {
                ++$this->calls;
                return $this->output;
            }
        };
        $adapter = new SocialMetaTagOverride($underlying);
        $template = (new Smarty())->createTemplate('string:unused');
        $underlying->output = $this->head('Page', $social);
        $this->assertSocial($adapter->filter($before, $template), 'Page', 'Page');
        $this->assertSame(1, $underlying->calls);
        $underlying->output = $before;
        $this->assertSame($before, $adapter->filter($before, $template));
        $this->assertSame(2, $underlying->calls);
    }
}
