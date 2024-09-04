<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_modifier_tiki_date_timezone_from_utc($string, $format, $tz = false)
{
    $tikiDateTimezoneFromUtcModifier = new \SmartyTiki\Modifier\TikiDateTimezoneFromUtc();
    return $tikiDateTimezoneFromUtcModifier->handle($string, $format, $tz);
}
