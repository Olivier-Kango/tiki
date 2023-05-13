<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

class FacetDecorator extends Decorator
{
    private $count;

    public function __construct(\Manticoresearch\Search $search, Index $index, $count = 10)
    {
        parent::__construct($search, $index);
        $this->count = $count;
    }

    public function decorate(array $facets)
    {
        if (empty($facets)) {
            return;
        }

        $out = [];
        foreach ($facets as $facet) {
            // federated search not yet implemented
            if ($facet->getField() == '_index') {
                continue;
            }
            $type = $facet->getType();
            if ($type === 'date_histogram') {
                // TODO: work out ES histogram through Manticore expression
            } elseif ($type === 'date_range') {
                // TODO: work out ES date range through Manticore expression
            } else {
                $count = $facet->getCount() ?: $this->count;
                // TODO: facet ordering is supported only in SQL
                try {
                    $this->search->facet($this->getNodeField($facet), $facet->getName(), $this->count);
                } catch (Exception $e) {
                    // ignore fields not found in the index
                }
            }
        }
    }
}
