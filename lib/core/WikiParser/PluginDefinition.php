<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class WikiParser_PluginDefinition implements ArrayAccess, Countable
{
    private $repository;
    private $data;

    public function __construct($repository, $data)
    {
        $this->repository = $repository;
        $this->data = $data;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        // Immutable
        // TODO check this out
        //return $this->offsetGet($offset);
    }

    public function offsetUnset($offset): void
    {
        // Immutable
    }

    public function count(): int
    {
        return count($this->data);
    }
}
