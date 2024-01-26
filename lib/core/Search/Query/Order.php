<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Search\Query;

class Order
{
    public const FIELD_SCORE = 'score';
    public const FIELD_MODIFICATION = 'modification_date';

    public const MODE_NUMERIC = 'numeric';
    public const MODE_TEXT = 'text';
    public const MODE_DISTANCE = 'distance';
    public const MODE_SCRIPT = 'script';

    private const ORDER_ASC = 'asc';
    private const ORDER_DESC = 'desc';

    private $field;
    private $mode;
    private $order;
    private $arguments;

    public function __construct($field, $mode, $order, array $arguments = [])
    {
        $this->field = $field;
        $this->mode = $mode;
        $this->order = $order;
        $this->arguments = $arguments;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public static function getDefault()
    {
        return self::searchResult();
    }

    public static function searchResult()
    {
        return new self(self::FIELD_SCORE, self::MODE_NUMERIC, self::ORDER_DESC);
    }

    public static function recentChanges()
    {
        return new self(self::FIELD_MODIFICATION, self::MODE_NUMERIC, self::ORDER_DESC);
    }

    public static function parse($orderString)
    {
        $clause = new OrderClause();
        $orderStrings = preg_split('/\s*,\s*/', $orderString);
        foreach ($orderStrings as $orderString) {
            if (empty($orderString)) {
                continue;
            }
            if (preg_match('/^(.+)_(asc|desc)$/', $orderString, $parts)) {
                $clause->add(new self($parts[1], self::MODE_TEXT, $parts[2]));
            } elseif (preg_match('/^(.+)_n(asc|desc)$/', $orderString, $parts)) {
                $clause->add(new self($parts[1], self::MODE_NUMERIC, $parts[2]));
            } else {
                $clause->add(new self($orderString, self::MODE_TEXT, self::ORDER_ASC));
            }
        }
        if (! $clause->any()) {
            $clause->add(self::getDefault());
        }
        return $clause;
    }
}
