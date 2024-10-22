<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use ThemeLib;

/**
 * @param $params
 *               - fieldname: name attribute for the input element
 *               - date: date to display in the input field; default is now
 *               - enddate: second date to display in the input field (for date ranges)
 *               - endfieldname: name attribute for the second input element (for date ranges)
 *               - showtime: show timepicker in addition to date
 *               - goto:
 *               - notAfter:
 *               - notBefore:
 *               - timezone
 *               - timezoneFieldname
 *
 * @param $smarty
 *
 * @return string
 * @throws Exception
 */
class JsCalendar extends Base
{
    public function handle($params, Template $template)
    {
        $tikilib = \TikiLib::lib('tiki');
        $headerlib = \TikiLib::lib('header');

        $headerlib->add_js_module("import '@vue-widgets/datetime-picker';");
        list($theme_active, $theme_option_active) = ThemeLib::getActiveThemeAndOption();
        $theme_css = ThemeLib::getThemeCssFilePath($theme_active, $theme_option_active);
        // If a non-existent theme option is set, the css file path will be null
        if (! $theme_css) {
            $theme_css = ThemeLib::getThemeCssFilePath($theme_active);
        }

        if (! isset($params['showtime'])) {
            $params['showtime'] = 'y';
        }

        $fieldName = $params['fieldname'];
        $enableTimezonePicker = $params['showtimezone'] === 'y' ? 1 : 0;
        $enableTimePicker = $params['showtime'] === 'y' ? 1 : 0;

        if (! isset($params['timezone'])) {
            $params['timezone'] = $tikilib->get_display_timezone();
        }

        return "
        <datetime-picker input-name=\"{$fieldName}\" theme-css=\"{$theme_css}\" id=\"{$params['id']}\" to-input-name=\"{$params['endfieldname']}\" timestamp=\"{$params['date']}\" to-timestamp=\"{$params['enddate']}\" timezone=\"{$params['timezone']}\" timezone-field-name=\"{$params['timezoneFieldname']}\" enable-timezone-picker=\"{$enableTimezonePicker}\" enable-time-picker=\"{$enableTimePicker}\" go-to-url-on-change=\"{$params['goto']}\" language=\"{$tikilib->get_language()}\" cancel-text=\"Cancel\" select-text=\"Select\"></datetime-picker>
        ";
    }
}
