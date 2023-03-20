<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Search_EngineResult_Manticore implements Search_EngineResult_Interface
{
    private $index = null;

    public function __construct(\Search\Manticore\Index $index)
    {
        $this->index = $index;
    }

    /**
     * Count the amount of fields used by the Manticore search engine
     * @return int
     */
    public function getEngineFieldsCount()
    {
        return count($this->index->getFieldMappings());
    }
}
