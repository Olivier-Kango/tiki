<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\WikiParser\Markdown\Parser;

use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockContinueParserWithInlinesInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\InlineParserEngineInterface;
use Tiki\WikiParser\Markdown\Node\CollapsibleHeading;

class CollapsibleHeadingParser extends AbstractBlockContinueParser implements BlockContinueParserWithInlinesInterface
{
    private CollapsibleHeading $block;

    private string $content;

    public function __construct(int $level, bool $open, string $content)
    {
        $this->block = new CollapsibleHeading($level, $open);
        $this->content = $content;
    }

    public function getBlock(): CollapsibleHeading
    {
        return $this->block;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        return BlockContinue::none();
    }

    public function parseInlines(InlineParserEngineInterface $inlineParser): void
    {
        $inlineParser->parse($this->content, $this->block);
    }
}
