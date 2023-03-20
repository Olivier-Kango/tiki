<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Search\Manticore;

abstract class Decorator
{
    protected $search;
    protected $index;

    public function __construct(\Manticoresearch\Search $search, Index $index)
    {
        $this->search = $search;
        $this->index = $index;
    }

    protected function getNodeField($node)
    {
        $field = strtolower($node->getField());
        $this->ensureHasField($field);
        return $field;
    }

    protected function ensureHasField($field)
    {
        $this->index->ensureHasField($field);
    }
}
