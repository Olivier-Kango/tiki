<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for location/map/gmap
 *
 * Letter key: ~G~
 *
 */
class Tracker_Field_Location extends \Tracker\Field\AbstractItemField implements \Tracker\Field\SynchronizableInterface, \Tracker\Field\ExportableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'G' => [
                'name' => tr('Location'),
                'description' => tr('Enable a geographic location to be selected for the item and displayed on a map.'),
                'help' => 'Location-Tracker-Field',
                'prefs' => ['trackerfield_location'],
                'tags' => ['basic'],
                'default' => 'y',
                'params' => [
                    'use_as_item_location' => [
                        'name' => tr('Use as item location'),
                        'description' => tr("When enabled, the field's value is recorded as the item's geolocation to be displayed on locator maps."),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'legacy_index' => 0,
                    ],
                    'list_width' => [
                        'name' => tr('List View Width'),
                        'description' => tr('Width of map in pixels when tracker items are shown in list view'),
                        'filter' => 'int',
                        'default' => 200,
                        'legacy_index' => 1,
                    ],
                    'list_height' => [
                        'name' => tr('List View Height'),
                        'description' => tr('Height of map in pixels when tracker items are shown in list view'),
                        'filter' => 'int',
                        'default' => 200,
                        'legacy_index' => 2,
                    ],
                    'map_options_view_list' => [
                        'name' => tr('Map Options in View List Mode'),
                        'description' => tr('Control available on the map when viewing in a list. Default "controls,navigation,layers" (use "none" for none)'),
                        'filter' => 'text',
                        'default' => 'controls,navigation,layers',
                    ],
                    'item_width' => [
                        'name' => tr('Item View Width'),
                        'description' => tr('Width of map in pixels when a single tracker item is shown'),
                        'filter' => 'int',
                        'default' => 500,
                        'legacy_index' => 3,
                    ],
                    'item_height' => [
                        'name' => tr('Item View Height'),
                        'description' => tr('Height of map in pixels when a single tracker item is shown'),
                        'filter' => 'int',
                        'default' => 400,
                        'legacy_index' => 4,
                    ],
                    'map_options_view_item' => [
                        'name' => tr('Map Options in View Item Mode'),
                        'description' => tr('Control available on the map when viewing item. Default "controls,navigation,layers" (use "none" for none)'),
                        'filter' => 'text',
                        'default' => 'controls,navigation,layers',
                    ],
                    'map_options_edit' => [
                        'name' => tr('Map Options in Edit Mode'),
                        'description' => tr('Control available on the map when editing. Default "controls,search_location,current_location,navigation,layers" (use "none" for none)'),
                        'filter' => 'text',
                        'default' => 'controls,search_location,current_location,navigation,layers',
                    ],
                    'sourceFieldsList' => [
                        'name' => tr('Fields To Search'),
                        'description' => tr('Fields in this tracker to use as a source to search for a location for.'),
                        'separator' => '|',
                        'filter' => 'int',
                        'profile_reference' => 'tracker_field',
                        'parent' => 'input[name=trackerId]',
                        'parentkey' => 'tracker_id',
                        'sort_order' => 'tracker_id',
                    ],
                    'sourceSearchEvent' => [
                        'name' => tr('When To Search'),
                        'description' => tr('Event to attempt to search for a location.'),
                        'filter' => 'alpha',
                        'default' => '',
                        'options' => [
                            '' => tr('Never'),
                            'save' => tr('Save (when Location is empty)'),
                            'savealways' => tr('Save (always)'),
                            'index' => tr('Indexing (when Location is empty)'),
                            'indexalways' => tr('Indexing (always)'),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = []): array
    {
        if (isset($requestData[$this->getInsertId()])) {
            $value = $requestData[$this->getInsertId()];
        } else {
            $value = $this->getValue();
        }

        $parts = explode(',', $value);
        $parts = array_map('floatval', $parts);

        if (count($parts) >= 2) {
            // Always use . as the decimal point in the value, and not comma as used some places
            $value = '';
            $value .= str_replace(',', '.', $parts[0]) . ',';
            $value .= str_replace(',', '.', $parts[1]) . ',';
            $value .= str_replace(',', '.', $parts[2]);

            return [
                'value' => $value,
                'x' => $parts[0],
                'y' => $parts[1],
                'z' => isset($parts[2]) ? $parts[2] : 0,
            ];
        } else {
            return [
                'value' => '',
                'x' => null,
                'y' => null,
                'z' => null,
            ];
        }
    }

    public function renderInput($context = [])
    {
        return $this->renderTemplate('trackerinput/location.tpl', $context);
    }

    public function renderOutput($context = [])
    {
        if ($context['list_mode'] === 'csv') {
            return $this->getConfiguration('value');
        } else {
            $attributes = TikiLib::lib('attribute')->get_attributes('trackeritem', $this->getItemId());

            if (isset($attributes['tiki.icon.src'])) {
                $context['icon_data'] = ' data-icon-src="' . smarty_modifier_escape($attributes['tiki.icon.src']) . '"';
            } else {
                $context['icon_data'] = '';
            }

            return $this->renderTemplate('trackeroutput/location.tpl', $context);
        }
    }

    public function handleSave($value, $oldValue)
    {
        $sourceFieldsList = $this->getOption('sourceFieldsList');

        if (! empty(array_filter($sourceFieldsList))) {
            $event = $this->getOption('sourceSearchEvent');

            $emptyValue = ! $value || strpos($value, '0,0,') !== false;

            if ($event === 'save' && $emptyValue || $event === 'savealways') {
                $value = $this->searchForLocation($sourceFieldsList);
            }
        }

        if (strpos($value, '0,0,') !== false) {
            $value = '';
        }

        return [
            'value' => $value,
        ];
    }

    private function searchForLocation($sourceFieldsList)
    {
        global $prefs;
        $out = '';
        $address = '';

        $definition = Tracker_Definition::get($this->getConfiguration('trackerId'));
        $item = Tracker_Item::fromId($this->getItemId());
        if ($item) {
            $item = $item->getData();
        }

        array_walk($sourceFieldsList, function (&$field) use ($definition, $item, &$address) {

            $fieldArray = $definition->getField((int)$field);

            if (! $fieldArray) {
                $message = tr('Location: Field %0 not found for field "%1"', $field, $this->getConfiguration('permName'));
                Feedback::error($message);
            } else {
                $factory = $definition->getFieldFactory();
                $handler = $factory->getHandler($fieldArray, $item);

                if ($handler) {
                    $fieldData = $handler->getFieldData($_REQUEST);
                    $address .= $fieldData['value'] . "\n";
                }
            }
        });

        if (trim($address)) {
            $geo = TikiLib::lib('geo')->geocode($address);
            if ($geo) {
                $out = $geo['lon'] . ',' . $geo['lat'] . ',' . $prefs['gmap_defaultz'];
            } else {
                Feedback::error(tr('Could not find a location for "%0"', $address));
            }
        }

        return $out;
    }

    public function importRemote($value)
    {
        return $value;
    }

    public function exportRemote($value)
    {
        return $value;
    }

    public function importRemoteField(array $info, array $syncInfo)
    {
        return $info;
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $value = $this->getValue();
        $sourceFieldsList = $this->getOption('sourceFieldsList');

        if ($sourceFieldsList) {
            $emptyValue = ! $value || strpos($value, '0,0,') !== false;
            $event = $this->getOption('sourceSearchEvent');

            if ($event === 'index' && $emptyValue || $event === 'indexalways') {
                $newValue = $this->searchForLocation($sourceFieldsList);

                if ($value !== $newValue) {
                    $value = $newValue;
                    $trklib = TikiLib::lib('trk');
                    $trklib->modify_field($this->getItemId(), $this->getConfiguration('fieldId'), $value);

                    // need to set up the geo attributes if this field is use_as_item_location
                    if ($this->getOption('use_as_item_location')) {
                        TikiLib::lib('geo')->set_coordinates('trackeritem', $this->getItemId(), $value);
                    }
                }
            }
        }

        $baseKey = $this->getBaseKey();
        return [
            $baseKey => $typeFactory->sortable($value), // TODO add geo_point type for elastic
        ];
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');

        $schema->addNew($permName, 'default')
            ->setLabel($name)
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            });

        return $schema;
    }
}
