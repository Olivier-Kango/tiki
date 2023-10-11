<?php

declare(strict_types=1);

namespace Tiki\WikiParser\Markdown\Renderer;

use League\CommonMark\Extension\TaskList\TaskListItemMarker;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Xml\XmlNodeRendererInterface;
use TikiLib;
use Tiki\Modules\Permissions;

final class TaskListItemMarkerRenderer implements NodeRendererInterface, XmlNodeRendererInterface
{
    private $order = 1;

    /**
     * @param TaskListItemMarker $node
     *
     * {@inheritDoc}
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        global $user;
        $tikilib = TikiLib::lib('tiki');
        $objectlib = TikiLib::lib('object');
        $parserlib = TikiLib::lib('parser');

        TaskListItemMarker::assertInstanceOf($node);

        $attrs = $node->data->get('attributes');
        $checkbox = new HtmlElement('input', $attrs, '', true);

        $objectType = $parserlib->option['objectType'] ?? '';
        $objectId = $parserlib->option['objectId'] ?? '';
        $fieldName = $parserlib->option['fieldName'] ?? '';
        $perm = $objectlib->get_needed_perm($objectType, 'edit');

        if (! (empty($objectType) || empty($objectId) || empty($fieldName)) && $tikilib->user_has_perm_on_object($user, $objectId, $objectType, $perm)) {
            $checkbox->setAttribute('data-order', '' . $this->order);
            $checkbox->setAttribute('data-object-id', '' . $objectId);
            $checkbox->setAttribute('data-object-type', '' . $objectType);
            $checkbox->setAttribute('data-field-name', '' . $fieldName);
            $checkbox->setAttribute('class', 'checkbox_plugin');
            $this->order++;
        } else {
            $checkbox->setAttribute('disabled', '');
        }

        $checkbox->setAttribute('type', 'checkbox');
        if ($node->isChecked()) {
            $checkbox->setAttribute('checked', 'checked');
        }

        return $checkbox;
    }

    public function getXmlTagName(Node $node): string
    {
        return 'task_list_item_marker';
    }

    /**
     * @param TaskListItemMarker $node
     *
     * @return array<string, scalar>
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function getXmlAttributes(Node $node): array
    {
        TaskListItemMarker::assertInstanceOf($node);

        if ($node->isChecked()) {
            return ['checked' => 'checked'];
        }

        return [];
    }
}
