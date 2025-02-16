<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class WikiParser_PluginMatcher implements Iterator, Countable
{
    private $starts = [];
    private $ends = [];
    private $level = 0;

    private $ranges = [];

    private $text = '';

    private $scanPosition = -1;

    private $leftOpen = 0;

    /**
     * @param $text
     * @return WikiParser_PluginMatcher
     */
    public static function match(?string $text)
    {
        $matcher = new self();
        $matcher->text = $text;
        $matcher->findMatches(0, strlen($text ?? ''));

        return $matcher;
    }

    public function __clone()
    {
        $new = $this;
        $this->starts = array_map(
            function ($match) use ($new) {
                $match->changeMatcher($new);
                return clone $match;
            },
            $this->starts
        );

        $this->ends = array_map(
            function ($match) use ($new) {
                $match->changeMatcher($new);
                return clone $match;
            },
            $this->ends
        );
    }

    private function getSubMatcher($start, $end)
    {
        $sub = new self();
        $sub->level = $this->level + 1;
        $sub->text = $this->text;
        $sub->findMatches($start, $end);

        return $sub;
    }

    private function appendSubMatcher($matcher)
    {
        foreach ($matcher->starts as $match) {
            $match->changeMatcher($this);
            $this->recordMatch($match);
        }
    }

    private function isComplete()
    {
        return $this->leftOpen == 0;
    }

    public function findMatches($start, $end)
    {
        global $prefs;

        static $passes;

        if ($this->level === 0) {
            $passes = 0;
        }

        if (++$passes > $prefs['wikiplugin_maximum_passes']) {
            return;
        }

        $this->findNoParseRanges($start, $end);

        $pos = $start;
        while (false !== $pos = strpos($this->text ?? '', '{', $pos)) {
            // Shortcut {$var} syntax
            if (substr($this->text, $pos + 1, 1) === '$') {
                ++$pos;
                continue;
            }

            if ($pos >= $end) {
                return;
            }

            if (! $this->isParsedLocation($pos)) {
                ++$pos;
                continue;
            }

            $match = new WikiParser_PluginMatcher_Match($this, $pos);
            ++$pos;

            if (! $match->findName($end)) {
                continue;
            }

            if (! $match->findArguments($end)) {
                continue;
            }

            if ($match->getEnd() !== false) {
                // End already reached
                $this->recordMatch($match);
                $pos = $match->getEnd();
            } else {
                ++$this->leftOpen;

                $bodyStart = $match->getBodyStart();
                $lookupStart = $bodyStart;

                while ($match->findEnd($lookupStart, $end)) {
                    $candidate = $match->getEnd();

                    $sub = $this->getSubMatcher($bodyStart, $candidate - 1);
                    if ($sub->isComplete()) {
                        $this->recordMatch($match);
                        if ($match->getName() != 'code') {
                            $this->appendSubMatcher($sub);
                        }
                        $pos = $match->getEnd();
                        --$this->leftOpen;
                        if (empty($this->level)) {
                            $passes = 0;
                        }
                        break;
                    }

                    $lookupStart = $candidate;
                }
            }
        }
    }

    public function getText()
    {
        return $this->text;
    }

    private function recordMatch($match)
    {
        $this->starts[$match->getStart()] = $match;
        $this->ends[$match->getEnd()] = $match;
    }

    private function findNoParseRanges($from, $to)
    {
        while (false !== $open = $this->findText('~np~', $from, $to)) {
            if (false !== $close = $this->findText('~/np~', $open, $to)) {
                $from = $close;
                $this->ranges[] = [$open, $close];
            } else {
                return;
            }
        }
    }

    public function isParsedLocation($pos)
    {
        foreach ($this->ranges as $range) {
            list($open, $close ) = $range;

            if ($pos > $open && $pos < $close) {
                return false;
            }
        }

        return true;
    }

    public function count(): int
    {
        return count($this->starts);
    }

    /**
     * @return WikiParser_PluginMatcher_Match
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->starts[ $this->scanPosition ];
    }

    /**
     * @return WikiParser_PluginMatcher_Match
     */
    public function next(): void
    {
        foreach ($this->starts as $key => $m) {
            if ($key > $this->scanPosition) {
                $this->scanPosition = $key;
                return;
            }
        }

        $this->scanPosition = -1;
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->scanPosition;
    }

    public function valid(): bool
    {
        return isset($this->starts[$this->scanPosition]);
    }

    public function rewind(): void
    {
        reset($this->starts);
        $this->scanPosition = key($this->starts);
    }

    public function getChunkFrom($pos, $size)
    {
        return substr($this->text, $pos, $size);
    }

    private function getFirstStart($lower)
    {
        foreach ($this->starts as $key => $match) {
            if ($key >= $lower) {
                return $key;
            }
        }

        return false;
    }

    private function getLastEnd()
    {
        $ends = array_keys($this->ends);
        return end($ends);
    }

    public function findText(string $string, int $from, int $to): int|bool
    {
        if ($from >= strlen($this->text ?? '')) {
            return false;
        }

        $pos = strpos($this->text, $string, $from);

        if ($pos === false || $pos + strlen($string) > $to) {
            return false;
        }

        return $pos;
    }

    public function performReplace($match, $string)
    {
        if (! isset($string)) {
            $string = '';
        }
        $start = $match->getStart();
        $end = $match->getEnd();

        $sizeDiff = - ($end - $start - strlen($string));
        $this->text = substr_replace($this->text, $string, $start, $end - $start);

        $this->removeRanges($start, $end);
        $this->offsetRanges($end, $sizeDiff);
        $this->findNoParseRanges($start, $start + strlen($string));

        $matches = $this->ends;
        $toRemove = [$match];
        $toAdd = [];

        foreach ($matches as $key => $m) {
            if ($m->inside($match)) {
                $toRemove[] = $m;
            } elseif ($match->inside($m)) {
                // Boundaries should not be extended for wrapping plugins
            } elseif ($key > $end) {
                unset($this->ends[$m->getEnd()]);
                unset($this->starts[$m->getStart()]);
                $m->applyOffset($sizeDiff);
                $toAdd[] = $m;
            }
        }

        foreach ($toRemove as $m) {
            unset($this->ends[$m->getEnd()]);
            unset($this->starts[$m->getStart()]);
            $m->invalidate();
        }

        foreach ($toAdd as $m) {
            $this->ends[$m->getEnd()] = $m;
            $this->starts[$m->getStart()] = $m;
        }

        $sub = $this->getSubMatcher($start, $start + strlen($string));
        if ($sub->isComplete()) {
            $this->appendSubMatcher($sub);
        }

        ksort($this->ends);
        ksort($this->starts);

        if ($this->scanPosition == $start) {
            $this->scanPosition = $start - 1;
        }
    }

    private function removeRanges($start, $end)
    {
        $toRemove = [];
        foreach ($this->ranges as $key => $range) {
            if ($start >= $range[0] && $start <= $range[1]) {
                $toRemove[] = $key;
            }
        }

        foreach ($toRemove as $key) {
            unset($this->ranges[$key]);
        }
    }

    private function offsetRanges($end, $sizeDiff)
    {
        foreach ($this->ranges as & $range) {
            if ($range[0] >= $end) {
                $range[0] += $sizeDiff;
                $range[1] += $sizeDiff;
            }
        }
    }
}

