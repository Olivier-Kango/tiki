<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Math_Formula_Element implements ArrayAccess, Iterator, Countable
{
    private $type;
    private $children;

    public function __construct($type, array $children = [])
    {
        $this->type = $type;
        $this->children = $children;
    }

    public function addChild($child)
    {
        $this->children[] = $child;
    }

    public function offsetExists($offset): bool
    {
        return is_int($offset) && isset($this->children[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (isset($this->children[$offset])) {
            return $this->children[$offset];
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset): void
    {
    }

    public function __get($name)
    {
        foreach ($this->children as $child) {
            if ($child instanceof Math_Formula_Element && $child->type == $name) {
                return $child;
            }
        }
    }

    public function getType()
    {
        return $this->type;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $key = key($this->children);
        return $this->children[$key];
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        next($this->children);
    }

    public function rewind(): void
    {
        reset($this->children);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->children);
    }

    public function valid(): bool
    {
        return false !== current($this->children);
    }

    public function count(): int
    {
        return count($this->children);
    }

    public function getExtraValues(array $allowedKeys)
    {
        $extra = [];

        foreach ($this->children as $child) {
            if ($child instanceof self) {
                if (! in_array($child->type, $allowedKeys)) {
                    $extra[] = "({$child->type} ...)";
                }
            } else {
                $extra[] = $child;
            }
        }

        if (count($extra)) {
            return $extra;
        }
    }
}
