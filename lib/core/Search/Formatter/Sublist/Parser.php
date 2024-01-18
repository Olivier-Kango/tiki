<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Search\Formatter\Sublist;

use WikiParser_PluginArgumentParser;
use WikiParser_PluginMatcher;
use WikiParser_PluginMatcher_Match;

class Parser
{
    private $argparser;

    public function __construct()
    {
        $this->argparser = new WikiParser_PluginArgumentParser();
    }

    public function parse(WikiParser_PluginMatcher_Match $match)
    {
        $args = $this->argparser->parse($match->getArguments());

        if (empty($args['key'])) {
            throw new Exception(tr('Missing parameter \'key\' for SUBLIST block.'));
        }

        $record = new Record($args['key'], $this);

        // handle nested sublists first
        $sublists = [];
        $submatches = WikiParser_PluginMatcher::match($match->getBody());
        foreach ($submatches as $submatch) {
            if ($submatch->getName() == 'sublist') {
                $record->addSublist($this->parse($submatch));
            }
        }

        $body = $submatches->getText();
        $match->replaceWith('');

        $record->setBody($body);

        return $record;
    }

    public function getMatches(string $body)
    {
        return WikiParser_PluginMatcher::match($body);
    }

    public function getParts(string $body)
    {
        $parts = [];
        $matches = $this->getMatches($body);
        foreach ($matches as $match) {
            $arguments = $this->argparser->parse($match->getArguments());
            $parts[] = [
                'name' => $match->getName(),
                'arguments' => $arguments,
                'match' => $match,
            ];
        }
        return $parts;
    }
}
