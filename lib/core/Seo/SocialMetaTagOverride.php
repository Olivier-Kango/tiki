<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Seo;

use Smarty\Filter\FilterInterface;
use Smarty\Template;

/** Decorate the existing override filter; resolution and access guards stay upstream. */
class SocialMetaTagOverride implements FilterInterface
{
    private FilterInterface $overrides;

    public function __construct(FilterInterface $overrides)
    {
        $this->overrides = $overrides;
    }

    public function filter($code, Template $template)
    {
        return SocialDescriptionFallback::synchronize($code, $this->overrides->filter($code, $template));
    }
}
