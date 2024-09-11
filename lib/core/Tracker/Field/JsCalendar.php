<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tracker_Field_JsCalendar extends Tracker_Field_DateTime
{
    private function getParsedValue(): array
    {
        $value = ['date' => '', 'timezone' => ''];
        $unparsedValue = $this->getValue() ?? $this->getConfiguration('value');
        if ($unparsedValue) {
            $parsed = json_decode($unparsedValue, true);
            if (! is_array($parsed)) {
                $value['date'] = $unparsedValue;
            } else {
                $value = array_merge($value, $parsed);
            }
        }
        return $value;
    }

    public static function getManagedTypesInfo(): array
    {
        $definition = [
            'j' => [
                'name'              => tr('Date and Time (Date Picker)'),
                'description'       => tr('Provide a jQuery UI date picker to select a date and, optionally, a time.'),
                'prefs'             => ['trackerfield_jscalendar'],
                'tags'              => ['advanced'],
                'default'           => 'y',
                'supported_changes' => ['f', 'j'],
                'params'            => [
                    'useNow'          => [
                        'name'         => tr('Default value'),
                        'description'  => tr('Default date and time for new items'),
                        'filter'       => 'int',
                        'options'      => [
                            0 => tr('None (undefined)'),
                            1 => tr('Item creation date and time'),
                        ],
                        'legacy_index' => 1,
                    ],
                    'notBefore'       => [
                        'name'              => tr('Not before'),
                        'description'       => tr(
                            'Field ID from this tracker to compare the value against and validate it is not before that timestamp.'
                        ),
                        'filter'            => 'int',
                        'legacy_index'      => 2,
                        'profile_reference' => 'tracker_field',
                        'parent'            => 'input[name=trackerId]',
                        'parentkey'         => 'tracker_id',
                        'sort_order'        => 'position_nasc',
                    ],
                    'notAfter'        => [
                        'name'              => tr('Not after'),
                        'description'       => tr(
                            'Field ID from this tracker to compare the value against and validate it is not after that timestamp.'
                        ),
                        'filter'            => 'int',
                        'legacy_index'      => 3,
                        'profile_reference' => 'tracker_field',
                        'parent'            => 'input[name=trackerId]',
                        'parentkey'         => 'tracker_id',
                        'sort_order'        => 'position_nasc',
                    ],
                ],
            ],
        ];

        $parentDef = parent::getManagedTypesInfo()['f']['params'];

        // params not relevant for datepicker field
        unset($parentDef['startyear'], $parentDef['endyear'], $parentDef['blankdate']);

        $definition['j']['params'] = array_merge($definition['j']['params'], $parentDef);

        return $definition;
    }

    public function getFieldData(array $requestData = [])
    {
        global $prefs;
        if ($prefs['feature_jquery_ui'] !== 'y') {  // fall back to simple date field
            return parent::getFieldData($requestData);
        }

        $ins_id = $this->getInsertId();

        // Vue component stores as JSON array
        if (isset($requestData[$ins_id]['timezone'])) {
            $requestData[$ins_id . '_timezone'] = $requestData[$ins_id]['timezone'];
        }
        if (isset($requestData[$ins_id]['date'])) {
            $requestData[$ins_id] = $requestData[$ins_id]['date'];
        }

        $value = (isset($requestData[$ins_id]))
            ? $requestData[$ins_id]
            : $this->getValue();

        if (! empty($value) && ! is_int((int) $value)) {    // prevent corrupted date values getting saved (e.g. from inline edit sometimes)
            $value = '';
            Feedback::error(tr('Date Picker Field: "%0" is not a valid internal date value', $value));
        }

          // if local browser offset or timezone identifier is submitted, convert timestamp to server-based timezone
        if ($value && isset($requestData[$ins_id]) && ! $this->getOption('customTimezone')) {
            if (isset($requestData['useDisplayTz']) && (bool) $requestData['useDisplayTz']) {
                $requestData['tzname'] = TikiLib::lib('tiki')->get_display_timezone();
            }
            $value = TikiDate::convertWithTimezone($requestData, $value);
        }
        if ($value && isset($requestData[$ins_id]) && $this->getOption('datetime') !== 'd') {
            $timezone = isset($requestData[$ins_id . '_timezone']) ? $requestData[$ins_id . '_timezone'] : TikiLib::lib('tiki')->get_display_timezone();
            $server_offset = TikiDate::tzServerOffset($timezone, $value);
            $value -= $server_offset;
        }

        if ($this->getOption(('customTimezone')) && isset($requestData[$ins_id . '_timezone'])) {
            $value = json_encode([
                'date' => $value,
                'timezone' => $requestData[$ins_id . '_timezone'],
            ]);
        }

        return [
            'value' => $value,
        ];
    }

    public function renderInput($context = [])
    {
        global $prefs;
        if ($prefs['feature_jquery_ui'] !== 'y') {  // fall back to simple date field
            return parent::renderInput($context);
        }

        $smarty = TikiLib::lib('smarty');

        $params = [ 'fieldname' => $this->getConfiguration('ins_id') ? $this->getConfiguration('ins_id') : $this->getInsertId()];
        $params['showtime'] = $this->getOption('datetime') === 'd' ? 'n' : 'y';
        $params['date'] = $this->getParsedValue()['date'];
        if (empty($params['date']) && $this->getOption('useNow')) {
            $params['date'] = TikiLib::lib('tiki')->now;
        }

        if ($params['date']) {
            // convert to UTC to display it properly for browser based timezone
            if ($this->getOption('datetime') !== 'd') {
                // check if the date parameter contains any alphabetic characters, and # symbol help to delimit the regular expression pattern.
                if (preg_match('#[a-zA-Z]#', $params['date'])) {
                    $params['date'] = strtotime($params['date']);
                }
                $tiki_date = TikiDate::tzServerOffset(TikiLib::lib('tiki')->get_display_timezone(), $params['date']);
                $params['date'] += $tiki_date;
            }
        }

        $params['notBefore'] = $this->getOption('notBefore') ? '#trackerinput_' . $this->getOption('notBefore') : '';
        $params['notAfter']  = $this->getOption('notAfter') ? '#trackerinput_' . $this->getOption('notAfter') : '';
        if ($this->getOption('customTimezone')) {
            $params['showtimezone'] = 'y';
            $params['timezoneFieldname'] = $this->getInsertId() . '_timezone';
            $params['timezone'] = $this->getParsedValue()['timezone'];
        } else {
            $params['showtimezone'] = 'n';
            $params['timezone'] = 'UTC'; // Do not use the display timezone here, as the date is already converted to the display timezone
        }

        return smarty_function_jscalendar($params, $smarty->getEmptyInternalTemplate());
    }

    public function isValid($ins_fields_data)
    {
        if ($notBefore = $this->getOption('notBefore')) {
            $notBeforeTimestamp = $ins_fields_data[$notBefore]['value'];
            if ((string)$notBeforeTimestamp !== (string)(int)$notBeforeTimestamp) {
                $notBeforeTimestamp = strtotime($notBeforeTimestamp);
            }
            if (! $notBeforeTimestamp || $this->getValue() < $notBeforeTimestamp) {
                return tr('"%0" cannot be before "%1"', $this->getConfiguration('name'), $ins_fields_data[$notBefore]['name']);
            }
        }

        if ($notAfter = $this->getOption('notAfter')) {
            $notAfterTimestamp = $ins_fields_data[$notAfter]['value'];
            if ((string)$notAfterTimestamp !== (string)(int)$notAfterTimestamp) {
                $notAfterTimestamp = strtotime($notAfterTimestamp);
            }
            if (! $notAfterTimestamp || $this->getValue() > $notAfterTimestamp) {
                return tr('"%0" cannot be after "%1"', $this->getConfiguration('name'), $ins_fields_data[$notAfter]['name']);
            }
        }

        return true;
    }

    public function renderInnerOutput($context = [])
    {
        $value = $this->getParsedValue();

        $configTzOffset = TikiDate::tzServerOffset(TikiLib::lib('tiki')->get_display_timezone(), $value['date']);
        $dateInConfigTz = (int) $value['date'] + (int) $configTzOffset;
        $context['value'] = $value['date'];
        if (! $this->getOption('customTimezone')) {
            return parent::renderInnerOutput($context);
        }

        $tzOffset = TikiDate::tzServerOffset($value['timezone'], $value['date']);
        $dateInCustomTz = (int) $value['date'] + (int) $tzOffset;

        switch ($this->getOption('outputTimezone')) {
            case '0':
                $output = parent::renderInnerOutput($context);
                break;

            case '1':
                $context['value'] = $dateInCustomTz;
                $output = parent::renderInnerOutput($context) . ' (' . $value['timezone'] . ')';
                break;

            case '2':
                $output = parent::renderInnerOutput($context) . ' (' . TikiLib::lib('tiki')->get_display_timezone() . ')';
                $context['value'] = $dateInCustomTz;
                $output .= ' | ' . parent::renderInnerOutput($context) . ' (' . $value['timezone'] . ')';
                break;

            default:
                $output = parent::renderInnerOutput($context);
                break;
        }

        return $output;
    }
}
