<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiParser\Markdown\Renderer;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Xml\XmlNodeRendererInterface;
use Tiki\WikiParser\Markdown\Node\CollapsibleLink;

class CollapsibleLinkRenderer implements NodeRendererInterface, XmlNodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        CollapsibleLink::assertInstanceOf($node);

        $attrs = $node->data->getData('attributes');

        $attrs->set('id', 'flipper' . $node->getId());
        $attrs->append('class', 'link');
        $attrs->set('href', '#');
        $attrs->set('onclick', 'flipWithSign(\'' . $node->getId() . '\');return false;');

        $text = $node->getOpen() ? '[-]' : '[+]';

        return new HtmlElement('a', $attrs->export(), $text, false);
    }

    public function getXmlTagName(Node $node): string
    {
        return 'collapsible_link';
    }

    /**
     * @param Heading $node
     *
     * @return array<string, scalar>
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function getXmlAttributes(Node $node): array
    {
        CollapsibleLink::assertInstanceOf($node);

        return ['id' => $node->getId(), 'open' => $node->getOpen()];
    }
}
