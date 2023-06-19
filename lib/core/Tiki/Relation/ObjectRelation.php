<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Relation;

use TikiLib;

class ObjectRelation implements \ArrayAccess
{
    public int $id;
    public string $relation;
    public Parts\Source $source;
    public Parts\Target $target;
    public ?Parts\Metadata $metadata;

    public function __construct(array $row)
    {
        $this->id = $row['relationId'];
        $this->relation = $row['relation'];
        $this->source = new Parts\Source($row['source_type'], $row['source_itemId'], $row['source_fieldId']);
        $this->target = new Parts\Target($row['target_type'], $row['target_itemId']);
        if (! empty($row['metadata_itemId'])) {
            $this->metadata = new Parts\Metadata($row['metadata_itemId']);
        } else {
            $this->metadata = null;
        }
    }

    public function offsetExists($offset): bool
    {
        return in_array($offset, ['id', 'relation', 'source', 'target', 'metadata']);
    }

    public function offsetGet($offset): mixed
    {
        switch ($offset) {
            case 'id':
                return $this->id;
            case 'relation':
                return $this->relation;
            case 'source':
                return $this->source;
            case 'target':
                return $this->target;
            case 'metadata':
                return $this->metadata;
            case 'title':
                return $this->getTitle();
        }
    }

    public function offsetSet($offset, $value): void
    {
    }

    public function offsetUnset($offset): void
    {
    }

    public function getTitle($format = null)
    {
        return TikiLib::lib('object')->get_title($this->target->type, $this->target->itemId, $format, $this->getMetadataItemId());
    }

    public function __toString()
    {
        return strval($this->target);
    }

    public function getMetadataItemId()
    {
        return $this->metadata ? $this->metadata->itemId : null;
    }
}
