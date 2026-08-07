<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ResultSet_FacetFilter
{
    private $facet;
    private $data;

    public function __construct(Search_Query_Facet_Interface $facet, array $data)
    {
        $this->facet = $facet;
        $this->data = $data;
    }

    public function isFacet(Search_Query_Facet_Interface $facet)
    {
        return $this->facet->getName() === $facet->getName();
    }

    public function getName()
    {
        return $this->facet->getName();
    }

    public function getLabel()
    {
        return $this->facet->getLabel();
    }

    public function getOperator()
    {
        if (is_a($this->facet, 'Search_Query_Facet_Term')) {
            return $this->facet->getOperator();
        } else {
            return null;
        }
    }

    public function getOptions()
    {
        $out = [];

        foreach ($this->data as $entry) {
            $rawValue = (string) $entry['value'];
            $displayValue = $entry['value'];

            if ($this->facet instanceof Search_Query_Facet_Term) {
                $displayValue = $this->facet->getValue($entry['value']);
            }

            $out[$rawValue] = tr('%0 (%1)', tra($this->facet->render($displayValue)), $entry['count']);
        }

        return $out;
    }
}
