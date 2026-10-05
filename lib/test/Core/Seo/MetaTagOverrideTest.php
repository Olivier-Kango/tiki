<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Seo;

use PHPUnit\Framework\TestCase;
use Tiki\Seo\MetaTagOverride;

class MetaTagOverrideTest extends TestCase
{
    public function testEachFieldInheritsSeparatelyAndUsesLanguage(): void
    {
        $values = MetaTagOverride::resolve(
            ['tiki.object.metatag.description' => 'Page'],
            [
                ['tiki.category.metatag.description.fr' => 'Catégorie', 'tiki.category.metatag.keywords' => 'default', 'tiki.category.metatag.keywords.fr' => 'français'],
                ['tiki.category.metatag.keywords.fr' => 'second', 'tiki.category.metatag.robots' => 'noindex'],
            ],
            'fr'
        );
        $this->assertSame(['description' => 'Page', 'keywords' => 'français', 'robots' => 'noindex'], $values);
    }

    public function testEmptyTranslationFallsBackAndLegacyRobotsPrecedeCategory(): void
    {
        $values = MetaTagOverride::resolve(
            ['tiki.object.metatag.description' => 'Default', 'tiki.object.metatag.description.en' => '  '],
            [['tiki.category.metatag.robots' => 'noindex']],
            'en',
            'nofollow'
        );
        $this->assertSame('Default', $values['description']);
        $this->assertSame('nofollow', $values['robots']);
    }

    public function testLegacyAndHtmlHeadOutputAreOverriddenWithoutChangingSchema(): void
    {
        $schema = '<script type="application/ld+json">{"description":"schema description","example":"<meta name=\'description\' content=\'example\'>"}</script>';
        foreach (['<meta name="description" content="old">', "<meta content='old' name='description' />"] as $meta) {
            $html = '<html><head>' . $meta . '<meta name="robots" content="index">' . $schema . '</head><body>Body</body></html>';
            $output = MetaTagOverride::apply($html, ['description' => 'Custom " & <', 'robots' => 'noindex']);
            $this->assertStringContainsString($schema, $output);
            $this->assertStringContainsString('content="Custom &quot; &amp; &lt;"', $output);
            $this->assertStringNotContainsString('content="old"', $output);
            $this->assertStringNotContainsString("content='old'", $output);
            $this->assertStringContainsString('<meta name="googlebot" content="noindex">', $output);
            $this->assertStringContainsString('<body>Body</body>', $output);
        }
    }

    public function testNoOverridesLeaveOutputIdentical(): void
    {
        $html = '<head><meta name="description" content="default"></head>';
        $this->assertSame($html, MetaTagOverride::apply($html, ['description' => '', 'keywords' => '', 'robots' => '']));
    }

    public function testSaveClearsOnlySubmittedFields(): void
    {
        $attributes = new class {
            public $writes = [];
            public function set_attribute(...$args) { $this->writes[] = $args; }
        };
        MetaTagOverride::save($attributes, 'category', 4, 'tiki.category.metatag', ['description' => '', 'keywords' => ' <b>bonjour</b> '], 'fr');
        $this->assertSame([
            ['category', 4, 'tiki.category.metatag.description.fr', null],
            ['category', 4, 'tiki.category.metatag.keywords.fr', 'bonjour'],
        ], $attributes->writes);
    }
}
