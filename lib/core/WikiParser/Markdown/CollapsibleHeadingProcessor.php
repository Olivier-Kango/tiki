<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\WikiParser\Markdown;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Node\RawMarkupContainerInterface;
use League\CommonMark\Node\StringContainerHelper;
use League\CommonMark\Normalizer\TextNormalizerInterface;
use League\Config\ConfigurationInterface;
use League\Config\Exception\InvalidConfigurationException;
use Tiki\WikiParser\Markdown\Node\CollapsibleHeading;
use Tiki\WikiParser\Markdown\Node\CollapsibleContainer;
use Tiki\WikiParser\Markdown\Node\CollapsibleLink;

/**
 * Searches the Document for CollapsibleHeading elements, adds a [+]/[-] link and
 * surrounds next block element with special collapsible div
 */
class CollapsibleHeadingProcessor
{
    public function __invoke(DocumentParsedEvent $e): void
    {
        global $page;
        static $i = 0;

        foreach ($e->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if ($node instanceof CollapsibleHeading) {
                $i++;
                $id = 'id' . preg_replace('/[^a-zA-z0-9]/', '', urlencode($page)) . 'heading' . $i;
                $this->wrapNext($node, $id);
                $this->addHeadingLink($node, $id);
            }
        }
    }

    private function addHeadingLink(CollapsibleHeading $heading, string $id): void
    {
        $link = new CollapsibleLink($id, $heading->getOpen());
        $heading->insertAfter($link);
    }

    private function wrapNext(CollapsibleHeading $heading, string $id): void
    {
        $container = new CollapsibleContainer($id, $heading->getOpen());
        $next = $heading->next();
        $next->replaceWith($container);
        $container->appendChild($next);
    }
}
