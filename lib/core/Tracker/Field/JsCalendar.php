<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tracker_Field_JsCalendar extends Tracker_Field_DateTime
{
    /**
     * Legacy code to deal with json-encoded date and timezone value of this field from 27.0 to 27.1
     */
    private function getParsedValue(): array | string
    {
        $unparsedValue = $this->getValue() ?? $this->getConfiguration('value');
        if (is_numeric($unparsedValue)) {
            return $unparsedValue;
        }
        if (empty($unparsedValue)) {
            return $unparsedValue;
        }
        $parsed = json_decode($unparsedValue, true);
        if (is_array($parsed)) {
            return $parsed['value'];
        }
        return $unparsedValue;
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
        $params['date'] = $this->getParsedValue();
        if (empty($params['date']) && $this->getOption('useNow')) {
            $params['date'] = TikiLib::lib('tiki')->now;
        }

        $params['notBefore'] = $this->getOption('notBefore') ? '#trackerinput_' . $this->getOption('notBefore') : '';
        $params['notAfter']  = $this->getOption('notAfter') ? '#trackerinput_' . $this->getOption('notAfter') : '';
        if ($this->getOption('customTimezone')) {
            $params['showtimezone'] = 'y';
            $params['timezoneFieldname'] = $this->getInsertId() . '_timezone';
        } else {
            $params['showtimezone'] = 'n';
        }
        if ($this->getOption('datetime') === 'd') {
            $params['timezone'] = 'UTC';
        } else {
            $params['timezone'] = TikiLib::lib('tiki')->get_display_timezone();
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
        $context['value'] = $value;
        return parent::renderInnerOutput($context);
    }
}
