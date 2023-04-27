<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Search\Manticore;

class IncrementalUpdateTest extends \Search_Index_IncrementalUpdateTest
{
    use IndexBuilder;

    protected $index;

    protected function setUp(): void
    {
        $this->index = $this->createIndex('_incremental_update');
        $this->index->destroy();

        $this->populate($this->index);
    }

    protected function tearDown(): void
    {
        if ($this->index) {
            $this->index->destroy();
        }
    }
}
