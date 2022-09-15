<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Manticore_OrderDecorator extends Search_Manticore_Decorator
{
    public function decorate(Search_Query_Order $order)
    {
        $component = '_score';
        $field = strtolower($order->getField());

        if ($order->getMode() == Search_Query_Order::MODE_SCRIPT) {
            $arguments = $order->getArguments();

            $this->search->expression("sort_".$field, $arguments['source']);
            $this->search->sort("sort_".$field, $order->getOrder());
        } elseif ($field !== Search_Query_Order::FIELD_SCORE) {
            $this->ensureHasField($field);
            if ($order->getMode() == Search_Query_Order::MODE_NUMERIC) {
                $this->search->sort("DOUBLE(".$field.")", $order->getOrder());
            } elseif ($order->getMode() == Search_Query_Order::MODE_DISTANCE) {
                $arguments = $order->getArguments();
                $fields = preg_split('/\s*,\s*/', $field);
                $this->search->sort("GEODIST(".$arguments['lat'].", ".$arguments['lon'].", ".$fields[0].", ".$fields[1].")", $order->getOrder());
            } else {
                $this->search->sort($field, $order->getOrder());
            }
        }
    }
}
