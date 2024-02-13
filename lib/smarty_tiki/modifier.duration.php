<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_modifier_duration($string, $long = true, $maxLevel = false)
{
    $durationModifier = new \SmartyTiki\Modifier\Duration();
    return $durationModifier->handle($string, $long, $maxLevel);
}