class WikiParser_PluginMatcher_Match
{
    private const LONG = 1;
    private const SHORT = 2;
    private const LEGACY = 3;
    private const NAME_MAX_LENGTH = 50;

    private $matchType = false;
    private $nameEnd = false;
    private $name = false;
    private $bodyStart = false;
    private $bodyEnd = false;

    /** @var WikiParser_PluginMatcher|bool */
    private $matcher = false;

    private $start = false;
    private $end = false;
    private $initialstart = false;
    private $arguments = false;

    public function __construct($matcher, $start)
    {
        $this->matcher = $matcher;
        $this->start = $start;
        $this->initialstart = $start;
    }

    public function findName($limit)
    {
        $candidate = $this->matcher->getChunkFrom($this->start + 1, self::NAME_MAX_LENGTH);
        $name = strtok($candidate, " (}\n\r,");

        if (empty($name) || ! ctype_alnum($name)) {
            $this->invalidate();
            return false;
        }

        // Upper case uses long syntax
        if (strtoupper($name) == $name) {
            $this->matchType = self::LONG;

            // Parenthesis required when using long syntax
            if (isset($candidate[strlen($name)]) && $candidate[strlen($name)] != '(') {
                $this->invalidate();
                return false;
            }
        } else {
            $this->matchType = self::SHORT;
        }

        $nameEnd = $this->start + 1 + strlen($name);

        if ($nameEnd > $limit) {
            $this->invalidate();
            return false;
        }

        $this->name = strtolower($name);
        $this->nameEnd = $nameEnd;

        return true;
    }

