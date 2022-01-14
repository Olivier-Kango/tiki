<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/*
 * Draft syntax - filters support, TODO - output block
 {KANBAN(trackerId=123 xaxis="tracker_field_type" yaxis="date" swimlane="tracker_field_category")}
   {filter type="trackeritem"}
   {filter field="tracker_id" content="42"}
   {OUTPUT()}
     {display name="title" format="objectlink"}
     ''{display name="date"}''
     {display name="tracker_field_type" format="trackerrender"}
   {OUTPUT}
 {KANBAN}
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
                'since' => '10.0',
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
        $defaults[$key] = $param['default'];
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

    // TODO: support OUTPUT blocks and field formatting
    $items = [];
    foreach ($query->scroll($index) as $row) {
        $swimlane = $row['tracker_field_'.$fields['swimlane']['permName']];
        if (is_array($swimlane)) {
            $swimlane = array_shift($swimlane);
        }
        foreach ($swimlanes as $sw) {
            if ($swimlane == $sw['id'] || $swimlane == $sw['title']) {
                $swimlane = $sw['id'];
                break;
            }
        }
        $column = $row['tracker_field_'.$fields['xaxis']['permName']];
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
            'title' => $row['tracker_field_'.$fields['title']['permName']],
            'description' => $row['tracker_field_'.$fields['description']['permName']],
            'row' => $swimlane,
            'column' => $column,
            'sortOrder' => $row['tracker_field_'.$fields['yaxis']['permName']],
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
        ]
    );

    TikiLib::lib('header')
        ->add_jsfile('storage/public/vue-mf/root-config/vue-mf-root-config.min.js')
        ->add_jsfile('storage/public/vue-mf/kanban/vue-mf-kanban.min.js')
    ;


    return WikiParser_PluginOutput::html($smarty->fetch('wiki-plugins/wikiplugin_kanban.tpl'));
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
            $key = $realKey++;
        }
        $formatted[] = ['id' => $key, 'title' => $val];
    }
    return $formatted;
}