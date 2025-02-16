<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

/**
 * @return array
 */
function module_tracker_input_info()
{
    return [
        'name' => tra('Tracker Input'),
        'description' => tra('Allows a dialog to be opened to create new tracker items.'),
        'prefs' => ['feature_trackers'],
        'params' => [
            'trackerId' => [
                'required' => true,
                'name' => tr('Tracker'),
                'description' => tr('Tracker ID to render'),
                'filter' => 'int',
                'profile_reference' => 'tracker',
            ],
            'textinput' => [
                'name' => tr('Text Input'),
                'description' => tr('Multiple text fields to display as part of the main form along with the label. Field names map to the permanent names in the tracker field definitions. ex: groupName(Group Name) relatedTask(Task)'),
                'filter' => 'text',
            ],
            'hiddeninput' => [
                'name' => tr('Hidden Input'),
                'description' => tr('Hidden values to be sent over to the dialog. fieldName(value)'),
                'filter' => 'text',
            ],
            'location' => [
                'name' => tr('Location Field'),
                'description' => tr('Obtain the coordinates from a nearby map and send them to the location field. In addition to the field name, :marker or :viewport can be used as the suffix. Default is :marker.'),
                'filter' => 'text',
            ],
            'streetview' => [
                'name' => tr('Capture StreetView'),
                'description' => tr('Include a button on the StreetView interface to create tracker items from the location. Requires upload image from URL and location parameter.'),
                'filter' => 'text',
            ],
            'submit' => [
                'name' => tr('Button Label'),
                'description' => tr('Alter the submit button label.'),
                'filter' => 'text',
            ],
            'success' => [
                'name' => tr('Operation to perform on success'),
                'description' => tr('Operation to perform in the following format: operationName(argument). Current operations are redirect with the URL template as the argument. @valueName@ will be replaced by the appropriate value where valueName is itemId, status or a permanent name'),
                'filter' => 'text',
            ],
            'insertmode' => [
                'name' => tr('Mode change on complete'),
                'description' => tr('Target mode to enter after dialog closes'),
                'filter' => 'text',
                'default' => '',
            ],
        ],
    ];
}

/**
 * @param $mod_reference
 * @param $module_params
 */
function module_tracker_input($mod_reference, $module_params)
{
    global $prefs;
    $smarty = TikiLib::lib('smarty');
    $trackerId = $module_params['trackerId'];

    if (! empty($trackerId)) {
        $itemObject = Tracker_Item::newItem($trackerId);
        $definition = Tracker_Definition::get($trackerId);

        if (! $itemObject->canModify()) {
            $smarty->assign(
                'tracker_input',
                [
                    'trackerId' => 0,
                    'textInput' => [],
                    'hiddenInput' => [],
                    'location' => null,
                ]
            );
            return;
        }
    }

    $textinput = isset($module_params['textinput']) ? $module_params['textinput'] : '';
    $hiddeninput = isset($module_params['hiddeninput']) ? $module_params['hiddeninput'] : '';
    $streetview = isset($module_params['streetview']) ? $module_params['streetview'] : '';
    $streetViewField = null ;
    $success = isset($module_params['success']) ? $module_params['success'] : '';
    $insertmode = isset($module_params['insertmode']) ? $module_params['insertmode'] : '';

    if ($streetview && $definition) {
        $streetViewField = $definition->getFieldFromPermName($streetview);
        if (! $streetViewField || $prefs['fgal_upload_from_source'] != 'y') {
            throw new \Exception("Error: ‘streetViewField’ missing. Check ‘streetview’ is correctly defined.");
        }
    }

    $location = null;
    $locationMode = null;
    if (isset($module_params['location'])) {
        $parts = explode(':', $module_params['location'], 2);
        $location = array_shift($parts);
        $locationMode = array_shift($parts);
        if (! $locationMode) {
            $locationMode = 'marker';
        }

        $hiddeninput .= " $location()";
    }

    preg_match_all('/(\w+)\(([^\)]+)\)/', $textinput, $parts, PREG_SET_ORDER);
    $text = [];
    foreach ($parts as $p) {
        $text[$p[1]] = tra($p[2]);
    }

    preg_match_all('/(\w+)\(([^\)]*)\)/', $hiddeninput, $parts, PREG_SET_ORDER);
    $hidden = [];
    foreach ($parts as $p) {
        $hidden[$p[1]] = $p[2];
    }

    $galleryId = null;
    if ($streetview) {
        $galleryId = TikiLib::lib('filegal')->check_user_file_gallery($streetViewField['options_array'][0]);
    }

    $operation = null;
    $operationArgument = null;
    if (preg_match("/(\w+)\(([^\)]*)\)/", $success, $parts)) {
        $operation = $parts[1];
        $operationArgument = $parts[2];
    }

    $smarty->assign(
        'tracker_input',
        [
            'trackerId' => $trackerId,
            'textInput' => $text,
            'hiddenInput' => $hidden,
            'location' => $location,
            'locationMode' => $locationMode,
            'streetview' => $streetview,
            'galleryId' => $galleryId,
            'submit' => isset($module_params['submit']) ? $module_params['submit'] : tr('Create'),
            'success' => [
                'operation' => $operation,
                'argument' => $operationArgument,
            ],
            'insertMode' => $insertmode,
        ]
    );
}
