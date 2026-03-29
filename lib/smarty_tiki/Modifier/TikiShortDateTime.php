<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

use TikiLib;
use SmartyTiki\TikiSmartyExtensionInterface;

class TikiShortDateTime implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'tiki_short_datetime';
    }

    /**
     * @param string $string
     * @param string $intro
     * @param string $same if set to 'n' will bypass timeago preferences. Useful when markup is illegal in date
     * @param $forceTimezone the time zone to be applied. Can be a timezone identifier or false, if user is not false forceTimezone must always be false
     *
     * @return string
     */
    public function handle($string, $intro = '', $same = 'y', $forceTimezone = false)
    {
        global $prefs;
        \TikiLib::lib('smarty'); //Load SmartyLib for side effects
        $date = smarty_modifier_tiki_date_format($string, $prefs['short_date_format'], false, $forceTimezone);
        $time = smarty_modifier_tiki_date_format($string, $prefs['short_time_format'], false, $forceTimezone);

        $intro = ! empty($intro) ? tra($intro) . ' ' : '';

        if ($prefs['jquery_timeago'] === 'y' && $same === 'y') {
            TikiLib::lib('header')->add_jq_onready('$("time.timeago").tikiTimeago();');
            return '<time class="timeago" datetime="' . TikiLib::date_format('c', $string, false, 5, false) . '">' . $date . ' ' . $time . '</time>';
        } elseif ($same != 'n' && $prefs['tiki_same_day_time_only'] == 'y' && $date == smarty_modifier_tiki_date_format(time(), $prefs['short_date_format'])) {
            //tra('on') tra('on:') tra('at') tra('at:')
            return str_replace(['on', 'On'], ['at', 'At'], $intro) . $time;
        } else {
            // if you change the separator do not forget to change the translation instruction in lib/prefs/short.php
            $time = $date . ' ' . $time;
            return $intro . ' ' . $time;
        }
    }

    /**
     * Static facade for calling this modifier from PHP code.
     */
    public static function apply($string, $intro = '', $same = 'y', $forceTimezone = false)
    {
        return (new self())->handle($string, $intro, $same, $forceTimezone);
    }
}
