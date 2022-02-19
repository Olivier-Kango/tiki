<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Current syntax - filters and display formatting support
 * {KANBAN(trackerId=9 title=taskTask description=taskDescription xaxis=taskResolutionStatus yaxis=taskPriority swimlane=taskJob wip=5,3,10)}
 *   {filter field="tracker_field_taskPriority" editable="content"}
 *   {display name="tracker_field_taskTask" format="objectlink"}
 * {KANBAN}
 */
function wikiplugin_kanban_info(): array
{
    return [
        'name' => tr('Kanban'),
        'description' => tr(''),
        'prefs' => ['wikiplugin_kanban'],
        'format' => 'html',
        'iconname' => 'th',
        'introduced' => 24,
        'params' => [
            'trackerId' => [
                'name' => tr('Tracker ID'),
                'description' => tr('Tracker to search from'),
                'since' => '24.0',
                'required' => false,
                'default' => 0,
                'filter' => 'int',
                'profile_reference' => 'tracker',
            ],
            'title' => [
                'name' => tr('Title Field'),
                'description' => tr('Title text on each card.'),
                'hint' => tr('e.g. "kanbanTitle"'),
                'since' => '24.0',
                'required' => true,
                'filter' => 'word',
            ],
            'description' => [
                'name' => tr('Card description'),
                'description' => tr(''),
                'hint' => tr('e.g. "kanbanDescription"'),
                'since' => '24.0',
                'required' => false,
                'filter' => 'word',
            ],
            'xaxis' => [
                'name' => tr('X Axis Field'),
                'description' => tr('The columns, usually a dropdown list with options such as "Wishes", "Work" and "Done".'),
                'hint' => tr('e.g. "kanbanColumns"'),
                'since' => '24.0',
                'required' => true,
                'filter' => 'word',
            ],
            'yaxis' => [
                'name' => tr('Y Axis Field'),
                'description' => tr('Sort order for cards within a cell, could be date or a numeric (sortable) field'),
                'hint' => tr('e.g. "kanbanOrder"'),
                'since' => '24.0',
                'required' => true,
                'filter' => 'word',
            ],
            'swimlane' => [
                'name' => tr('Swimlane Field'),
                'description' => tr('Defines the "rows" or "swimlanes". Can be any list type field'),
                'hint' => tr('e.g. "kanbanSwimlanes'),
                'since' => '24.0',
                'required' => false,
                'filter' => 'word',
            ],
            'wip' => [
                'name' => tr('WiP Limit'),
                'description' => tr('Work in progress limit is a comma-separated list of numbers defining the WiP limits for every column. If you need a single limit for the whole board, specify just one number here.'),
                'since' => '24.0',
                'required' => false,
                'filter' => 'text',
            ],
        ],
    ];
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
    $definition = Tracker_Definition::get($jit->trackerId->int());

    if (! $definition) {
        return WikiParser_PluginOutput::userError(tr('Tracker not found.'));
    }

    $fields = [
        'title' => $jit->title->word(),
        'description' => $jit->description->word(),
        'xaxis' => $jit->xaxis->word(),
        'yaxis' => $jit->yaxis->word(),
        'swimlane' => $jit->swimlane->word(),
    ];

    foreach ($fields as $key => $field) {
        $field = $definition->getFieldFromPermName($field);
        if (! $field) {
            return WikiParser_PluginOutput::userError(tra('Field not found: %0', $key));
        }
        $fields[$key] = $field;
    }

    $fieldFactory = $definition->getFieldFactory();

    $columnsHandler = $fieldFactory->getHandler($fields['xaxis']);
    $columns = wikiplugin_kanban_format_list($columnsHandler);

    $wip = $jit->wip->text();
    if (is_numeric($wip)) {
        $wip = array_fill(0, count($columns), $wip);
    } elseif ($wip) {
        $wip = preg_split('/\s*,\s*/', trim($wip));
    }
    if ($wip) {
        foreach ($columns as $key => $column) {
            $columns[$key]['wip'] = intval($wip[$key] ?? 1);
        }
    }

    $swimlanesHandler = $fieldFactory->getHandler($fields['swimlane']);
    $swimlanes = wikiplugin_kanban_format_list($swimlanesHandler);

    $query = new Search_Query();
    $query->filterType('trackeritem');
    $query->filterContent((string)$jit->trackerId->int(), 'tracker_id');

    $unifiedsearchlib = TikiLib::lib('unifiedsearch');
    $unifiedsearchlib->initQuery($query);

    $matches = WikiParser_PluginMatcher::match($data);

    $builder = new Search_Query_WikiBuilder($query);
    $builder->apply($matches);

    if (! $index = $unifiedsearchlib->getIndex()) {
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
    foreach ($fields as $key => $field) {
        if (! in_array('tracker_field_' . $field['permName'], $usedFields) && ! in_array($field['permName'], $usedFields)) {
            if ($field['type'] == 'e') {
                $data .= '{display name="tracker_field_' . $field['permName'] . '" format="categorylist" singleList="y" separator=" "}';
            } else {
                $data .= '{display name="tracker_field_' . $field['permName'] . '" default=" "}';
            }
        }
    }
    $fields['object_id'] = ['permName' => 'object_id'];
    $plugin = new Search_Formatter_Plugin_ArrayTemplate($data);
    $plugin->setFieldPermNames($fields);

    $builder = new Search_Formatter_Builder();
    $builder->setId('wpkanban-' . $id);
    $builder->setCount($result->count());
    $builder->apply($matches);
    $builder->setFormatterPlugin($plugin);

    $formatter = $builder->getFormatter();
    $entries = $formatter->getPopulatedList($result, false);
    $entries = $plugin->renderEntries($entries);

    $items = [];
    foreach ($entries as $row) {
        $swimlane = $row[$fields['swimlane']['permName']];
        if (is_array($swimlane)) {
            $swimlane = array_shift($swimlane);
        }
        foreach ($swimlanes as $sw) {
            if ($swimlane == $sw['id'] || $swimlane == $sw['title']) {
                $swimlane = $sw['id'];
                break;
            }
        }
        $column = $row[$fields['xaxis']['permName']];
        if (is_array($column)) {
            $column = array_shift($column);
        }
        $found = false;
        foreach ($columns as $col) {
            if ($column == $col['id'] || $column == $col['title']) {
                $found = true;
                $column = $col['id'];
                break;
            }
        }
        if (! $found) {
            continue;
        }
        $items[] = [
            'id' => $row['object_id'],
            'title' => $row[$fields['title']['permName']],
            'description' => $row[$fields['description']['permName']],
            'row' => $swimlane,
            'column' => $column,
            'sortOrder' => $row[$fields['yaxis']['permName']],
        ];
    }

    $token = TikiLib::lib('api_token')->createToken([
        'type' => 'kanban',
        'user' => $user,
        'expireAfter' => strtotime("+1 hour"),
    ]);

    $smarty = TikiLib::lib('smarty');
    $smarty->assign(
        'kanbanData',
        [
            'id' => 'kanban' . ++$id,
            'accessToken' => $token['token'],
            'trackerId' => $jit->trackerId->int(),
            'xaxisField' => $jit->xaxis->word(),
            'yaxisField' => $jit->yaxis->word(),
            'swimlaneField' => $jit->swimlane->word(),
            'titleField' => $jit->title->word(),
            'descriptionField' => $jit->description->word(),
            'columns' => $columns,
            'rows' => $swimlanes,
            'cards' => $items,
            'user' => $user
        ]
    );

    TikiLib::lib('header')
        ->add_jsfile('storage/public/vue-mf/root-config/vue-mf-root-config.min.js')
        ->add_jsfile('storage/public/vue-mf/kanban/vue-mf-kanban.min.js')
    ;

    $out = str_replace(['~np~', '~/np~'], '', $formatter->renderFilters());

    $out .= $smarty->fetch('wiki-plugins/wikiplugin_kanban.tpl');

    return WikiParser_PluginOutput::html($out);
}


function wikiplugin_kanban_format_list($handler)
{
    $fieldData = $handler->getFieldData();
    $list = $formatted = [];
    if ($handler->getConfiguration('type') === 'd') {
        $list = $fieldData['possibilities'];
    } elseif ($handler->getConfiguration('type') === 'e') {
        foreach ($fieldData['list'] as $categ) {
            $list[$categ['categId']] = $categ['name'];
        }
    }
    $non_numeric_keys = array_filter(array_keys($list), function($key) {
        return ! is_numeric($key);
    });
    $realKey = 1;
    foreach($list as $key => $val) {
        if ($non_numeric_keys) {
            $id = $realKey++;
        } else {
            $id = $key;
        }
        $formatted[] = ['id' => $id, 'title' => $val, 'value' => $key];
    }
    return $formatted;
}
