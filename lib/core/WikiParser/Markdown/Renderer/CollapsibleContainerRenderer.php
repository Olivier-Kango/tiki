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
use Tiki\WikiParser\Markdown\Node\CollapsibleContainer;

class CollapsibleContainerRenderer implements NodeRendererInterface, XmlNodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        CollapsibleContainer::assertInstanceOf($node);

        $attrs = $node->data->getData('attributes');

        $attrs->set('id', $node->getId());
        $attrs->append('class', 'showhide_heading');
        $attrs->set('style', 'display:' . ($node->getOpen() ? 'block' : 'none'));

        return new HtmlElement('div', $attrs->export(), $childRenderer->renderNodes($node->children()));
    }

    public function getXmlTagName(Node $node): string
    {
        return 'collapsible_container';
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
        CollapsibleContainer::assertInstanceOf($node);

        return ['id' => $node->getId(), 'open' => $node->getOpen()];
    }
}
