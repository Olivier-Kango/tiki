<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function smarty_modifier_tiki_long_time($string)
{
    global $prefs;
    $smarty = TikiLib::lib('smarty');
    $smarty->loadPlugin('smarty_modifier_tiki_date_format');
    return smarty_modifier_tiki_date_format($string, $prefs['long_time_format']);
}
