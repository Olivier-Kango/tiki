<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Query_Facet_Term extends Search_Query_Facet_Abstract implements Search_Query_Facet_Interface
{
    private $operator = 'or';
    private $count;
    private $order;
    private $min_doc_count;
    private $valueMap;

    public static function fromField($field)
    {
        return new self($field);
    }

    public function getCount()
    {
        return $this->count;
    }

    public function setCount($count)
    {
        $this->count = $count;
        return $this;
    }

    public function setRenderMap(array $map)
    {
        return $this->setRenderCallback(
            function ($value) use ($map) {
                if (isset($map[$value])) {
                    return $map[$value];
                } else {
                    return $value;
                }
            }
        );
    }

    public function setValueMap(array $map)
    {
        $this->valueMap = $map;
        return $this;
    }

    public function getValue($value)
    {
        if (isset($this->valueMap[$value])) {
            return $this->valueMap[$value];
        } else {
            return $value;
        }
    }

    public function getType()
    {
        return 'terms';
    }

    public function setOperator($operator)
    {
        $this->operator = in_array($operator, ['and', 'or']) ? $operator : 'or';
        return $this;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return null|array [field => order]
     */
    public function getOrder()
    {
        $order = null;

        if ($this->order) {
            $order = [];
            $searchQueryOrder = \Search\Query\Order::parse($this->order);
            foreach ($searchQueryOrder->getParts() as $part) {
                $order[$part->getField()] = $part->getOrder();
            }
        }

        return $order;
    }

    /**
     * @param string $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinDocCount()
    {
        return $this->min_doc_count;
    }

    /**
     * @param int $min_doc_count
     *
     * @return Search_Query_Facet_Term
     */
    public function setMinDocCount($min)
    {
        $this->min_doc_count = $min;
        return $this;
    }
}
