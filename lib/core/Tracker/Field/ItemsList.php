<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for ItemsList
 *
 * Letter key: ~l~
 *
 */
class Tracker_Field_ItemsList extends \Tracker\Field\AbstractItemField implements \Tracker\Field\ExportableInterface, \Tracker\Field\FilterableInterface
{
    private static $itemValuesLocalCache = [];

    public static function getManagedTypesInfo(): array
    {
        return [
            'l' => [
                'name' => tr('Items List'),
                'description' => tr('Display a list of field values from another tracker that has a relation with this tracker.'),
                'readonly' => true,
                'help' => 'Items-List-and-Item-Link-Tracker-Fields',
                'prefs' => ['trackerfield_itemslist'],
                'tags' => ['advanced'],
                'default' => 'y',
                'params' => [
                    'trackerId' => [
                        'name' => tr('Tracker ID'),
                        'description' => tr('Tracker from which to list items'),
                        'filter' => 'int',
                        'legacy_index' => 0,
                        'profile_reference' => 'tracker',
                    ],
                    'fieldIdThere' => [
                        'name' => tr('Link Field ID'),
                        'description' => tr('Field ID from the other tracker containing an item link pointing to the item in this tracker or some other value to be matched.'),
                        'filter' => 'int',
                        'legacy_index' => 1,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'trackerId',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                    ],
                    'fieldIdHere' => [
                        'name' => tr('Value Field ID'),
                        'description' => tr('Field ID from this tracker matching the value in the link field ID from the other tracker if the field above is not an ItemLink or Relations. If the field chosen here is an ItemLink or Relations, Link Field ID above can be left empty.'),
                        'filter' => 'int',
                        'legacy_index' => 2,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'input[name=trackerId]',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                    ],
                    'displayFieldIdThere' => [
                        'name' => tr('Fields to display'),
                        'description' => tr('Display alternate fields from the other tracker instead of the item title'),
                        'filter' => 'int',
                        'separator' => '|',
                        'legacy_index' => 3,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'trackerId',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                    ],
                    'displayFieldIdThereFormat' => [
                        'name' => tr('Format for customising fields to display'),
                        'description' => tr('Uses the translate function to replace %0 etc with the field values. E.g. "%0 any text %1"'),
                        'filter' => 'text',
                    ],
                    'sortField' => [
                        'name' => tr('Sort Fields'),
                        'description' => tr('Order results by one or more fields from the other tracker.'),
                        'filter' => 'int',
                        'separator' => '|',
                        'legacy_index' => 6,
                        'profile_reference' => 'tracker_field',
                        'parent' => 'trackerId',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'position_nasc',
                    ],
                    'linkToItems' => [
                        'name' => tr('Display'),
                        'description' => tr('How the link to the items should be rendered'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('Value'),
                            1 => tr('Link'),
                        ],
                        'legacy_index' => 4,
                    ],
                    'status' => [
                        'name' => tr('Status Filter'),
                        'description' => tr('Limit the available items to a selected set'),
                        'filter' => 'alpha',
                        'options' => [
                            'opc' => tr('all'),
                            'o' => tr('open'),
                            'p' => tr('pending'),
                            'c' => tr('closed'),
                            'op' => tr('open, pending'),
                            'pc' => tr('pending, closed'),
                        ],
                        'legacy_index' => 5,
                    ],
                    'editable' => [
                        'name' => tr('Add, Edit, Remove Items'),
                        'description' => tr('Master switch for enabling Add, Edit, Remove Items'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                    ],
                    'addItemText' => [ // having text switches adding on/off
                        'name' => tr('Add Item text'),
                        'description' => tr('Text to show on a button to add new items. Also enables adding items.'),
                        'filter' => 'text',
                        'depends' => [
                            'field' => 'editable'
                        ],
                    ],
                    'editItem' => [
                        'name' => tr('Edit Item'),
                        'description' => tr('Enable editing items'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'depends' => [
                            'field' => 'editable'
                        ],
                    ],
                    'deleteItem' => [
                        'name' => tr('Delete Item'),
                        'description' => tr('Allow deleting items'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'depends' => [
                            'field' => 'editable'
                        ],
                    ],
                    'editInViewMode' => [
                        'name' => tr('Edit in View Mode'),
                        'description' => tr('Whether the edit buttons and icons also appear when viewing an item'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'depends' => [
                            'field' => 'editable'
                        ],
                    ],
                    // TODO:
                    /*'addItemWikiTpl' => [
                        'name' => tr('Add Item Template Page'),
                        'description' => tr('Wiki page to use as a Pretty Tracker template'),
                        'filter' => 'pagename',
                        'profile_reference' => 'wiki_page',
                        'depends' => [
                            'field' => 'editable'
                        ],
                    ],*/
                ],
            ],
        ];
    }


    /**
     * Get field data
     * @see \Tracker\Field\AbstractItemField::getFieldData()
     *
     */
    public function getFieldData(array $requestData = []): array
    {
        $items = $this->getItemIds();
        $list = $this->getItemLabels($items);

        $ret = [
            'value' => '',
            'items' => $list,
        ];

        return $ret;
    }

    public function renderInput($context = [])
    {
        if (empty($this->getOption('fieldIdHere'))) {
            return $this->renderOutput(['render_input' => 'y']);
        } else {
            TikiLib::lib('header')->add_jq_onready(
                '
$("input[name=ins_' . $this->getOption('fieldIdHere') . '], select[name=ins_' . $this->getOption('fieldIdHere') . ']").on("change", function(e, initial) {
    if(initial == "initial" && $(this).data("triggered-' . $this->getInsertId() . '")) {
        return;
    }
    $(this).data("triggered-' . $this->getInsertId() . '", true);
    $.getJSON(
        "tiki-ajax_services.php",
        {
            controller: "tracker",
            action: "itemslist_output",
            field: "' . $this->getConfiguration('fieldId') . '",
            fieldIdHere: "' . $this->getOption('fieldIdHere') . '",
            value: $(this).val(),
            itemId: "' . $this->getItemId() . '"
        },
        function(data, status) {
            $ddl = $("div[name=' . $this->getInsertId() . ']");
            $ddl.html(data);
            if (jqueryTiki.select2) {
                $ddl.trigger("change.select2");
            }
            $ddl.trigger("change");
        }
    );
});
            '
            );
            // this is smart enough to attach only once even if multiple fields attach the same code
            TikiLib::lib('header')->add_jq_onready('
$("input[name=ins_' . $this->getOption('fieldIdHere') . '], select[name=ins_' . $this->getOption('fieldIdHere') . ']").trigger("change", "initial");
', 1);

            $this->getClickModalJQ();

            return '<div name="' . $this->getInsertId() . '"></div>';
        }
    }

    public function renderOutput($context = [])
    {
        if (isset($context['search_render']) && $context['search_render'] == 'y') {
            $itemIds = $this->getData($this->getConfiguration('fieldId'));
        } else {
            $itemIds = $this->getItemIds();
        }

        $list = $this->getItemLabels($itemIds, $context);

        // if nothing found check definition for previous list (used for output render)
        if (empty($list)) {
            $list = $this->getConfiguration('items', []);
            $itemIds = array_keys($list);
        }

        if (isset($context['list_mode']) && $context['list_mode'] === 'csv') {
            return implode('%%%', $list);
        } else {
            $data = [
                'links' => (bool)$this->getOption('linkToItems'),
                'raw' => (bool)$this->getOption('displayFieldIdThere'),
                'itemIds' => implode(',', $itemIds),
                'items' => $list,
                'num' => count($list),
                'itemPermissions' => [],
                'addItemText' => '',
                'otherFieldPermName' => '',
                'parentItemId' => 0,
            ];
            // Either it has been called from renderInput (edit mode) or we're rendering it as well in view mode
            if ((isset($context['render_input']) && $context['render_input'] === 'y') || $this->getOption('editInViewMode')) {
                $editmode = true;
            } else {
                $editmode = false;
            }
            if ($this->getOption('editable') && $editmode) {
                $trackerThere = Tracker_Definition::get($this->getOption('trackerId'));
                $fieldThere = $trackerThere->getField($this->getOption('fieldIdThere'));
                // trackerPerms overide field Options
                $trackerPerms = Perms::get('tracker', $this->getOption('trackerId'));
                $canCreate = $trackerPerms->create_tracker_items; // tracker/global permission; However canEdit and canRemove are Item permissions, see below
                $itemPermissions = [];
                foreach ($itemIds as $itemId) {
                    $item = Tracker_Item::fromId($itemId);
                    $itemPermissions[$itemId]['can_remove'] = $item->canRemove();
                    $itemPermissions[$itemId]['can_modify'] = $item->canModify();
                }
                $data['itemPermissions'] = $itemPermissions;
                $data['addItemText'] = $canCreate ? $this->getOption('addItemText') : '';
                $data['otherFieldPermName'] = $fieldThere['permName'];
                if (empty($this->getOption('fieldIdHere'))) {
                    $data['parentItemId'] = $this->getItemId();
                } else {
                    // other field not an ItemLink - sent by ajax from \Services_Tracker_Controller::action_itemslist_output
                    $itemData = $this->getItemData();
                    $data['parentItemId'] = $itemData[$this->getOption('fieldIdHere')];
                }

                $this->getClickModalJQ();
            }
            return $this->renderTemplate(
                'trackeroutput/itemslist.tpl',
                $context,
                $data
            );
        }
    }

    public function itemsRequireRefresh($trackerId, $modifiedFields)
    {
        if ($this->getOption('trackerId') != $trackerId) {
            return false;
        }

        $displayFields = $this->getOption('displayFieldIdThere');
        if (! is_array($displayFields)) {
            $displayFields = [$displayFields];
        }

        $usedFields = array_merge(
            [$this->getOption('fieldIdThere')],
            $displayFields
        );

        $intersect = array_intersect($usedFields, $modifiedFields);

        return count($intersect) > 0;
    }

    public function watchCompare($old, $new)
    {
        $o = '';
        $items = $this->getItemIds();
        $n = $this->getItemLabels($items);

        return parent::watchCompare($o, $n);    // then compare as text
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $baseKey = $this->getBaseKey();
        $items = $this->getItemIds();

        $list = $this->getItemLabels($items);
        $listtext = implode(' ', $list);

        return [
            $baseKey => $typeFactory->multivalue($items),
            "{$baseKey}_text" => $typeFactory->plainmediumtext($listtext),
        ];
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        return [
            $baseKey,
            "{$baseKey}_text",
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        $baseKey = $this->getBaseKey();
        return [
            $baseKey => 'multivalue',
            "{$baseKey}_text" => 'plainmediumtext',
        ];
    }

    public function getGlobalFields(): array
    {
        return [];
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');

        $schema->addNew($permName, 'multi-id')
            ->setLabel($name)
            ->setReadOnly(true)
            ->setRenderTransform(function ($value) {
                return is_array($value) ? implode(';', $value) : $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            });

        $schema->addNew($permName, 'multi-name')
            ->setLabel($name)
            ->addQuerySource('itemId', 'object_id')
            ->setReadOnly(true)
            ->setRenderTransform(function ($value, $extra) {

                if (is_string($value) && empty($value)) {
                    // ItemsLists have no stored value, so when called from \Tracker\Tabular\Source\TrackerSourceEntry...
                    // we have to: get a copy of this field
                    $field = $this->getTrackerDefinition()->getFieldFromPermName($this->getConfiguration('permName'));
                    // get a new handler for it
                    $factory = $this->getTrackerDefinition()->getFieldFactory();
                    $handler = $factory->getHandler($field, ['itemId' => $extra['itemId']]);
                    // for which we can then get the itemIds array of the "linked" items
                    $value = $handler->getItemIds();
                    // and then get the labels from the id's we've now found as if they were the field's data
                }

                $labels = $this->getItemLabels($value, ['list_mode' => 'csv']);
                return implode(';', $labels);
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            });

        // json format for export and import (which will recreate missing linked items)

        $fieldIdHere = $this->getOption('fieldIdHere');
        $definition = $this->getTrackerDefinition();
        $fieldHere = $definition->getField($fieldIdHere);
        $extraFieldName = "tracker_field_{$fieldHere['permName']}";

        $fieldIdThere = $this->getOption('fieldIdThere');
        $trackerIdThere = $this->getOption('trackerId');
        $trackerThere = Tracker_Definition::get($trackerIdThere);
        $fieldThere = $trackerThere->getField($fieldIdThere);
        if ($fieldThere) {
            $queryFieldName = "tracker_field_{$fieldThere['permName']}";
            if ($fieldHere['type'] === 'r' && $fieldThere['type'] !== 'r') {
                $extraFieldName .= '_text';
            }
        } else {
            $queryFieldName = '';
        }


        // cache the other tracker's items to test when importing
        $itemsThereLookup = new Tracker\Tabular\Schema\CachedLookupHelper();
        $tiki_tracker_items = TikiDb::get()->table('tiki_tracker_items');
        $itemsThereLookup->setInit(
            function ($count) use ($tiki_tracker_items, $trackerIdThere) {
                return $tiki_tracker_items->fetchMap(
                    'itemId',
                    'status',
                    [
                        'trackerId' => $trackerIdThere,
                    ],
                    $count,
                    0
                );
            }
        );
        $itemsThereLookup->setLookup(
            function ($value) use ($tiki_tracker_items, $trackerIdThere) {
                return $tiki_tracker_items->fetchOne(
                    'itemId',
                    [
                        'trackerId' => $trackerIdThere,
                        'itemId'    => $value,
                    ]
                );
            }
        );

        $attributelib = TikiLib::lib('attribute');
        $unifiedsearchlib = TikiLib::lib('unifiedsearch');
        $trackerUtilities = new Services_Tracker_Utilities();

        $schema->addNew($permName, 'multi-json')
            ->setLabel($name)
            // these query sources appear in the $extra array in the render transform fn
            ->addQuerySource('itemId', 'object_id')
            ->addQuerySource('fieldIdHere', $extraFieldName)
            ->setRenderTransform(
                function ($value, $extra) use ($trackerIdThere, $queryFieldName, $unifiedsearchlib) {

                    if (! empty($extra['fieldIdHere'])) {
                        $content = $extra['fieldIdHere'];
                    } else {
                        $content = (string)$extra['itemId'];
                    }

                    $query = $unifiedsearchlib->buildQuery(
                        [
                            'type'          => 'trackeritem',
                            'tracker_id'    => (string)$trackerIdThere,
                            $queryFieldName => $content,
                        ]
                    );

                    $result = $query->search($unifiedsearchlib->getIndex());
                    $out = [];

                    if ($result->count()) {
                        foreach ($result as $entry) {
                            $item = Tracker_Item::fromId($entry['object_id']);
                            $data = $item->getData();
                            $data['fields'] = array_filter($data['fields']);
                            $out[] = $data;
                        }
                    }

                    if ($out) {
                        $out = json_encode($out);
                    }

                    return $out;
                }
            )
            ->setParseIntoTransform(
                function (&$info, $value) use ($permName, $trackerUtilities, $trackerThere, $itemsThereLookup, $attributelib, $fieldThere, $schema) {
                    static $newItemsThereCreated = [];

                    $data = json_decode($value, true);

                    if ($data && is_array($data)) {
                        foreach ($data as $row) {
                            if (! empty($row['itemId'])) {
                                // check the old itemId as an attribute to avoid repeat imports
                                $attr = $attributelib->find_objects_with('tiki.trackeritem.olditemid', $row['itemId']);

                                // not done this time?
                                if (! isset($newItemsThereCreated[$row['itemId']])) {
                                    if (! isset($row['created']) && ! empty($row['creation_date'])) {
                                        $row['created'] = $row['creation_date'];    // convert from index to database field name
                                    }

                                    $item = Tracker_Item::fromInfo($row);

                                    // FIXME $schema here doesn't know if it's a transaction type so this never executes
                                    if ($schema->isImportTransaction()) {
                                        $trackerThereId = $trackerThere->getConfiguration('trackerId');
                                        if (! $item->canModify()) {
                                            throw new \Tracker\Tabular\Exception\Exception(tr(
                                                'Permission denied importing into linked tracker %0',
                                                $trackerThereId
                                            ));
                                        }
                                        $errors = $trackerUtilities->validateItem($trackerThere, $item->getData());
                                        if ($errors) {
                                            throw new \Tracker\Tabular\Exception\Exception(tr(
                                                'Errors occurred importing into linked tracker %0',
                                                $trackerThereId
                                            ));
                                        }
                                    }

                                    $itemData = $item->getData();

                                    // no item with this itemId and we didn't create it before? so let's make one!
                                    if (! $itemsThereLookup->get($row['itemId']) && empty($attr)) {
                                        // needs to be done after the new main item has been created
                                        if (! isset($info['postprocess'])) {
                                            $info['postprocess'] = [];
                                        }
                                        $info['postprocess'][] = function ($newMainItemId) use ($trackerUtilities, $trackerThere, $itemData, $fieldThere, $attributelib) {

                                            // fix the ItemLink there to point at our new item
                                            if ($fieldThere['type'] === 'r') {
                                                $itemData['fields'][$fieldThere['permName']] = $newMainItemId;
                                            }

                                            $newItemId = $trackerUtilities->insertItem($trackerThere, $itemData);

                                            if ($newItemId) {
                                                $newItemsThereCreated[$itemData['itemId']] = $newItemId;
                                                // store the old itemId as an attribute of this item so we don't import it again
                                                $attributelib->set_attribute(
                                                    'trackeritem',
                                                    $newItemId,
                                                    'tiki.trackeritem.olditemid',
                                                    $itemData['itemId']
                                                );
                                            } else {
                                                Feedback::error(
                                                    tr(
                                                        'Creating replacement linked item for itemId %0 for ItemsList field "%1" import failed on item #%2',
                                                        $itemData['itemId'],
                                                        $this->getConfiguration('permName'),
                                                        $this->getItemId()
                                                    )
                                                );
                                            }
                                        };
                                    } elseif ($itemsThereLookup->get($row['itemId'])) {    // linked item exists, so update it
                                        $item = Tracker_Item::fromInfo($row);
                                        $itemData = $item->getData();
                                        $result = $trackerUtilities->updateItem($trackerThere, $itemData);
                                        if (! $result) {
                                            Feedback::error(
                                                tr(
                                                    'Updating linked item for itemId %0 for ItemsList field "%1" import failed on item #%2',
                                                    $itemData['itemId'],
                                                    $this->getConfiguration('permName'),
                                                    $this->getItemId()
                                                )
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $info['fields'][$permName] = '';
                }
            );

        return $schema;
    }

    public function getFilterCollection()
    {
        $collection = new Tracker\Filter\Collection($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $baseKey = $this->getBaseKey();

        $collection->addNew($permName, 'selector')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\ObjectSelector("tf_{$permName}_os", [
                'type' => 'trackeritem',
                'tracker_status' => implode(' OR ', str_split($this->getOption('status', 'opc'), 1)),
                'tracker_id' => $this->getOption('trackerId'),
                '_placeholder' => tr(TikiLib::lib('object')->get_title('tracker', $this->getOption('trackerId'))),
            ]))
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                $value = $control->getValue();

                if ($value) {
                    $query->filterMultivalue((string) $value, $baseKey);
                }
            })
            ;

        $collection->addNew($permName, 'multiselect')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\ObjectSelector(
                "tf_{$permName}_ms",
                [
                'type' => 'trackeritem',
                'tracker_status' => implode(' OR ', str_split($this->getOption('status', 'opc'), 1)),
                'tracker_id' => $this->getOption('trackerId'),
                '_placeholder' => tr(TikiLib::lib('object')->get_title('tracker', $this->getOption('trackerId'))),
                ],
                true
            ))  // for multi
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                $value = $control->getValue();

                if ($value) {
                    $value = array_map(function ($v) {
                        return str_replace('trackeritem:', '', $v);
                    }, $value);
                    $query->filterMultivalue(implode(' OR ', $value), $baseKey);
                }
            })
        ;

        return $collection;
    }

    private function getItemIds()
    {
        $trklib = TikiLib::lib('trk');
        $trackerId = (int) $this->getOption('trackerId');

        if (! $trackerId) {
            Feedback::error(
                tr(
                    'No tracker "%0" found for ItemsList fieldId %1',
                    $this->getOption('trackerId'),
                    $this->getFieldId()
                )
            );
            return [];
        }
        $filterFieldIdHere = (int) $this->getOption('fieldIdHere');
        $filterFieldIdThere = (int) $this->getOption('fieldIdThere');

        if ($filterFieldIdHere) {
            try {
                $filterFieldHere = $this->getTrackerDefinition()->getField($filterFieldIdHere);
            } catch (Exception $e) {
                Feedback::error(
                    tr(
                        'Tracker %0 field %1 config error: ',
                        $this->getConfiguration('trackerId'),
                        $this->getConfiguration('fieldId')
                    ) .
                    $e->getMessage()
                );
            }
        } else {
            $filterFieldHere = null;
        }
        if ($filterFieldIdThere) {
            $filterFieldThere = $trklib->get_tracker_field($filterFieldIdThere);
        } else {
            // this is legit case when here field is ItemLink or Relation
            $filterFieldThere = null;
        }

        $sortFieldIds = $this->getOption('sortField');
        if (is_array($sortFieldIds)) {
            $sortFieldIds = array_filter($sortFieldIds);
        } else {
            $sortFieldIds = [];
        }
        $status = $this->getOption('status', 'opc');
        $tracker = Tracker_Definition::get($trackerId);



        // note: if itemlink or dynamic item list is used, than the final value to compare with must be calculated based on the current itemid

        $technique = 'value';
        $multiple = false;

        // r = ItemLink
        // w = DynamicList
        if ($tracker && $filterFieldThere && (! $filterFieldIdHere || $filterFieldThere['type'] === 'r' || $filterFieldThere['type'] === 'w')) {
            if (($filterFieldThere['type'] === 'r' || $filterFieldThere['type'] === 'w') && (! $filterFieldHere || $filterFieldHere['type'] !== 'r')) {
                $technique = 'id';
                if (! empty($filterFieldThere['options_map']['selectMultipleValues'])) {
                    $multiple = true;
                }
            }
        }

        // q = AutoIncrement
        if ($filterFieldHere && isset($filterFieldHere['type']) && $filterFieldHere['type'] == 'q' && isset($filterFieldHere['options_array'][3]) && $filterFieldHere['options_array'][3] == 'itemId') {
            $technique = 'id';
        }

        if ($technique == 'id') {
            $itemId = $this->getItemId();
            if (! $itemId) {
                $items = [];
            } else {
                $items = $trklib->get_items_list($trackerId, $filterFieldIdThere, $itemId, $status, $multiple, $sortFieldIds);
            }
        } else {
            // when this is an item link or dynamic item list field, localvalue contains the target itemId
            $localValue = $this->getData($filterFieldIdHere);
            if (! $localValue) {
                // in some cases e.g. pretty tracker $this->getData($filterFieldIdHere) is not reliable as the info is not there
                // Note: this fix only works if the itemId is passed via the template
                $itemId = $this->getItemId();
                $localValue = $trklib->get_item_value($trackerId, $itemId, $filterFieldIdHere);
            }
            if (! $filterFieldThere && $filterFieldHere && ( $filterFieldHere['type'] === 'r' || $filterFieldHere['type'] === 'w' || $filterFieldHere['type'] === 'math' ) && $localValue) {
                // itemlink/dynamic item list field in this tracker pointing directly to an item in the other tracker
                return [$localValue];
            }
            // r = item link - not sure this is working
            if (
                isset($filterFieldHere['type']) &&
                $filterFieldHere['type'] == 'r' &&              // an ItemLink
                isset($filterFieldHere['options_array'][0]) &&  // trackerId
                isset($filterFieldHere['options_array'][1]) &&  // fieldId
                // check for filterFieldThere not being another ItemLink because then we need the itemId
                $filterFieldThere['type'] !== 'r' &&
                $filterFieldThere['type'] !== 'w'
            ) {
                $localValue = $trklib->get_item_value(
                    $filterFieldHere['options_array'][0],
                    $localValue,
                    $filterFieldHere['options_array'][1]
                );
            }

            // w = dynamic item list - localvalue is the itemid of the target item. so rewrite.
            if (isset($filterFieldHere['type']) && $filterFieldHere['type'] == 'w') {
                $localValue = $trklib->get_item_value($trackerId, $localValue, $filterFieldIdThere);
            }
            // u = user selector, might be mulitple users so need to find multiple values
            if (
                isset($filterFieldHere['type']) && $filterFieldHere['type'] == 'u' && ! empty($filterFieldHere['options_map']['multiple'])
                && $localValue
            ) {
                if (! is_array($localValue)) {
                    $theUsers = explode(',', $localValue);
                } else {
                    $theUsers = $localValue;
                }
                $items = [];
                foreach ($theUsers as $theUser) {
                    $items = array_merge(
                        $items,
                        $trklib->get_items_list($trackerId, $filterFieldIdThere, $theUser, $status, false, $sortFieldIds)
                    );
                }

                return $items;
            }
            // e = category, might be mulitple categories so need to find multiple values
            if (isset($filterFieldHere['type']) && $filterFieldHere['type'] == 'e' && $localValue) {
                if (! is_array($localValue)) {
                    $categories = explode(',', $localValue);
                } else {
                    $categories = $localValue;
                }
                $items = [];
                foreach ($categories as $category) {
                    $items = array_merge(
                        $items,
                        $trklib->get_items_list($trackerId, $filterFieldIdThere, $category, $status, true, $sortFieldIds)
                    );
                }

                return $items;
            }
            // REL = relation field can contain items from the target tracker which we can use to feed our ItemsList field
            if (isset($filterFieldHere['type']) && $filterFieldHere['type'] == 'REL' && $localValue) {
                $items = [];
                $handler = $this->getTrackerDefinition()->getFieldFactory()->getHandler($filterFieldHere, $this->getItemData());
                $data = $handler->getFieldData();
                foreach ($data['relations'] as $relation) {
                    $remote = null;
                    if ($relation->source->type == 'trackeritem' && $relation->source->itemId == $this->getItemId()) {
                        $remote = $relation->target;
                    } elseif ($relation->target->type == 'trackeritem' && $relation->target->itemId == $this->getItemId()) {
                        $remote = $relation->source;
                    }
                    if ($remote && $remote->type == 'trackeritem') {
                        $remoteItem = $trklib->get_item_info($remote->itemId);
                        if ($remoteItem['trackerId'] == $trackerId) {
                            $items[] = $remote->itemId;
                        }
                    }
                }
                return $items;
            }
            // Skip nulls
            if ($localValue) {
                $items = $trklib->get_items_list($trackerId, $filterFieldIdThere, $localValue, $status, false, $sortFieldIds);
            } else {
                $items = [];
            }
        }

        return $items;
    }

    /**
     * Get value of displayfields from given array of itemIds
     * @param array $items
     * @param array $context
     * @return array array of values by itemId
     */
    private function getItemLabels($items, $context = ['list_mode' => ''])
    {
        $displayFields = $this->getOption('displayFieldIdThere');
        $trackerId = (int) $this->getOption('trackerId');
        if (! $trackerId) {
            Feedback::error(
                tr(
                    'No tracker "%0" found for ItemsList fieldId %1',
                    $this->getOption('trackerId'),
                    $this->getFieldId()
                )
            );
            return [];
        }
        $status = $this->getOption('status', 'opc');

        $definition = Tracker_Definition::get($trackerId);
        if (! $definition) {
            return [];
        }

        $list = [];
        $trklib = TikiLib::lib('trk');
        foreach ($items as $itemId) {
            if ($displayFields && $displayFields[0]) {
                $list[$itemId] = $trklib->concat_item_from_fieldslist(
                    $trackerId,
                    $itemId,
                    $displayFields,
                    $status,
                    ' ',
                    isset($context['list_mode']) ? $context['list_mode'] : '',
                    $this->getOption('linkToItems'),
                    $this->getOption('displayFieldIdThereFormat'),
                    $trklib->get_tracker_item($itemId)
                );
            } else {
                $list[$itemId] = $trklib->get_isMain_value($trackerId, $itemId);
            }
        }

        return $list;
    }

    /**
     * Get remote items' values in an array as opposed to a string label.
     * Useful in Math calculations where individual field values are needed.
     * @return array associated array of field names and values
     */
    public function getItemValues()
    {
        $cache_key = $this->getItemId() . '-' . $this->getFieldId();
        if (isset(self::$itemValuesLocalCache[$cache_key])) {
            return self::$itemValuesLocalCache[$cache_key];
        }

        $displayFields = $this->getOption('displayFieldIdThere');
        $trackerId = (int) $this->getOption('trackerId');

        if (! $trackerId) {
            return [];
        }
        $definition = Tracker_Definition::get($trackerId);
        if (! $definition) {
            return [];
        }

        $itemsValues = [];

        $items = $this->getItemIds();
        foreach ($items as $itemId) {
            $item = TikiLib::lib('trk')->get_tracker_item($itemId);
            $itemValues = [];
            if ($displayFields) {
                foreach ($displayFields as $fieldId) {
                    try {
                        $field = $definition->getField($fieldId);
                        if (empty($field)) {
                            throw new Exception(tr('ItemsList::getItemValues display field not found: %0', $fieldId));
                        }
                    } catch (Exception $e) {
                        trigger_error($e->getMessage());
                        continue;
                    }
                    if ($field['type'] == 'l') {
                        $factory = $definition->getFieldFactory();
                        $handler = $factory->getHandler($field, $item);
                        $itemValues[$field['permName']] = $handler->renderOutput(['list_mode' => 'csv']);
                    } elseif (isset($item[$fieldId])) {
                        $itemValues[$field['permName']] = $item[$fieldId];
                    } else {
                        $itemValues[$field['permName']] = '';
                    }
                }
                $itemValues['itemId'] = $itemId;
            }
            $itemsValues[] = $itemValues;
        }

        if (count(self::$itemValuesLocalCache) > 500) {
            self::$itemValuesLocalCache = [];
        }
        self::$itemValuesLocalCache[$cache_key] = $itemsValues;

        return $itemsValues;
    }

    /**
     * Adds the javascript to the page to make the edit buttons open a modal popup
     *
     * @return void
     * @throws Exception
     */
    private function getClickModalJQ(): void
    {
        TikiLib::lib('header')->add_jq_onready(
            '
// a custom handler to load the action in a modal
$(document).on("click", "a.itemslist-btn", $.clickModal({
    button: this,
    backdrop: "static",
    success: function (data) {
        let $itemsList = $(this).closest(".itemslist-field");
        let url = $.service("tracker", "fetch_item_field", {
            trackerId: $itemsList.data("trackerid"),
            itemId: $itemsList.data("itemid"),
            fieldId: $itemsList.data("fieldid"),
            listMode: $itemsList.data("listmode"),
            mode: "output"
        })
        $.closeModal();

        if (' . (empty($this->getOption('fieldIdHere')) ? 'false' : 'true') . ') {
            $("input[name=ins_' . $this->getOption('fieldIdHere') . '], select[name=ins_' . $this->getOption('fieldIdHere') . ']").trigger("change", "custom");
        } else {
            $itemsList.tikiModal(tr("Loading...")).load(url.replace(/&amp;/g, "&"), function () {
                $itemsList.find(".itemslist-field").unwrap(); $itemsList.tikiModal();
            });
        }
    }
}));'
        );
    }
}
