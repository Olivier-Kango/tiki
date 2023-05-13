<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiParser\Markdown\Node;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * Represents an anchor link for collapsible heading
 */
class CollapsibleLink extends AbstractInline
{
    private string $id;
    private bool $open;

    public function __construct(string $id, bool $open)
    {
        parent::__construct();

        $this->id = $id;
        $this->open = $open;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOpen(): bool
    {
        return $this->open;
    }
}
