<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Relation\Parts;

class Metadata implements \ArrayAccess
{
    public int $itemId;

    public function __construct(int $itemId)
    {
        $this->itemId = $itemId;
    }

    public function offsetExists($offset): bool
    {
        return $offset == 'itemId';
    }

    public function offsetGet($offset): mixed
    {
        switch ($offset) {
            case 'itemId':
                return $this->itemId;
        }
    }

    public function offsetSet($offset, $value): void
    {
    }

    public function offsetUnset($offset): void
    {
    }

    public function getItem()
    {
        return \Tracker_Item::fromId($this->itemId);
    }
}
