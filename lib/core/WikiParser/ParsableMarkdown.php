<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DescriptionList\DescriptionListExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Table\TableRenderer;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;

use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\Block\Renderer\FencedCodeRenderer;

class WikiParser_ParsableMarkdown extends WikiParser_Parsable
{
    public function wikiParse($data, $noparsed = [])
    {
        global $prefs;

        // let's define our configurationon
        $config = [
            // allow html because of the wiki plugins and any other allowed thing that generate html
            // we can't strip it here, so we rely on the parser to protectSpecialChars
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
        ];
        $environment = new Environment($config);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AttributesExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new DescriptionListExtension());
        $environment->addExtension(new FootnoteExtension());

        if ($this->option['autotoc']) {
            $environment->addExtension(new HeadingPermalinkExtension());
            $environment->addExtension(new TableOfContentsExtension());
        }

        if ($prefs['markdown_gfm'] === 'y') {
            $environment->addExtension(new GithubFlavoredMarkdownExtension());
        }

        // add default class to code blocks -> <pre class="codelisting">
        $environment->addRenderer(
            FencedCode::class,
            new class implements NodeRendererInterface {
                public function render(Node $node, ChildNodeRendererInterface $childRenderer)
                {
                    $htmlEl = (new FencedCodeRenderer())->render($node, $childRenderer);
                    $class = $htmlEl->getAttribute('class') ?: 'codelisting';
                    $htmlEl->setAttribute('class', $class);
                    return $htmlEl;
                }
            },
            10
        );

        // add default class to table -> <table class="wikitable table table-striped table-hover">
        $environment->addRenderer(
            Table::class,
            new class implements NodeRendererInterface {
                public function render(Node $node, ChildNodeRendererInterface $childRenderer)
                {
                    $htmlEl = (new TableRenderer())->render($node, $childRenderer);
                    $class = $htmlEl->getAttribute('class') ?: 'wikitable table table-striped table-hover';
                    $htmlEl->setAttribute('class', $class);
                    return $htmlEl;
                }
            },
            10
        );

        $converter = new MarkdownConverter($environment);
        $data = $converter->convert($data)->getContent();

        $data = $this->parse_data_wikilinks($data, false, $this->option['ck_editor']);

        return $data;
    }
}
