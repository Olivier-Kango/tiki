<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\WikiParser\Markdown\Node;

use League\CommonMark\Node\Block\AbstractBlock;

class CollapsibleHeading extends AbstractBlock
{
    private int $level;
    private bool $open;

    public function __construct(int $level, bool $open)
    {
        parent::__construct();

        $this->level = $level;
        $this->open = $open;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getOpen(): int
    {
        return $this->open;
    }

    public function setOpen(int $open): void
    {
        $this->open = $open;
    }
}
