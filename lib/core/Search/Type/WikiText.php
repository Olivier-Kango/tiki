<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Type_WikiText implements Search_Type_Interface
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        global $prefs, $pluginIncludeNumberOfInclusions;
        $pluginIncludeNumberOfInclusions = [];

        $out = TikiLib::lib('parser')->parse_data(
            $this->value,
            [
                'parsetoc' => false,
                'indexing' => true,
                'exclude_plugins' => $prefs['unified_excluded_plugins'],
                'include_plugins' => $prefs['unified_included_plugins'],
            ]
        );

        return strip_tags($out);
    }

    public function filter(array $filters)
    {
        $value = $this->value;

        foreach ($filters as $f) {
            $value = $f->filter($value);
        }

        return new self($value);
    }
}
