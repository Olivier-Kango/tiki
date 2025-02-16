<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

use Search\Query\Order;
use Search\Query\OrderClause;

class OrderDecorator extends Decorator
{
    public function decorate(OrderClause $order)
    {
        foreach ($order->getParts() as $part) {
            $this->decorateOne($part);
        }
    }

    protected function decorateOne(Order $order)
    {
        $component = '_score';
        $field = strtolower($order->getField());

        if ($order->getMode() == Order::MODE_SCRIPT) {
            $arguments = $order->getArguments();

            $this->search->expression("sort_" . $field, $arguments['source']);
            $this->search->sort("sort_" . $field, $order->getOrder());
        } elseif ($field !== Order::FIELD_SCORE) {
            $mapping = $this->index ? $this->index->getFieldMapping($field) : [];
            if ($order->getMode() == Order::MODE_NUMERIC && $mapping && ! in_array('float', $mapping['types']) && substr($field, -6) != '_nsort') {
                $this->ensureHasField($field . '_nsort');
                $this->search->sort($field . '_nsort', $order->getOrder());
            } elseif ($order->getMode() == Order::MODE_DISTANCE) {
                $this->ensureHasField($field);
                $arguments = $order->getArguments();
                $fields = preg_split('/\s*,\s*/', $field);
                $this->search->sort("GEODIST(" . $arguments['lat'] . ", " . $arguments['lon'] . ", " . $fields[0] . ", " . $fields[1] . ")", $order->getOrder());
            } else {
                $this->ensureHasField($field);
                $this->search->sort($field, $order->getOrder());
            }
        }
    }
}
