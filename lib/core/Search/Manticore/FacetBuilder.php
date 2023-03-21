<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Search\Manticore;

class FacetBuilder
{
    private $index;
    private $count;

    public function __construct(Index $index, $count = 10)
    {
        $this->index = $index;
        $this->count = $count;
    }

    public function build(array $facets)
    {
        if (empty($facets)) {
            return;
        }

        $out = [];
        foreach ($facets as $facet) {
            $out[] = $this->buildFacet($facet);
        }
        return implode(' ', $out);
    }

    private function buildFacet($facet)
    {
        $out = '';

        // federated search not yet implemented
        if ($facet->getField() == '_index') {
            return $out;
        }
        $type = $facet->getType();
        if ($type === 'date_histogram') {
            // TODO: work out ES histogram through Manticore expression
        } elseif ($type === 'date_range') {
            // TODO: work out ES date range through Manticore expression
        } else {
            $count = $facet->getCount() ?: $this->count;
            $order = $facet->getOrder();
            $field = strtolower($facet->getField());
            try {
                $this->index->ensureHasField($field);
            } catch (Exception $e) {
                // ignore facet requests for missing fields
                return '';
            }
            $out = 'FACET ' . $facet->getName() . ' BY ' . $field;
            if ($order) {
                foreach ($order as $field => $direction) {
                    $out .= ' ORDER BY ' . $field . ' ' . $direction;
                    break;
                }
            } else {
                $out .= ' ORDER BY COUNT(*) DESC';
            }
            $out .= ' LIMIT ' . $count;
        }

        return $out;
    }
}
