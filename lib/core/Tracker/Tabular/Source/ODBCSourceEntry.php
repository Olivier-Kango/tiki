<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tracker\Tabular\Source;

class ODBCSourceEntry implements SourceEntryInterface
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function render(\Tracker\Tabular\Schema\Column $column)
    {
        $field = $column->getRemoteField();
        if (isset($this->data[$field])) {
            $value = $this->data[$field];
        } else {
            $value = null;
        }
        return $column->render($value);
    }

    public function parseInto(&$info, $column)
    {
        $entry = $this->data[$column->getRemoteField()];
        $column->parseInto($info, $entry, $this->data);
    }
}