    public function findArguments($limit)
    {
        if ($this->nameEnd === false) {
            return false;
        }

        $pos = $this->matcher->findText('}', $this->nameEnd, $limit);

        if (false === $pos) {
            $this->invalidate();
            return false;
        }

        $unescapedFound = $this->countUnescapedQuotes($this->nameEnd, $pos);

        while (1 == ($unescapedFound % 2)) {
            $old = $pos;
            $pos = $this->matcher->findText('}', $pos + 1, $limit);
            if (false === $pos) {
                $this->invalidate();
                return false;
            }

            $unescapedFound += $this->countUnescapedQuotes($old, $pos);
        }

        if ($this->matchType == self::LONG && $this->matcher->findText('/', $pos - 1, $limit) === $pos - 1) {
            $this->matchType = self::LEGACY;
            --$pos;
        }

        $seek = $pos;
        while (ctype_space($this->matcher->getChunkFrom($seek - 1, '1'))) {
            $seek--;
        }

        if (in_array($this->matchType, [self::LONG, self::LEGACY]) && $this->matcher->findText(')', $seek - 1, $limit) !== $seek - 1) {
            $this->invalidate();
            return false;
        }

        // $arguments =    trim($this->matcher->getChunkFrom($this->nameEnd, $pos - $this->nameEnd), '() ');
        $rawarguments = trim($this->matcher->getChunkFrom($this->nameEnd, $pos - $this->nameEnd), '() ');
        // arguments can be html encoded. So, decode first
        $arguments = html_entity_decode($rawarguments);
        $this->arguments = trim($arguments);

        if ($this->matchType == self::LEGACY) {
            ++$pos;
        }

        $this->bodyStart = $pos + 1;

        if ($this->matchType == self::SHORT || $this->matchType == self::LEGACY) {
            $this->end = $this->bodyStart;
            $this->bodyStart = false;
        }

        return true;
    }

    public function findEnd($after, $limit)
    {
        if ($this->bodyStart === false) {
            return false;
        }

        $endToken = '{' . strtoupper($this->name) . '}';

        do {
            if (isset($bodyEnd)) {
                $after = $bodyEnd + 1;
            }

            if (false === $bodyEnd = $this->matcher->findText($endToken, $after, $limit)) {
                $this->invalidate();
                return false;
            }
        } while (! $this->matcher->isParsedLocation($bodyEnd));

        $this->bodyEnd = $bodyEnd;
        $this->end = $bodyEnd + strlen($endToken);

        return true;
    }

    public function inside($match)
    {
        return $this->start > $match->start
            && $this->end < $match->end;
    }

    public function replaceWith($string)
    {
        $this->matcher->performReplace($this, $string);
    }

    public function replaceWithPlugin($name, $params, $content)
    {
        $replacement = $this->buildPluginString($name, $params, $content);
        $this->replaceWith($replacement);
    }

    public function buildPluginString($name, $params, $content)
    {
        $hasBody = ! empty($content) && ! ctype_space($content);

        if (is_array($params)) {
            $parts = [];
            foreach ($params as $key => $value) {
                if ($value || $value === '0') {
                    $parts[] = "$key=\"" . str_replace('"', "\\\"", $value) . '"';
                }
            }

            $params = implode(' ', $parts);
        }

        // Replace the content
        if ($hasBody) {
            $type = strtoupper($name);
            $result = "{{$type}($params)}$content{{$type}}";
        } else {
            $plugin = strtolower($name);
            $result = "{{$plugin} $params}";
        }

        return $result;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getBody()
    {
        return $this->matcher->getChunkFrom($this->bodyStart, $this->bodyEnd - $this->bodyStart);
    }

    public function getStart()
    {
        return $this->start;
    }

    public function getEnd()
    {
        return $this->end;
    }

    public function getInitialStart()
    {
        return $this->initialstart;
    }

    public function getBodyStart()
    {
        return $this->bodyStart;
    }

    public function invalidate()
    {
        $this->matcher = false;
        $this->start = false;
        $this->end = false;
    }

    public function applyOffset($offset)
    {
        $this->start += $offset;
        $this->end += $offset;

        if ($this->nameEnd !== false) {
            $this->nameEnd += $offset;
        }

        if ($this->bodyStart !== false) {
            $this->bodyStart += $offset;
        }

        if ($this->bodyEnd !== false) {
            $this->bodyEnd += $offset;
        }
    }

    private function countUnescapedQuotes($from, $to)
    {
        $string = $this->matcher->getChunkFrom($from, $to - $from);
        $count = 0;

        $pos = -1;
        while (false !== $pos = strpos($string, '"', $pos + 1)) {
            ++$count;
            if ($pos > 0 && $string[$pos - 1] == "\\") {
                --$count;
            }
        }

        return $count;
    }

    public function changeMatcher($matcher)
    {
        $this->matcher = $matcher;
    }

    public function __toString()
    {
        return $this->matcher->getChunkFrom($this->start, $this->end - $this->start);
    }

    public function debug($level = 'X')
    {
        echo "\nMatch [$level] {$this->name} ({$this->arguments}) = {$this->getBody()}\n";
        echo "{$this->bodyStart}-{$this->bodyEnd} {$this->nameEnd} ({$this->matchType})\n";
    }
}
