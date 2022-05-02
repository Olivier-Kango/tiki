<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

/**
 * @return array
 */
function module_reading_time_info()
{
    return [
        'name' => tra('Reading Time'),
        'description' => tra('Displays the estimated reading time for the words on a Wiki page (inner modules are excluded). To avoid displaying empty module on non wiki page object, set the module Visibility,Section to wiki.'),
        'prefs' => ['feature_wiki'],
        'params' => [
            'wordsPerMinutes' => [
                'name' => tra('Words per minute'),
                'description' => tra('The number of words human can read per minute.'),
                'filter' => 'int',
                'default' => '200',
            ],
            'minTimeThreshold' => [
                'name' => tra('Minimum threshold'),
                'description' => tra('value in minutes below which it is not needed to display the time. Set to 0 for none.'),
                'filter' => 'int',
                'default' => '1',
            ],
            'minTimeThresholdText' => [
                'name' => tra('Minimum threshold text'),
                'description' => tra('Text displayed when the reading time is below the minimum threshold value.'),
                'filter' => 'text',
                'default' => 'Reading time: Less than a minute',
            ],
            'maxTimeThreshold' => [
                'name' => tra('Maximum threshold'),
                'description' => tra('value in minutes above which it is not needed to display the time. Set to 0 for none.'),
                'filter' => 'int',
                'default' => '30',
            ],
            'maxTimeThresholdText' => [
                'name' => tra('Maximum threshold text'),
                'description' => tra('Text displayed when the reading time is above the maximum threshold value.'),
                'filter' => 'text',
                'default' => 'Reading time: More than 30 minutes',
            ],
            'timePrefixText' => [
                'name' => tra('Text before the reading time'),
                'description' => tra('Text displayed before the result (the reading time).'),
                'filter' => 'text',
                'default' => 'Approximate reading time:',
            ],
            'timePostfixText' => [
                'name' => tra('Text after the reading time'),
                'description' => tra('Text displayed after the result (the reading time).'),
                'filter' => 'text',
                'default' => 'min.',
            ],
            'timeMinutesOnly' => [
                'name' => tra('Displays minutes only'),
                'description' => tra('Will not displays the seconds and round up the result if the value is bigger than 30s.'),
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => tra(''), 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
        ],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_reading_time($mod_reference, &$module_params)
{
    global $prefs;
    $smarty = TikiLib::lib('smarty');

    if ($module_params['minTimeThreshold'] == '0') {
        $module_params['minTimeThreshold'] = 'none';
    }

    if ($module_params['maxTimeThreshold'] == '0') {
        $module_params['maxTimeThreshold'] = 'none';
    }

    $info = module_reading_time_info();
    $defaults = [];
    foreach ($info['params'] as $key => $param) {
        $defaults[$key] = $param['default'];
    }

    $module_params = array_merge($defaults, array_filter($module_params));
    // Template does most of the work
}