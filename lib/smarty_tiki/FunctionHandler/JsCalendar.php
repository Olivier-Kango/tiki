<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

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

        $uniqueId = uniqid();
        $fieldName = $params['fieldname'];

        if (! isset($params['showtime'])) {
            $params['showtime'] = 'y';
        }

        if (! isset($params['timezone'])) {
            $params['timezone'] = $tikilib->get_display_timezone();
        }

        /*
        NOTE: we don't unregister the application when the component is unmounted because this action results in the component's
        resources being removed from the DOM which causes other components of the same kind to lose their resources as well.
        FIXME: Find a more efficient way to handle this for other components as well. - Merci Jacob 08/05/2024
        */
        $headerlib->add_jq_onready('
window.registerApplication({
    name: "@vue-mf/datetime-picker-" + ' . json_encode($uniqueId) . ',
    app: () => importShim("@vue-mf/datetime-picker"),
    activeWhen: () => true,
    customProps: {
        inputName: ' . json_encode($fieldName) . ',
        toInputName: ' . json_encode($params['endfieldname']) . ',
        timestamp: ' . json_encode($params['date']) . ',
        toTimestamp: ' . json_encode($params['enddate']) . ',
        timezone: ' . json_encode($params['timezone']) . ',
        timezoneFieldName: ' . json_encode($params['timezoneFieldname']) . ',
        enableTimezonePicker: ' . json_encode($params['showtimezone'] === 'y') . ',
        enableTimePicker: ' . json_encode($params['showtime'] === 'y') . ',
        goToURLOnChange: ' . json_encode($params['goto']) . ',
        language: ' . json_encode($tikilib->get_language()) . ',
        cancelText: ' . json_encode(tr('Cancel')) . ',
        selectText: ' . json_encode(tr('Select')) . ',
    },
});
');
        $appHtml = '<div id="single-spa-application:@vue-mf/datetime-picker-' . $uniqueId . '" class="wp-datetime-picker"></div>';

        return $appHtml;
    }
}
