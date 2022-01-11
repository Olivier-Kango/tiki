<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/*
 * Draft syntax
 {KANBAN(xaxis="tracker_field_type" yaxis="date" swimlane="tracker_field_category")}
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

function wikiplugin_kanban(string $fieldData, array $params): WikiParser_PluginOutput
{
    static $id = 0;

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

    // for perms later
    $itemObject = Tracker_Item::newItem($jit->trackerId->int());

    $xaxisField    = $definition->getFieldFromPermName($jit->xaxis->word());
    $yaxisField    = $definition->getFieldFromPermName($jit->yaxis->word());

    if (! $xaxisField || ! $yaxisField) {
        return WikiParser_PluginOutput::userError(tr('Fields not found.'));
    }

    $utilities = new Services_Tracker_Utilities();
    $items = $utilities->getItems(['trackerId' => $jit->trackerId->int()]);

    $swimlaneField = $definition->getFieldFromPermName($jit->swimlane->word());

    $fieldFactory = $definition->getFieldFactory();

    $columnsHandler = $fieldFactory->getHandler($xaxisField);
    $fieldData = $columnsHandler->getFieldData();
    $columns = [];

    if ($columnsHandler->getConfiguration('type') === 'd') {
        $columns = $fieldData['possibilities'];
    } elseif ($columnsHandler->getConfiguration('type') === 'e') {
        foreach ($fieldData['list'] as $categ) {
            $columns[$categ['categId']] = $categ['name'];
        }
    }

    $swimlanesHandler = $fieldFactory->getHandler($swimlaneField);
    $fieldData = $swimlanesHandler->getFieldData();
    $swimlanes = [];

    if ($swimlanesHandler->getConfiguration('type') === 'd') {
        $swimlanes = $fieldData['possibilities'];
    } elseif ($swimlanesHandler->getConfiguration('type') === 'e') {
        foreach ($fieldData['list'] as $categ) {
            $swimlanes[$categ['categId']] = $categ['name'];
        }
    }


    $smarty = TikiLib::lib('smarty');
    $smarty->assign(
        'kanbanData',
        [
            'id' => 'kanban' . ++$id,
            'trackerId' => $jit->trackerId->int(),
            'xaxisField' => $jit->xaxis->word(),
            'yaxisField' => $jit->yaxis->word(),
            'swimlaneField' => $jit->swimlane->word(),
            'titleField' => $jit->title->word(),
            'descriptionField' => $jit->description->word(),
            'items' => $items,
            'columns' => $columns,
            'swimlanes' => $swimlanes,
        ]
    );

    TikiLib::lib('header')
        ->add_jsfile('storage/public/vue-mf/root-config/vue-mf-root-config.min.js')
        ->add_jsfile('storage/public/vue-mf/kanban/vue-mf-kanban.min.js')
    ;


    return WikiParser_PluginOutput::html($smarty->fetch('wiki-plugins/wikiplugin_kanban.tpl'));
}
