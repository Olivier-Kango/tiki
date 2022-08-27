<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular\Source;

interface SourceEntryInterface
{
    /**
     * Renders an entry part from specified column
     */
    public function render(\Tracker\Tabular\Schema\Column $column, bool $allow_multiple);

    /**
     * Returns raw search server value before rendering for the specified column
     */
    public function raw(\Tracker\Tabular\Schema\Column $column);
}
