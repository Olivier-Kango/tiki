<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     ternary
 * Purpose:  map true and false to first and second parameter respectively
 * -------------------------------------------------------------
 */
function smarty_modifier_ternary($input, $true = '', $false = '')
{
    return $input ? $true : $false;
}
