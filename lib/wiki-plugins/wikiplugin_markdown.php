<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_markdown_info()
{
    return [
        'name' => tra('Markdown'),
        'documentation' => 'PluginMarkdown',
        'description' => tra('Parse the body of the plugin using a Markdown parser.'),
        'prefs' => ['wikiplugin_markdown'],
        'body' => tra('Markdown syntax to be parsed'),
        'iconname' => 'code',
        'introduced' => 20,
        'filter' => 'rawhtml_unsafe',
        'format' => 'html',
        'tags' => [ 'advanced' ],
        'params' => [
            // TODO: add some useful params here
        ],
    ];
}

// common requirement for extension packages
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Table\TableRenderer;

use League\CommonMark\Block\Element\AbstractBlock;
use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\Block\Renderer\BlockRendererInterface;
use League\CommonMark\Block\Renderer\FencedCodeRenderer;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;

function wikiplugin_markdown($data, $params)
{

    global $prefs;
    extract($params, EXTR_SKIP);

    $md = trim($data);
    $md = str_replace('&lt;x&gt;', '', $md);
    $md = str_replace('<x>', '', $md);

    // create pre-configured Environment
    $environment = Environment::createCommonMarkEnvironment();

    // add Attributes-Extension
    $environment->addExtension(new AttributesExtension());
    $environment->addExtension(new TableExtension());

    // let's define our configurationon
    $config = ['html_input' => 'escape', 'allow_unsafe_links' => 'false'];
    $environment->setConfig(['html_input' => 'escape', 'allow_unsafe_links' => false]);

    // add default class to code blocks -> <pre class="codelisting">
    $environment->addBlockRenderer(
        FencedCode::class,
        new class implements BlockRendererInterface {
            public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, bool $inTightList = false)
            {
                $htmlEl = (new FencedCodeRenderer())->render($block, $htmlRenderer, $inTightList);
                $class = $htmlEl->getAttribute('class') ?: 'codelisting';
                $htmlEl->setAttribute('class', $class);
                return $htmlEl;
            }
        },
        10
    );

    // add default class to table -> <table class="wikitable table table-striped table-hover">
    $environment->addBlockRenderer(
        Table::class,
        new class implements BlockRendererInterface {
            public function render(AbstractBlock $block, ElementRendererInterface $htmlRenderer, bool $inTightList = false)
            {
                $htmlEl = (new TableRenderer())->render($block, $htmlRenderer, $inTightList);
                $class = $htmlEl->getAttribute('class') ?: 'wikitable table table-striped table-hover';
                $htmlEl->setAttribute('class', $class);
                return $htmlEl;
            }
        },
        10
    );

    $converter = new CommonMarkConverter($config, $environment);
    $md = $converter->convertToHtml($md);

    # TODO: "if param wiki then" $md = TikiLib::lib('parser')->parse_data($md, ['is_html' => true, 'parse_wiki' => true]);
    return $md;
}
