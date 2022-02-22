<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * Handler class for duration field type
 *
 * Letter key: ~DUR~
 *
 */
class Tracker_Field_Duration extends Tracker_Field_Abstract implements Tracker_Field_Synchronizable, Tracker_Field_Exportable, Tracker_Field_Filterable
{
    public static function getTypes()
    {
        return [
            'DUR' => [
                'name' => tr('Duration'),
                'description' => tr('Provide a convenient way to enter time duration in different units. It is highly advisable to turn Vue.js integration on for a better user interface.'),
                'help' => 'Duration-Tracker-Field',
                'prefs' => ['trackerfield_duration'],
                'tags' => ['basic'],
                'default' => 'y',
                'supported_changes' => ['DUR', 'n'],
                'params' => [
                    'seconds' => [
                        'name' => tr('Seconds'),
                        'description' => tr('Allow selection of seconds.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 1,
                    ],
                    'minutes' => [
                        'name' => tr('Minutes'),
                        'description' => tr('Allow selection of minutes.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 1,
                        'legacy_index' => 2,
                    ],
                    'hours' => [
                        'name' => tr('Hours'),
                        'description' => tr('Allow selection of hours.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 1,
                        'legacy_index' => 3,
                    ],
                    'days' => [
                        'name' => tr('Days'),
                        'description' => tr('Allow selection of days.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 4,
                    ],
                    'weeks' => [
                        'name' => tr('Weeks'),
                        'description' => tr('Allow selection of weeks.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 5,
                    ],
                    'months' => [
                        'name' => tr('Months'),
                        'description' => tr('Allow selection of months.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 6,
                    ],
                    'years' => [
                        'name' => tr('Years'),
                        'description' => tr('Allow selection of years.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 7,
                    ],
                    'chronometer' => [
                        'name' => tr('Chronometer'),
                        'description' => tr('Add chronometer type of UI with start/stop and reset buttons to count elapsed time.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'default' => 0,
                        'legacy_index' => 8,
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        $ins_id = $this->getInsertId();

        if (isset($requestData[$ins_id]) && is_array($requestData[$ins_id])) {
            $value = json_encode($requestData[$ins_id]);
        } elseif (isset($requestData[$ins_id])) {
            $value = $requestData[$ins_id];
        } else {
            $value = $this->getValue();
        }

        return ['value' => $value];
    }

    public function renderInnerOutput($context = [])
    {
        return $this->humanize();
    }

    public function renderInput($context = [], $params = [])
    {
        global $prefs;

        if ($prefs['vuejs_enable'] === 'n') {
            return $this->renderTemplate('trackerinput/duration.tpl', $context, [
                'amounts' => $this->denormalize(),
                'units' => array_keys(self::getFactors())
            ]);
        }

        // vue.js integration
        $headerlib = TikiLib::lib('header');

        // if ($prefs['vuejs_always_load'] === 'n') {
        //     $headerlib->add_jsfile_cdn("vendor_bundled/vendor/npm-asset/vue/dist/{$prefs['vuejs_build_mode']}");
        // }

        // $headerlib->add_jsfile('vendor_bundled/vendor/moment/moment/min/moment.min.js', true);
        // $headerlib->add_jsfile('vendor_bundled/vendor/npm-asset/moment-duration-format/lib/moment-duration-format.js');
        // $headerlib->add_jsfile('lib/vue/duration/store.js');
        $value = $this->getValue();
        if (! $value) {
            $value = 0;
        }
        $composedFieldId = $this->getComposedId($params);

        if ($this->getComposedId($params)) {
            $applicationId = $this->getComposedId($params);
        } else {
            $applicationId = 'new';
        }
   
        $headerlib->add_js('
window.registerApplication({
    name: "@vue-mf/duration-picker-" + ' . json_encode($applicationId) . ',
    app: () => System.import("@vue-mf/duration-picker"),
    activeWhen: (location) => {
        let condition = true;
        return condition;
    },
    customProps: {
        durationData: {
            inputId: ' . json_encode($composedFieldId) . ',
            inputName: ' . json_encode($this->getInsertId()) . ',
            draft: ' . json_encode($_SESSION['duration_drafts'][$composedFieldId]) . ',
            value: ' . $value . ',
            units: ' . json_encode($this->enabledUnits()) . ',
            chronometer: ' . $this->getOption("chronometer") . '
        },
    },
});
onDOMElementRemoved("single-spa-application:@vue-mf/duration-picker-" + ' . json_encode($applicationId) . ', function () {
    window.unregisterApplication("@vue-mf/duration-picker-" + ' . json_encode($applicationId) . ');
});
');
        $appHtml = '<div id="single-spa-application:@vue-mf/duration-picker-' . $applicationId . '" class="wp-duration-picker"></div>';

        return $appHtml;
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $baseKey = $this->getBaseKey();

        $out = [
            $baseKey => $typeFactory->numeric($this->getValueInSeconds()),
            $baseKey . '_text' => $typeFactory->sortable($this->humanize()),
        ];

        return $out;
    }

    public function getProvidedFields()
    {
        $baseKey = $this->getBaseKey();
        return [$baseKey, "{$baseKey}_text"];
    }

    public function importRemote($value)
    {
        return $value;
    }

    public function exportRemote($value)
    {
        return $value;
    }

    public function importRemoteField(array $info, array $syncInfo)
    {
        return $info;
    }

    public function getComposedId($params)
    {
        if (! $params['field']['fieldId'] || ! $params['field']['trackerId'] || ! $params['itemId']) {
            return null;
        } else {
            return "{$params['field']['fieldId']}{$params['field']['trackerId']}{$params['itemId']}";
        }
    }

    public function getTabularSchema()
    {
        // TODO
    }

    public function getFilterCollection()
    {
        // TODO
    }

    public static function getSortModeSql()
    {
        $parts = [];
        $factors = self::getFactors();
        foreach ($factors as $unit => $multiplier) {
            $parts[] = 'COALESCE(sttif.`value`->>"$.' . $unit . '", 0) * ' . $multiplier;
        }
        return 'IF(JSON_VALID(sttif.`value`), ' . implode('+', $parts) . ', 0)';
    }

    public static function getFactors()
    {
        return [
            'seconds' => 1,
            'minutes' => 60,
            'hours' => 3600,
            'days' => 86400,
            'weeks' => 604800,
            'months' => 2629746,
            'years' => 31556952,
        ];
    }

    private function denormalize()
    {
        $value = json_decode($this->getValue(), true);
        if (! is_array($value)) {
            $value = [];
        }
        return $value;
    }

    private function humanize()
    {
        $value = $this->denormalize();

        $output = '';
        foreach ($value as $unit => $amount) {
            // Remove s char if value is < 2
            if($amount < 2) {
                $unit = substr($unit, 0, -1);
            }
            $output .= ($output ? ', ' : '') . "$amount ".tra($unit);
        }

        return $output;
    }

    private function getValueInSeconds()
    {
        $factors = self::getFactors();

        $value = 0;
        foreach ($this->denormalize() as $unit => $amount) {
            if (isset($factors[$unit])) {
                $value += floatval($amount) * $factors[$unit];
            } else {
                $value += floatval($amount);
            }
        }

        return $value;
    }

    private function enabledUnits()
    {
        return array_reverse(
            array_values(
                array_filter(
                    array_keys(self::getFactors()),
                    function ($unit) {
                        return $this->getOption($unit);
                    }
                )
            )
        );
    }
}
