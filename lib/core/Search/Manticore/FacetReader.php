<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Manticore_FacetReader
{
    private $result;

    public function __construct($result)
    {
        $this->result = $result;
    }

    public function getFacetFilter(Search_Query_Facet_Interface $facet)
    {
        $facetName = $facet->getName();
        $entry = null;

        $facets = $this->result->getFacets();

        if (empty($facets[$facetName]['buckets'])) {
            return null;
        }

        if (isset($facets[$facetName])) {
            $entry = $facets[$facetName];
        }

        return new Search_ResultSet_FacetFilter($facet, $this->getFromBucket($entry));
    }

    private function getFromBucket($entry)
    {
        $out = [];

        if (! empty($entry['buckets'])) {
            foreach ($entry['buckets'] as $bucket) {
                if ('' !== $bucket['key']) {
                    $out[] = ['value' => $bucket['key'], 'count' => $bucket['doc_count']];
                }
            }
        }

        return $out;
    }
}
