<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Config;

use ArrayAccess;
use Countable;
use Iterator;

class Config implements Countable, Iterator, ArrayAccess
{
    protected array $data = [];
    protected bool $readOnly;
    protected bool $skipNextIteration = false;

    public function __construct(array $data = [], bool $readOnly = false)
    {
        $this->readOnly = $readOnly;

        // Wrap sub-arrays into Config objects for recursive structure
        foreach ($data as $key => $value) {
            $this->data[$key] = is_array($value)
                ? new self($value, $readOnly)
                : $value;
        }
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value)
    {
        if ($this->readOnly) {
            throw new \RuntimeException('Config is read only');
        }

        $this->data[$name] = is_array($value)
            ? new self($value, false)
            : $value;
    }

    public function __unset($name)
    {
        if ($this->readOnly) {
            throw new \RuntimeException('Config is read only');
        }

        unset($this->data[$name]);
        $this->skipNextIteration = true;
    }

    public function __isset($name): bool
    {
        return isset($this->data[$name]);
    }

    public function merge(array|self $other): self
    {
        $otherData = $other instanceof self ? $other->toArray() : $other;
        $merged = array_replace_recursive($this->toArray(), $otherData);
        return new self($merged, $this->readOnly);
    }

    public function toArray(): array
    {
        $array = [];
        foreach ($this->data as $key => $value) {
            $array[$key] = $value instanceof self ? $value->toArray() : $value;
        }
        return $array;
    }

    public function setReadOnly(): void
    {
        $this->readOnly = true;
        foreach ($this->data as $value) {
            if ($value instanceof self) {
                $value->setReadOnly();
            }
        }
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public function __clone(): void
    {
        foreach ($this->data as $key => $value) {
            $this->data[$key] = $value instanceof self ? clone $value : $value;
        }
    }

    // === Countable ===
    public function count(): int
    {
        return count($this->data);
    }

    // === Iterator ===
    public function rewind(): void
    {
        $this->skipNextIteration = false;
        reset($this->data);
    }

    public function current(): mixed
    {
        $this->skipNextIteration = false;
        return current($this->data);
    }

    public function key(): mixed
    {
        return key($this->data);
    }

    public function next(): void
    {
        if ($this->skipNextIteration) {
            $this->skipNextIteration = false;
            return;
        }
        next($this->data);
    }

    public function valid(): bool
    {
        return key($this->data) !== null;
    }

    // === ArrayAccess ===
    public function offsetExists(mixed $offset): bool
    {
        return $this->__isset($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->__set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->__unset($offset);
    }
}
