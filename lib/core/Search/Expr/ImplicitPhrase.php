<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Expr_ImplicitPhrase implements Search_Expr_Interface
{
    private $parts;
    private $weight = 1.0;

    public function __construct(array $parts)
    {
        $this->parts = $parts;
    }

    public function __clone()
    {
        $this->parts = array_map(function ($part) {
            return clone $part;
        }, $this->parts);
    }

    public function addPart(Search_Expr_Interface $part)
    {
        $this->parts[] = $part;
    }

    public function setType($type)
    {
        foreach ($this->parts as $part) {
            $part->setType($type);
        }
    }

    public function setField($field = 'global')
    {
        foreach ($this->parts as $part) {
            $part->setField($field);
        }
    }

    public function setWeight($weight)
    {
        $this->weight = (float) $weight;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function getBasicOperator()
    {
        global $prefs;
        if ($prefs['unified_search_default_operator'] == 1) {
            return new Search_Expr_And($this->parts);
        } else {
            return new Search_Expr_Or($this->parts);
        }
    }

    public function walk($callback)
    {
        $results = [];
        foreach ($this->parts as $part) {
            $results[] = $part->walk($callback);
        }

        return call_user_func($callback, $this, $results);
    }

    public function traverse($callback)
    {
        return call_user_func($callback, $callback, $this, $this->parts);
    }
}
