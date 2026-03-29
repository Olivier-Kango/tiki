<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use SmartyTiki\TikiSmartyExtensionInterface;

class CompactIsoDate implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'compactisodate';
    }

    public function handle($string)
    {
        global $tikilib;
        return $tikilib->get_compact_iso8601_datetime($string);
    }

    /**
     * Static facade for calling this modifier from PHP code.
     */
    public static function apply($string)
    {
        return (new self())->handle($string);
    }
}
