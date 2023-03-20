<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Search\Manticore;

class FacetReader
{
    private $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function getFacetFilter(\Search_Query_Facet_Interface $facet)
    {
        $entry = null;

        foreach ($this->results as $i => $result) {
            if ($i == 0) {
                continue;
            }
            if (array_key_first($result[0]) == $facet->getName()) {
                $entry = $result;
                break;
            }
        }

        if ($entry) {
            return new \Search_ResultSet_FacetFilter($facet, $this->getFromBucket($entry));
        } else {
            return null;
        }
    }

    private function getFromBucket($entry)
    {
        $out = [];
        foreach ($entry as $row) {
            $out[] = ['value' => array_shift($row), 'count' => array_pop($row)];
        }
        return $out;
    }
}
