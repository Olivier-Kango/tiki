<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Current syntax - filters and display formatting support
 * {KANBAN(boardTrackerId=9 title=taskTask description=taskDescription column=taskResolutionStatus order=taskPriority swimlane=taskJob wip=5,3,10)}
 *   {filter field="tracker_field_taskPriority" editable="content"}
 *   {display name="tracker_field_taskTask" format="objectlink"}
 * {KANBAN}
 */
function wikiplugin_kanban_info(): array
{
    return [
        'name' => tr('Kanban'),
        'documentation' => 'PluginKanban',
        'description' => tr(''),
        'prefs' => ['wikiplugin_kanban'],
        'format' => 'html',
        'iconname' => 'th',
        'introduced' => 24,
        'params' => [
            'boardTrackerId' => [
                'name' => tr('Tracker ID'),
                'description' => tr('Id of the tracker the board is a partial representation of'),
                'since' => '24.0',
                'required' => true,
                'filter' => 'int',
                'profile_reference' => 'tracker',
            ],
            'title' => [
                'name' => tr('Card title field'),
                'description' => tr('Tracker field containing the inline editable text shown on each card.'),
                'hint' => tr('e.g. "kanbanTitle"'),
                'since' => '24.0',
                'required' => true,
                'filter' => 'word',
            ],
            'description' => [
                'name' => tr('Card description field'),
                'description' => tr('Optional text shown below the title on each card.'),
                'hint' => tr('e.g. "kanbanDescription"'),
                'since' => '24.0',
                'required' => false,
                'filter' => 'word',
            ],
            'column' => [
                'name' => tr('Column (state) field'),
                'description' => tr('Tracker field representing the columns, usually a dropdown list with options such as "Wishes", "Work" and "Done".'),
                'hint' => tr('e.g. "kanbanColumns"'),
                'since' => '24.0',
                'required' => true,
                'filter' => 'word',
            ],
            'columnValues' => [
                'name' => tr('Column acceptable values and configuration'),
                'description' => tr('Defines the number and order of columns, as well as the value, label and WiP limit for each one.  An array of semicolumn separated values, containing a coma separated arguments configuring the column.
                someValue,someAlternateTextToDisplay,null:someOtherValue,someOtherAlternateTextToDisplay,,4
                
                In order, the configuration represent the:

                1) field value in the column mapped tracker field
                2) overrideLabel If present and not "null",  the text to be displayed as the column header instead of the normal tracker field label. (For example "Done" instead of "Closed")
                3) wipLimit for the column.  Typically you will use null for the first and last column.

                null or nothing between the comas means the parameter is not set.  Necessary since the arguments are positional.

                If the whole parameter is absent (not recommended), all possible field values will be used.
                
                '),
                'hint' => tr('e.g. "someValue,someAlternateTextToDisplay,null:someOtherValue,someOtherAlternateTextToDisplay,,4"'),
                'since' => '24.0',
                'required' => false,
                'filter' => 'text',
                'separator' => ':'
            ],
            'order' => [
                'name' => tr('Card relative order field'),
                'description' => tr('Sort order for cards within a cell.  Must be a numeric field.  You will have to create it if the board represents an existing tracker.  It is not meant to be displayed to the user, or represent something global like "priority" (that would make no sense on a partial representation).  It merely means that the card is displayed above any card wifh lower value, and below any card with a higher one if displayed in the same cell.  When a card is moved board will halve the value of the two surrounding cards to compute the new value.'),
                'hint' => tr('e.g. "kanbanOrder"'),
                'since' => '24.0',
                'required' => true,
                'filter' => 'word',
            ],
            'swimlane' => [
                'name' => tr('Swimlane (row) field'),
                'description' => tr('Tracker field representing the "rows" or "swimlanes" of the board. Can be any field with discrete values.  Usually represents a client, a project, or a team member.  Note:  A kanban board can have multiple rows, but these rows aren\'t independent, they share the same possible States and Wip limits.  If what you want is completely independent "rows", create two boards on the same tracker, with different filters.'),
                'hint' => tr('e.g. "kanbanSwimlanes'),
                'since' => '24.0',
                'required' => false,
                'filter' => 'word',
            ],
            'swimlaneValues' => [
                'name' => tr('Swimlanes acceptable values and configuration'),
                'description' => tr('Similar to columnValues, except there is no WiP limit.

                If the whole parameter is absent, all possible field values will be used.
                
                '),
                'hint' => tr('e.g. "someValue,someAlternateTextToDisplay:someOtherValue"'),
                'since' => '24.0',
                'required' => false,
                'filter' => 'text',
                'separator' => ':'
            ],
        ],
    ];
}

