<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\WikiPlugins;

use Tiki\Sections;
use TikiLib;
use TikiTestCase;

/**
 * Section and plugin edit icons of a page using PluginInclude must only target the page's own content.
 */
class WikiPluginIncludeTest extends TikiTestCase
{
    private const PARENT = 'WikiPluginIncludeTest_Parent';
    private const CHILD = 'WikiPluginIncludeTest_Child';
    private const GRANDCHILD = 'WikiPluginIncludeTest_Grandchild';

    private array $testPages = [];
    private array $origPrefs = [];
    private $origUser;
    private $origPage;
    private $origEdit;
    private ?string $origSection;

    protected function setUp(): void
    {
        global $prefs, $user, $page, $tiki_p_edit;

        $this->origUser = $user;
        $this->origPage = $page;
        $this->origEdit = $tiki_p_edit;
        $this->origSection = Sections::getCurrentSection();

        $testPrefs = [
            'wikiplugin_include' => 'y',
            'wikiplugin_div' => 'y',
            'wiki_edit_section' => 'y',
            'wiki_edit_section_level' => 0,
            'wiki_edit_plugin' => 'y',
            'wiki_edit_plugin_mode' => 'icon',
            'wiki_edit_icons_toggle' => 'n',
            'flaggedrev_approval' => 'n',
        ];
        foreach ($testPrefs as $name => $value) {
            $this->origPrefs[$name] = $prefs[$name] ?? null;
            $prefs[$name] = $value;
        }

        $user = 'admin';
        $page = self::PARENT;
        $tiki_p_edit = 'y';
        Sections::setCurrentSection(Sections::SECTION_WIKI_PAGE);

        $this->createPage(self::GRANDCHILD, "!Grandchild Heading\n{DIV(class=\"grandchild-div\")}Grandchild div{DIV}\n");
        $this->createPage(
            self::CHILD,
            "!Child Heading A\nChild text.\n{DIV(class=\"child-div\")}Child div{DIV}\n!Child Heading B\n{include page=\"" . self::GRANDCHILD . "\"}\n"
        );
    }

    protected function tearDown(): void
    {
        global $prefs, $user, $page, $tiki_p_edit;

        foreach ($this->testPages as $name) {
            (new \TestHelpers())->removeAllVersions($name);
        }
        foreach ($this->origPrefs as $name => $value) {
            $prefs[$name] = $value;
        }
        $user = $this->origUser;
        $page = $this->origPage;
        $tiki_p_edit = $this->origEdit;
        if ($this->origSection !== null) {
            Sections::setCurrentSection($this->origSection);
        }
    }

    public function testIncludedHeadingsGetNoSectionEditIcon(): void
    {
        $html = $this->parseParent('{include page="' . self::CHILD . '"}');

        $this->assertEquals([1, 2], $this->sectionEditNumbers($html), 'Only the 2 headings of the page are editable, numbered as in its source');
        $this->assertStringContainsString('id="Child_Heading_A"', $html, 'The included headings are still rendered');
        $this->assertStringContainsString('id="Grandchild_Heading"', $html);
        $this->assertDoesNotMatchRegularExpression('/[\x{E000}-\x{E002}]/u', $html);
    }

    public function testIncludedPluginsGetNoEditIcon(): void
    {
        $html = $this->parseParent('{include page="' . self::CHILD . '"}');

        $divs = $this->pluginEditIndexes($html, 'div');
        $this->assertCount(2, $divs, 'Only the 2 DIV plugins of the page are editable');
        $this->assertSame($divs[0] + 1, $divs[1], 'The included DIV plugins are not counted between the page ones');
        $this->assertCount(1, $this->pluginEditIndexes($html, 'include'), 'The include within the included page is not editable');
    }

    public function testIncludeWithinIncludedPageHasNoReplaceIcon(): void
    {
        $html = $this->parseParent('{include page="' . self::CHILD . '" page_replace_icon="y"}');

        $this->assertSame(1, substr_count($html, 'wikiplugin-include-replace'));
    }

    public function testPreParsedIncludedPageDoesNotShiftNumbering(): void
    {
        $html = $this->parseParent('{include page="' . self::CHILD . '" parse_included_page="y"}');

        $this->assertEquals([1, 2], $this->sectionEditNumbers($html));
        $divs = $this->pluginEditIndexes($html, 'div');
        $this->assertCount(2, $divs);
        $this->assertSame($divs[0] + 1, $divs[1]);
    }

    private function parseParent(string $include): string
    {
        $data = "!Parent Intro\n{DIV(class=\"parent-div-1\")}Parent div one{DIV}\n$include\n!Parent Two\n{DIV(class=\"parent-div-2\")}Parent div two{DIV}\n";
        $this->createPage(self::PARENT, $data);

        return TikiLib::lib('parser')->parse_data($data, ['page' => self::PARENT]);
    }

    private function createPage(string $name, string $data): void
    {
        (new \TestHelpers())->createPage($name, 0, $data);
        $this->testPages[] = $name;
    }

    /**
     * @return int[] the hdr numbers of the section edit links, without the whole page one (hdr=0)
     */
    private function sectionEditNumbers(string $html): array
    {
        preg_match_all('/hdr=(\d+)/', $html, $matches);
        return array_values(array_filter(array_map('intval', $matches[1])));
    }

    /**
     * @return int[] the plugin indexes the edit icons of a plugin type would save to
     */
    private function pluginEditIndexes(string $html, string $type): array
    {
        preg_match_all('/<a id="plugin-edit-' . $type . '(\d+)"[^>]* class="editplugin"/', $html, $matches);
        return array_map('intval', $matches[1]);
    }
}
