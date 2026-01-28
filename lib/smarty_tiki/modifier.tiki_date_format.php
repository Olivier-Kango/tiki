<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @param string $string the string representing the date to be formatted.
 * @param string $format the desired date format.
 * @param string|false $_user if specified, use this user's timezone instead of the current user's, if specified forceTimezone must be false.
 * @param string|false $forceTimezone the time zone to be applied. Can be a timezone identifier or false, if user is not false forceTimezone must always be false.
 *
 * @return string
 */

function smarty_modifier_tiki_date_format(string $string, string $format, string|false $_user = false, string|false $forceTimezone = false): string
{
    $tikiDateFormatModifier = new \SmartyTiki\Modifier\TikiDateFormat();
    return $tikiDateFormatModifier->handle($string, $format, $_user, $forceTimezone);
}
