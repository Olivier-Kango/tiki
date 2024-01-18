<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Search\Formatter\Sublist;

use Search_Formatter;

class Record
{
    private $key;
    private $body;
    private $sublists;

    private $parser;

    public function __construct(string $key, Parser $parser)
    {
        $this->key = $key;
        $this->parser = $parser;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function addSublist(Record $sublist)
    {
        $this->sublists[] = $sublist;
    }

    public function getSublists()
    {
        return $this->sublists;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function executeOverDataset(&$data, Search_Formatter $sf)
    {
        $executor = new Executor($this, $sf);
        $executor->runOnDataset($data);
    }

    public function getFormats()
    {
        $formats = [];
        $parts = $this->parser->getParts($this->body);
        foreach ($parts as $part) {
            if ($part['name'] == 'format') {
                $formats[] = [
                    'body' => $part['match']->getBody(),
                    'arguments' => $part['arguments'],
                ];
            }
        }
        return $formats;
    }

    public function getFilters()
    {
        $filters = [];
        $parts = $this->parser->getParts($this->body);
        foreach ($parts as $part) {
            if ($part['name'] == 'filter') {
                $filters[] = [
                    'match' => $part['match'],
                    'arguments' => $part['arguments'],
                ];
            }
        }
        return $filters;
    }
}
