<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Source;

class ODBCSourceEntry implements SourceEntryInterface
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function render(\Tracker\Tabular\Schema\Column $column, $allow_multiple)
    {
        $values = [];
        $fields = $column->getRemoteFields();
        foreach ($fields as $field) {
            if (isset($this->data[$field])) {
                $value = $this->data[$field];
            } else {
                $value = null;
            }
            $values[] = $value;
        }
        return $column->render($allow_multiple ? $values : $values[0], ['allow_multiple' => $allow_multiple]);
    }

    public function raw(\Tracker\Tabular\Schema\Column $column)
    {
        $fields = $column->getRemoteFields();
        foreach ($fields as $field) {
            if (isset($this->data[$field])) {
                return $this->data[$field];
            }
        }
        return null;
    }

    public function parseInto(&$info, $column)
    {
        $remoteFields = $column->getRemoteFields();
        if (count($remoteFields) > 1) {
            $entry = [];
            foreach ($remoteFields as $remoteField) {
                if (isset($this->data[$remoteField])) {
                    $entry[] = $this->data[$remoteField];
                }
            }
        } else {
            $entry = $this->data[$column->getRemoteField()] ?? null;
        }
        $column->parseInto($info, $entry, $this->data);
    }
}
