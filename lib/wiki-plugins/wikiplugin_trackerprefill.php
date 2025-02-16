<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_trackerprefill_info()
{
    return [
        'name' => tra('Tracker Prefill'),
        'documentation' => 'PluginTrackerPrefill',
        'description' => tra('Create a button to prefill tracker fields'),
        'prefs' => [ 'feature_trackers', 'wikiplugin_trackerprefill' ],
        'iconname' => 'trackers',
        'introduced' => 2,
        'params' => [
            'page' => [
                'required' => true,
                'name' => tra('Page'),
                'description' => tra('Tracker page name'),
                'since' => '2.0',
                'filter' => 'pagename',
                'default' => '',
                'profile_reference' => 'wiki_page',
            ],
            'label' => [
                'required' => false,
                'name' => tra('Label'),
                'description' => tra('Button Label.'),
                'since' => '2.0',
                'filter' => 'text',
                'default' => '',
            ],
            'urlparams' => [
                'required' => false,
                'name' => tra('URL Parameters'),
                'description' => tr(
                    'Parameters to pass in the URL, for example, %0&my_parameter1=123&my_parameter2=q%1',
                    '<code>',
                    '</code>'
                ),
                'since' => '6.0',
                'filter' => 'striptags',
                'default' => '',
            ],
            'field1' => [
                'required' => true,
                'name' => tra('Field 1'),
                'description' => tra('Field ID for the first field'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'value1' => [
                'required' => true,
                'name' => tra('Value 1'),
                'description' => tra('Content that should be used to prefill the field.'),
                'since' => '2.0',
                'filter' => 'striptags',
                'default' => '',
            ],
            'field2' => [
                'required' => false,
                'name' => tra('Field 2'),
                'description' => tra('Field ID for the second field'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'value2' => [
                'required' => false,
                'name' => tra('Value 2'),
                'description' => tra('Content that should be used to prefill the field.'),
                'since' => '2.0',
                'filter' => 'striptags',
                'default' => '',
            ],
            'field3' => [
                'required' => false,
                'name' => tra('Field 3'),
                'description' => tra('Field ID for the third field'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'value3' => [
                'required' => false,
                'name' => tra('Value 3'),
                'description' => tra('Content that should be used to prefill the field.'),
                'since' => '2.0',
                'filter' => 'striptags',
                'default' => '',
            ],
            'field4' => [
                'required' => false,
                'name' => tra('Field 4'),
                'description' => tra('Field ID for the fourth field'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'value4' => [
                'required' => false,
                'name' => tra('Value 4'),
                'description' => tra('Content that should be used to prefill the field.'),
                'since' => '2.0',
                'filter' => 'striptags',
                'default' => '',
            ],
            'field5' => [
                'required' => false,
                'name' => tra('Field 5'),
                'description' => tra('Field ID for the fifth field'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'value5' => [
                'required' => false,
                'name' => tra('Value 5'),
                'description' => tra('Content that should be used to prefill the field.'),
                'since' => '2.0',
                'filter' => 'striptags',
                'default' => '',
            ],
            'field6' => [
                'required' => false,
                'name' => tra('Field 6'),
                'description' => tra('Field ID for the sixth field'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'value6' => [
                'required' => false,
                'name' => tra('Value 6'),
                'description' => tra('Content that should be used to prefill the field.'),
                'since' => '2.0',
                'filter' => 'striptags',
                'default' => '',
            ],
            'field7' => [
                'required' => false,
                'name' => tra('Field 7'),
                'description' => tra('Field ID for the seventh field'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'value7' => [
                'required' => false,
                'name' => tra('Value 7'),
                'description' => tra('Content that should be used to prefill the field.'),
                'since' => '2.0',
                'filter' => 'striptags',
                'default' => '',
            ],
            'field8' => [
                'required' => false,
                'name' => tra('Field 8'),
                'description' => tra('Field ID for the eighth field'),
                'since' => '2.0',
                'filter' => 'digits',
                'default' => '',
                'profile_reference' => 'tracker_field',
            ],
            'value8' => [
                'required' => false,
                'name' => tra('Value 8'),
                'description' => tra('Content that should be used to prefill the field.'),
                'since' => '2.0',
                'filter' => 'striptags',
                'default' => '',
            ],
        ],
    ];
}

function wikiplugin_trackerprefill($data, $params)
{
    $smarty = TikiLib::lib('smarty');

    if (! isset($params['page'])) {
        Feedback::error(tr('The %0 parameter is missing', 'page'));
        return;
    }
    if (! isset($params['field1'])) {
        Feedback::error(tr('The %0 parameter is missing', 'field1'));
        return;
    }
    if (! isset($params['value1'])) {
        Feedback::error(tr('The %0 parameter is missing', 'value1'));
        return;
    }


    $prefills = [];
    foreach ($params as $param => $value) {
        if (strstr($param, 'field')) {
            $id = substr($param, strlen('field'));
            $f['fieldId'] = $value;
            $f['value'] = $params["value$id"];
            $prefills[] = $f;
        }
    }
    $smarty->assign_by_ref('prefills', $prefills);
    $smarty->assign_by_ref('params', $params);
    return $smarty->fetch('wiki-plugins/wikiplugin_trackerprefill.tpl');
}
