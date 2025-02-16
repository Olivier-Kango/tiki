<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class DatetimeRange extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;
        $smarty = \TikiLib::lib('smarty');

        if (! is_array($params) || ! isset($params['from']) || ! isset($params['to'])) {
            trigger_error(tra("Missing 'to' or 'from' parameter"));
            return;
        }

        if ($params['datetime_separator']) {
            $datetime_separator = tra($params['datetime_separator']);
        } else {
            $datetime_separator = tra("at");
        }

        if ($params['range_separator']) {
            $range_separator = tra($params['range_separator']);
        } else {
            $range_separator = tra("to");
        }

        if ($params['type'] == 'long') {
            $fromDate = smarty_modifier_tiki_date_format($params['from'], $prefs['long_date_format']);
            $fromTime = smarty_modifier_tiki_date_format($params['from'], $prefs['long_time_format']);
            $toDate = smarty_modifier_tiki_date_format($params['to'], $prefs['long_date_format']);
            $toTime = smarty_modifier_tiki_date_format($params['to'], $prefs['long_time_format']);
        } else {
            $fromDate = smarty_modifier_tiki_date_format($params['from'], $prefs['short_date_format']);
            $fromTime = smarty_modifier_tiki_date_format($params['from'], $prefs['short_time_format']);
            $toDate = smarty_modifier_tiki_date_format($params['to'], $prefs['short_date_format']);
            $toTime = smarty_modifier_tiki_date_format($params['to'], $prefs['short_time_format']);
        }

        if ($fromDate == $toDate && $prefs['tiki_same_day_time_only'] == 'y') {
            $range = $fromDate . ' ' . $datetime_separator . ' ' . $fromTime . ' ' . $range_separator . ' ' . $toTime;
        } else {
            $range = $fromDate . ' ' . $datetime_separator . ' ' . $fromTime . ' ' . $range_separator . ' ' . $toDate . ' ' . $datetime_separator . ' ' . $toTime;
        }
        return $range;
    }
}