function _map_field($fieldHandler, string $fieldValuesParamName, $fieldValuesParam, string $fieldPermName, array $fieldDefaultConfig): array
{
    $fieldPossibleValues = wikiplugin_kanban_format_list($fieldHandler);
    //echo '<pre>';print_r($fieldPossibleValues);echo '</pre>';
    $fieldValuesMap = [];
    foreach ($fieldPossibleValues as $info) {
        $fieldValuesMap[$info['value']] = $info;
    }
    $fieldInfo = [];
    if (!$fieldValuesParam) {
        foreach ($fieldValuesMap as $key => $column) {
            $fieldInfo[$key] = array_merge($fieldDefaultConfig, $column);
        }
    } else {

        foreach ($fieldValuesParam as $key => $fieldParams) {
            $fieldParamsArray = explode(',', $fieldParams);
            //column value
            if (!$fieldParamsArray[0]) {
                return WikiParser_PluginOutput::userError(tra('Parameter "%0=%1" has an empty column value (first parameter after the :) at index %2.  Possible values are %3', '', false, [
                    $fieldValuesParamName,
                    implode(':', $fieldValuesParam),
                    $key,
                    implode(',', array_keys($fieldValuesMap))
                ]));
            }
            $fieldValue = trim($fieldParamsArray[0]);
            if (!$fieldValuesMap[$fieldValue]) {
                return WikiParser_PluginOutput::userError(tra('Column value "%0" specified in parameter "%1=%2" is not found in tracker field "%3".  Possible values are %4', '', false, [
                    $fieldValue,
                    $fieldValuesParamName,
                    implode(':', $fieldValuesParam),
                    $fieldPermName,
                    implode(',', array_keys($fieldValuesMap))
                ]));
            }
            $fieldInfo[$fieldValue] = array_merge($fieldDefaultConfig, $fieldValuesMap[$fieldValue]);
            //Override column label
            if ($fieldParamsArray[1] && $fieldParamsArray[1] !== 'null') {
                $fieldInfo[$fieldValue]['title'] = trim($fieldParamsArray[1]);
            }
            //wip limit
            if ($fieldParamsArray[2] && $fieldParamsArray[2] !== 'null') {
                if (!is_numeric($fieldParamsArray[2])) {
                    throw new Exception(tra('Wip limit value "%0" specified in parameter "%1=%2" is not numeric', '', false, [
                        $fieldParamsArray[2],
                        $fieldValuesParamName,
                        implode(':', $fieldValuesParam)
                    ]));
                }
                $wipValue = intval($fieldParamsArray[2]);
                $fieldInfo[$fieldValue]['wip'] = $wipValue;
            }
        }
    }
    return $fieldInfo;
}
function wikiplugin_kanban(string $data, array $params): WikiParser_PluginOutput
{
    global $user, $prefs;
    static $id = 0;

    if ($prefs['auth_api_tokens'] !== 'y') {
        return WikiParser_PluginOutput::userError(tr('Security -> API access is disabled but Kanban plugin needs it.'));
    }

    //set defaults
    $plugininfo = wikiplugin_kanban_info();
    $defaults = [];
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults[$key] = $param['default'] ?? null;
    }
    $params = array_merge($defaults, $params);

    $jit = new JitFilter($params);

    // Begin mapping the fields
    $trackerId = $jit->boardTrackerId->int();
    if (!$trackerId) {
        return WikiParser_PluginOutput::userError(tr('Tracker not specified in param "boardTrackerId".'));
    }

    $mappedTrackerDefinition = Tracker_Definition::get($jit->boardTrackerId->int());
    if (!$mappedTrackerDefinition) {
        return WikiParser_PluginOutput::userError(tr('Tracker not found.'));
    }

    $boardFields = [
        'title' => $jit->title->word(),
        'description' => $jit->description->word(),
        'column' => $jit->column->word(),
        'order' => $jit->order->word(),
        'swimlane' => $jit->swimlane->word(),
    ];

    foreach ($boardFields as $key => $field) {
        if (!$field) {
            return WikiParser_PluginOutput::userError(tr('Param "%0" is missing', $key));
        }
        $fieldDef = $mappedTrackerDefinition->getFieldFromPermName($field);
        if (!$fieldDef) {
            return WikiParser_PluginOutput::userError(tra('Tracker field with permName "%0" not found for param "%1".  Possible fields are %2', '', false, [
                $field,
                $key,
                implode(',', array_column($mappedTrackerDefinition->getFields(), 'permName'))
            ]));
        }
        $boardFields[$key] = $fieldDef;
    }

    $fieldFactory = $mappedTrackerDefinition->getFieldFactory();

    $columnsHandler = $fieldFactory->getHandler($boardFields['column']);
    $columnFieldPermName = $boardFields['column']['permName'];
    $columnsInfo = _map_field(
        $columnsHandler,
        'columnValues',
        $jit->columnValues->text(),
        $columnFieldPermName,
        [
            'wip' => null
        ]
    );


    $swimlanesHandler = $fieldFactory->getHandler($boardFields['swimlane']);
    $swimlaneFieldPermName = $boardFields['swimlane']['permName'];
    $swimlanesInfo = _map_field(
        $swimlanesHandler,
        'swimlaneValues',
        $jit->swimlaneValues->text(),
        $swimlaneFieldPermName,
        [
            'wip' => null
        ]
    );

    //echo '<pre>';print_r($swimlanesInfo);echo '</pre>';
    //END mapping the fields

    //Begin mapping the cards
    $query = new Search_Query();
    $query->filterType('trackeritem');
    $query->filterContent((string)$jit->boardTrackerId->int(), 'tracker_id');
    //print_r(array_keys($columnsInfo));
    if ($jit->columnValues->text()) {
        $query->filterContent(implode(' OR ', array_keys($columnsInfo)), 'tracker_field_' . $columnFieldPermName);
    }
    if ($jit->swimlaneValues->text()) {
        $query->filterContent(implode(' OR ', array_keys($swimlanesInfo)), 'tracker_field_' . $swimlaneFieldPermName);
    }

    $unifiedsearchlib = TikiLib::lib('unifiedsearch');
    $unifiedsearchlib->initQuery($query);

    $matches = WikiParser_PluginMatcher::match($data);

    $builder = new Search_Query_WikiBuilder($query);
    $builder->apply($matches);

    if (!$index = $unifiedsearchlib->getIndex()) {
        return WikiParser_PluginOutput::userError(tr('Unified search index not found.'));
    }

    $result = [];
    foreach ($query->scroll($index) as $row) {
        $result[] = $row;
    }

    $result = Search_ResultSet::create($result);
    $result->setId('wpkanban-' . $id);

    $resultBuilder = new Search_ResultSet_WikiBuilder($result);
    $resultBuilder->apply($matches);

    $data .= '{display name="object_id"}';
    $plugin = new Search_Formatter_Plugin_ArrayTemplate($data);
    $usedFields = array_keys($plugin->getFields());
    foreach ($boardFields as $key => $field) {
        if (!in_array('tracker_field_' . $field['permName'], $usedFields) && !in_array($field['permName'], $usedFields)) {
            if ($field['type'] == 'e') {
                $data .= '{display name="tracker_field_' . $field['permName'] . '" format="categorylist" singleList="y" separator=" "}';
            } else {
                $data .= '{display name="tracker_field_' . $field['permName'] . '" default=" "}';
            }
        }
    }
    $objectIdField = ['object_id' => ['permName' => 'object_id']];

    $plugin = new Search_Formatter_Plugin_ArrayTemplate($data);
    $plugin->setFieldPermNames(array_merge($boardFields, $objectIdField));

    $builder = new Search_Formatter_Builder();
    $builder->setId('wpkanban-' . $id);
    $builder->setCount($result->count());
    $builder->apply($matches);
    $builder->setFormatterPlugin($plugin);

    $formatter = $builder->getFormatter();
    $entries = $formatter->getPopulatedList($result, false);
    $entries = $plugin->renderEntries($entries);
    //echo '<pre>';print_r($entries);echo '</pre>';
    $boardCards = [];


    $caslAbilities = []; //Spec at https://casl.js.org/
    $trackerPerms = Perms::get(['type' => 'tracker', 'object' => $trackerId]);



    if ($trackerPerms['tiki_p_create_tracker_items']) {

        //We need this to check field permissions.
        $trackerItem = Tracker_Item::newItem($trackerId);
        $updatableFields = [];
        foreach ($boardFields as $field) {
            if ($trackerItem->canModifyField($field['fieldId'])) {
                $updatableFields[] =  $field['permName'];
            }
        }
        $caslAbilities[] =
            [
                'action' => 'create',
                'subject' => 'Tracker_Item',
                'fields' => $updatableFields
            ];
    }
    foreach ($entries as $row) {

        //$trackerItem = Tracker_Item::fromInfo($row);
        //The following will cause SQL query inside a loop, but the above just doesn't work right.   We really need a proper query engine...
        $trackerItem = Tracker_Item::fromId($row['object_id']);
        $updatableFields = [];
        foreach ($boardFields as $field) {
            if ($trackerItem->canModifyField($field['fieldId'])) {
                $updatableFields[] =  $field['permName'];
            }
        }
        $trackerItemData = $trackerItem->getData();

        //echo '<pre>DATA:';print_r($trackerItemData);echo '</pre>';

        $caslAbilities[] =
            [
                'action' => 'update',
                'subject' => 'Tracker_Item',
                'fields' => $updatableFields,
                'conditions' => ['itemId' => $trackerItem->getId()]
            ];

        $caslAbilities[] =
            [
                'action' => 'delete',
                'subject' => 'Tracker_Item',
                'conditions' => ['itemId' => $trackerItem->getId()]
            ];

        //if ($perms['tiki_p_create_tracker_items'] == 'n' && empty($itemId)) {

        //We don't use $row[$swimlaneFieldPermName], because it's the title, not the value
        $swimlaneValue = $trackerItemData['fields'][$swimlaneFieldPermName];
        //We really should NOT be providing this id, since the api writes using the value
        $swimlaneDatabasePrimaryKey = $swimlanesInfo[$swimlaneValue]['id'];

        //We don't use $row[$columnFieldPermName], because it's the title, not the value
        $columnValue = $trackerItemData['fields'][$columnFieldPermName];
        //We really should NOT be providing this id, since the api writes using the value
        $columnDatabasePrimaryKey = $columnsInfo[$columnValue]['id'];

        $boardCards[] = [
            'id' => $row['object_id'],
            'title' => $row[$boardFields['title']['permName']],
            'description' => $row[$boardFields['description']['permName']],
            'row' => $swimlaneDatabasePrimaryKey,
            'column' => $columnDatabasePrimaryKey,
            'sortOrder' => $row[$boardFields['order']['permName']],
        ];
    }

    $token = TikiLib::lib('api_token')->createToken([
        'type' => 'kanban',
        'user' => $user,
        'expireAfter' => strtotime("+1 hour"),
    ]);

    $smarty = TikiLib::lib('smarty');
    $kanbanData =
        [
            'id' => 'kanban' . ++$id,
            'accessToken' => $token['token'],
            'trackerId' => $jit->boardTrackerId->int(),
            'xaxisField' => $jit->column->word(),
            'yaxisField' => $jit->order->word(),
            'swimlaneField' => $jit->swimlane->word(),
            'titleField' => $jit->title->word(),
            'descriptionField' => $jit->description->word(),
            'columns' => array_values($columnsInfo),
            'rows' => array_values($swimlanesInfo),
            'cards' => $boardCards,
            'user' => $user,
            'CASLAbilityRules' => $caslAbilities
        ];
    //echo ("<pre>");print_r($kanbanData);echo ("</pre>");
    $smarty->assign(
        'kanbanData',
        $kanbanData

    );

    TikiLib::lib('header')
        ->add_jsfile('storage/public/vue-mf/root-config/vue-mf-root-config.min.js')
        ->add_jsfile('storage/public/vue-mf/kanban/vue-mf-kanban.min.js');

    $out = str_replace(['~np~', '~/np~'], '', $formatter->renderFilters());

    $out .= $smarty->fetch('wiki-plugins/wikiplugin_kanban.tpl');

    return WikiParser_PluginOutput::html($out);
}


function wikiplugin_kanban_format_list($handler)
{
    $fieldData = $handler->getFieldData();
    //echo '<pre>';print_r($fieldData);echo '</pre>';
    $list = $formatted = [];
    if ($handler->getConfiguration('type') === 'd') {
        $list = $fieldData['possibilities'];
    } elseif ($handler->getConfiguration('type') === 'e') {
        foreach ($fieldData['list'] as $categ) {
            $list[$categ['categId']] = $categ['name'];
        }
    }
    $non_numeric_keys = array_filter(array_keys($list), function ($key) {
        return !is_numeric($key);
    });
    $realKey = 1;
    foreach ($list as $key => $val) {
        if ($non_numeric_keys) {
            $id = $realKey++;
        } else {
            $id = $key;
        }
        $formatted[] = ['id' => $id, 'title' => $val, 'value' => $key];
    }
    return $formatted;
}
