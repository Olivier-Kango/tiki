<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/** Just in time filters.  For filtering object or array values when the type cannot be known before the script is executed.
 *
 *  See https://dev.tiki.org/Filtering-Best-Practices
 *
 */
class JitFilter implements ArrayAccess, Iterator, Countable
{
    private $stored;
    private $defaultFilter;
    private $lastUsed = [];
    private $filters = [];

    public function __construct($data)
    {
        $this->stored = $data;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->stored[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->stored[$offset]);
        unset($this->lastUsed[$offset]);
        unset($this->filters[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        // Composed objects go through
        if (isset($this->stored[$key]) && $this->stored[$key] instanceof self) {
            return $this->stored[$key];
        }

        $filter = $this->getFilter($key);

        if (isset($this->stored[$key]) && is_array($this->stored[$key])) {
            $this->stored[$key] = new self($this->stored[$key]);

            if ($filter) {
                $this->stored[$key]->setDefaultFilter($filter);
            }

            return $this->stored[$key];
        }

        if ($filter) {
            if (isset($this->lastUsed[$key]) && $this->lastUsed[$key][0] == $filter) {
                return $this->lastUsed[$key][1];
            }

            if (! isset($this->stored[$key])) {
                return null;
            }

            $this->lastUsed[$key] = [$filter, $filter->filter($this->stored[$key])];
            return $this->lastUsed[$key][1];
        } else {
            // No filtering has no special behavior
            return $this->stored[$key] ?? null;
        }
    }

    public function offsetSet($key, $value): void
    {
        unset($this->lastUsed[$key]);

        if ($value instanceof self) {
            $this->stored[$key] = $value->stored;
        } else {
            $this->stored[$key] = $value;
        }
    }

    public function asArray($key = false, $separator = false)
    {
        if ($key === false) {
            $ret = [];
            if (is_array($this->stored)) {
                foreach (array_keys($this->stored) as $k) {
                    $ret[$k] = $this->offsetGet($k);
                    if ($ret[$k] instanceof self) {
                        $ret[$k] = $ret[$k]->asArray();
                    }
                }
            }

            return $ret;
        } elseif (isset($this->stored[$key])) {
            $value = $this->stored[$key];

            if ($value instanceof self || is_array($value)) {
                return $this->offsetGet($key)->asArray();
            } elseif ($separator === false) {
                return [$this->offsetGet($key)];
            } else {
                $jit = new self(explode($separator, $value));
                $jit->setDefaultFilter($this->getFilter($key));

                return $jit->asArray();
            }
        } else {
            return [];
        }
    }

    public function subset($keys)
    {
        $jit = new self([]);
        $jit->defaultFilter = $this->defaultFilter;
        $jit->filters = $this->filters;

        foreach ($keys as $key) {
            if (isset($this->stored[$key])) {
                $jit->stored[$key] = $this->stored[$key];
            }
            if (isset($this->lastUsed[$key])) {
                $jit->lastUsed[$key] = $this->lastUsed[$key];
            }
        }

        return $jit;
    }

    public function isArray($key)
    {
        return isset($this->stored[$key]) && $this->offsetGet($key) instanceof self;
    }

    public function keys()
    {
        return array_keys($this->stored);
    }

    private function getFilter($key)
    {
        if (array_key_exists($key, $this->filters)) {
            return $this->filters[$key];
        } elseif ($this->defaultFilter) {
            return $this->defaultFilter;
        }

        return null;
    }

    public function setDefaultFilter($filter)
    {
        $this->defaultFilter = TikiFilter::get($filter);
    }

    public function replaceFilter($key, $filter)
    {
        $filter = TikiFilter::get($filter);

        $this->filters[$key] = $filter;

        if (isset($this->stored[$key]) && $this->stored[$key] instanceof self) {
            $this->stored[$key]->setDefaultFilter($filter);
        }
    }

    public function replaceFilters($filters)
    {
        foreach ($filters as $key => $values) {
            if (
                is_array($values)
                && $this->offsetExists($key)
                && $this->offsetGet($key) instanceof self
            ) {
                $this->offsetGet($key)->replaceFilters($values);
            } else {
                $this->replaceFilter($key, $values);
            }
        }
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $key = key($this->stored);
        return $this->offsetGet($key);
    }

    public function next(): void
    {
        next($this->stored);
    }

    public function rewind(): void
    {
        reset($this->stored);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->stored);
    }

    public function valid(): bool
    {
        return false !== current($this->stored);
    }

    public function count(): int
    {
        return count($this->stored);
    }

    /**
     * @param $key
     * @return JitFilter_Element
     */
    public function __get($key)
    {
        if (! isset($this->stored[$key])) {
            return new JitFilter_Element(null);
        }

        if ($this->stored[$key] instanceof self || is_array($this->stored[$key])) {
            return $this->offsetGet($key);
        }

        return new JitFilter_Element($this->stored[$key]);
    }

    public function filter($filter)
    {
        $jit = new self($this->stored);
        $jit->setDefaultFilter($filter);
        return $jit->asArray();
    }

    public function __call($name, $arguments)
    {
        return $this->filter($name);
    }
}
