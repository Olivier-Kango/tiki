<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_modifier_money_format($number, $locale, $currency, $format = '%(#10n', $display = 0)
{
    $moneyFormatModifier = new \SmartyTiki\Modifier\MoneyFormat();
    return $moneyFormatModifier->handle($number, $locale, $currency, $format, $display);
}
