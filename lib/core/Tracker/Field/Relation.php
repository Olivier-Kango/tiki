<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Relation\ObjectRelation;
use Tracker\Field;
use Tracker\Field\TrackerFieldRelation;

/**
The Relation field type create a Many-to-Many relationship through the table tiki_object_relations (also used by wiki pages and other objects) and the table tracker_item_fields

@see Tracker_Field_Relation which also manages table 'tiki_object_relations'

The data saved in tiki_object_relations by this code has the following structure:

    relationId: int, the primary key of the related pair.
    relation: string, the relation namespace. Variously named type or name in the code and namespace in the web docs.
    source_type: string, In the case of tracker relations, always 'trackeritem'
    source_itemId: string, id of the source tracker item
    source_fieldId: int, id of the source tracker item field
    target_type: string, 'trackeritem', 'wiki page', 'user' depending on the target object type
    target_itemId: string, actual value may be an int (ex: tracker items) or a string  (ex: Wiki page)
    metadata_itemId: int, id of the optional tracker item used to describe this relation

As of 2023-03-15, this structure is never edited, always replaced.  That is removing a link to an object, saving, and adding it back will result in a row with a new relationId.

No data is saved in tracker_item_fields value column. Canonical representation of the relation data should be retrieved from tiki_object_relations. If there are 3 links, there will be 3 rows in tiki_object_relations.

 A lot of this code is hard to grasp by inspection, because it is spread out in multiple places:
 - Some of it lives here
 - Some of the code is implemented in the class RelationLib  (lib/attributes/relationlib.php) relationlib.php and is referenced as "relation" through db/config/tiki.xml
 - Some of the code lives directly in the class TikiLib lib/tikilib.php (such as replace_link())
 - Some of the code lives in Services_Relation_Controller (lib/core/Services/Relation/Controller.php)

Part of the documentation is at https://dev.tiki.org/Object+Attributes+and+Relations and https://doc.tiki.org/Relations-Tracker-Field
*/
class Tracker_Field_Relation extends \Tracker\Field\AbstractItemField implements \Tracker\Field\ExportableInterface, \Tracker\Field\FilterableInterface
{
    public static $refreshedTargets = [];
    public static $relationFieldCache = [];

    public static function getManagedTypesInfo(): array
    {
        return [
            'REL' => [
                'name' => tr('Relations'),
                'description' => tr('Allow arbitrary relations to be created between the trackers and other objects in the system.'),
                'prefs' => ['trackerfield_relation'],
                'tags' => ['advanced'],
                'default' => 'y',
                'help' => 'Relations-Tracker-Field',
                'params' => [
                    'relation' => [
                        'name' => tr('Relation'),
                        'description' => tr('Mandatory.  Relation qualifier. Must be a three-part qualifier containing letters and separated by dots.  The field will silently refuse to save values if it\'s empty.'),
                        'filter' => 'attribute_type',
                        'legacy_index' => 0
                    ],
                    'filter' => [
                        'name' => tr('Filter'),
                        'description' => tr('URL-encoded list of filters to be applied on object selection.'),
                        'filter' => 'url',
                        'legacy_index' => 1,
                        'profile_reference' => 'search_urlencoded',
                    ],
                    'format' => [
                        'name' => tr('Format'),
                        'description' => tr('Customize display of search results of object selection. Default is {title} listing the object title. Note that including other fields in the format will make search look up exactly those fields intead of the title field.'),
                        'filter' => 'text',
                    ],
                    'relationshipTrackerId' => [
                        'name' => tr('Relationship Tracker ID'),
                        'description' => tr('Optionally describe the relationship with a metadata system tracker. You can create predefined trackers from %0admin trackers%1 or define any existing tracker from its properties.', '<a href="tiki-admin.php?page=trackers#content-admin1-4" target="_blank">', '</a>'),
                        'filter' => 'int',
                        'profile_reference' => 'tracker',
                        'selector_filter' => 'relationship-trackers'
                    ],
                    'remoteUrl' => [
                        'name' => tr('Remote Tiki URL'),
                        'description' => tr('Base URL of another Tiki. Selected objects are stored here and their labels are cached on save, so later display does not contact the remote site. Leave empty for local relations only.'),
                        'filter' => 'url',
                    ],
                    'readonly' => [
                        'name' => tr('Read-only'),
                        'description' => tr('Only display the incoming relations instead of manipulating them.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'legacy_index' => 2,
                    ],
                    /* Having a hard time understanding the practical use of this one.  It seems to cause the values from BOTH side of the relation to be merged into one deduplicated list.  - benoitg - 2024-08-09 */
                    'invert' => [
                        'name' => tr('Include Invert'),
                        'description' => tr('Include invert relations in the list'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                        'legacy_index' => 3,
                    ],
                    'display' => [
                        'name' => tr('Display'),
                        'description' => tr('Control how the relations are displayed in view mode'),
                        'filter' => 'word',
                        'options' => [
                            'list' => tr('List'),
                            'count' => tr('Count'),
                            'toggle' => tr('Count with toggle for list'),
                        ],
                        'legacy_index' => 4,
                    ],
                    'refresh' => [
                        'name' => tr('Force Refresh'),
                        'description' => tr('Re-save related tracker items.'),
                        'filter' => 'alpha',
                        'options' => [
                            '' => tr('No'),
                            'save' => tr('On Save'),
                        ],
                    ],
                    'parentFilter' => [
                        'name' => tr('Extra Filter Field'),
                        'description' => tr('Filter objects by value of another field. Use a jQuery seletor to target the element in the page.'),
                        'filter' => 'text',
                    ],
                    'parentFilterKey' => [
                        'name' => tr('Extra Filter Key'),
                        'description' => tr('Key to filter objects by using the value from the Extra Filter Field above.'),
                        'filter' => 'text',
                    ],
                    'linkTarget' => [
                        'name' => tr('Link Target'),
                        'description' => tr('Control whether relation links open in the same window or a new tab.'),
                        'filter' => 'word',
                        'options' => [
                            '' => tr('Same window'),
                            '_blank' => tr('New tab'),
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function getTrackerFieldClass(): string
    {
        return TrackerFieldRelation::class;
    }

    /**
     * Get the ObjectRelation objects that touch this Relation field
     *
     * @return array of ObjectRelation objects
     */
    private function getObjectRelationInstances(): array
    {
        $relation = $this->trackerField->getOption(TrackerFieldRelation::OPT_RELATION);
        $relations = TikiLib::lib('relation')->getObjectRelations('trackeritem', $this->getItemId(), $relation);
        if ($this->trackerField->getOption(TrackerFieldRelation::OPT_INVERT)) {
            $relations = array_merge($relations, TikiLib::lib('relation')->getObjectRelations('trackeritem', $this->getItemId(), $relation, true));
        }

        $byTarget = [];
        foreach ($relations as $rel) {
            $key = strval($rel->target);
            if (! isset($byTarget[$key]) || ($rel->metadata && ! $byTarget[$key]->metadata)) {
                $byTarget[$key] = $rel;
            }
        }
        return array_values($byTarget);
    }

    public function getFieldData(array $requestData = []): array
    {
        $insertId = $this->getInsertId();

        $data = [];
        $relations = [];
        $meta = [];
        if (! $this->trackerField->getOption(TrackerFieldRelation::OPT_READONLY) && isset($requestData[$insertId])) {
            $selector = TikiLib::lib('objectselector');
            if (is_array($requestData[$insertId])) {
                $entries = $selector->readMultiple($requestData[$insertId]['objects']);
                $meta = json_decode($requestData[$insertId]['meta'], true);
            } else {
                $entries = $selector->readMultiple($requestData[$insertId]);
            }
            $data = array_map('strval', $entries);
        } else {
            $relations = $this->getObjectRelationInstances();
            foreach ($relations as $rel) {
                $targetStr = strval($rel->target);
                $data[] = $targetStr;
                if ($rel->metadata) {
                    $meta[$targetStr] = $rel->metadata->itemId;
                }
            }
            $data = array_unique($data);
            if (empty($data) && $this->getValue()) {
                $data = array_filter(array_map(function ($line) {
                    if (! str_contains($line, ':')) {
                        return null;
                    } else {
                        return trim($line);
                    }
                }, explode("\n", $this->getValue())));
            }
        }

        return [
            'value' => implode("\n", $data),
            'relations' => $relations,
            'meta' => $meta,
        ];
    }

    public function addValue($value)
    {
        $existing = explode("\n", $this->getValue());
        if (! in_array($value, $existing)) {
            $existing[] = $value;
        }
        return implode("\n", $existing);
    }

    public function removeValue($value)
    {
        $existing = explode("\n", $this->getValue());
        $existing = array_filter($existing, function ($v) use ($value) {
            return $v != $value;
        });
        return implode("\n", $existing);
    }

    public function renderInput($context = [])
    {
        if ($this->trackerField->getOption(TrackerFieldRelation::OPT_READONLY)) {
            return tra('Read-only');
        }

        $data = $this->getFieldData();

        if (! empty($data['relations']) && count($data['relations']) > 1) {
            // cache the titles with just 1 query for all related objects
            $this->getItemValues();
        }

        $filter = $this->trackerField->getParsedFilter();

        if (
            isset($filter['tracker_id']) &&
                $this->getConfiguration('trackerId') == $filter['tracker_id'] &&
                ! isset($filter['object_id']) && $this->getItemId()
        ) {
            $filter['object_id'] = 'NOT ' . $this->getItemId(); // exclude this item if we are related to the same tracker_id
        }

        $format = $this->trackerField->getOption('format');
        $sort = 'title_asc';
        if (! empty($format)) {
            if (preg_match('/\{(.*?)\}/', $format, $m)) {
                $sort = $m[1] . '_asc';
            }
        }

        $params = [
            'existing' => $data['value'],
            'relations' => $data['relations'],
            'meta' => json_encode($data['meta']),
            'filter' => $filter,
            'sort' => $sort,
            'format' => $format,
            'parent' => $this->trackerField->getOption('parentFilter'),
            'parentkey' => $this->trackerField->getOption('parentFilterKey'),
            'remoteUrl' => $this->trackerField->getOption('remoteUrl'),
        ];

        $relationshipTracker = $this->trackerField->getRelationshipTracker();
        $params['relationshipTracker'] = $relationshipTracker;
        if ($relationshipTracker) {
            $params['relationshipTrackerId'] = $relationshipTracker->getConfiguration('trackerId');
            $params['relationshipBehaviour'] = $relationshipTracker->getRelationshipBehaviour($this->trackerField->getOption(TrackerFieldRelation::OPT_RELATION));
        }

        return $this->renderTemplate(
            'trackerinput/relation.tpl',
            $context,
            $params
        );
    }

    public function renderInnerOutput($context = [])
    {
        if ($context['list_mode'] === 'csv') {
            // use field value instead of relation storage because of history logs
            $fieldId = $this->getConfiguration('fieldId');
            if (! empty($fieldId)) {
                $itemData = $this->getData($fieldId);
                $items = preg_split('/[\s]+/', $itemData);

                $returnValue = '';
                foreach ($items as $itemValue) {
                    $itemFieldValue = explode(':', $itemValue);
                    if (! empty($itemFieldValue[0]) && ! empty($itemFieldValue[1])) {
                        $objectLib = TikiLib::lib('object');
                        $value = $objectLib->get_title($itemFieldValue[0], $itemFieldValue[1]);
                    } else {
                        $value = $itemValue;
                    }

                    $returnValue .= ! empty($returnValue) ? ', ' . $value : $value;
                }

                return ! empty($returnValue) ? $returnValue : $itemData;
            }
            return $this->getConfiguration('value');
        } else {
            $relations = $this->getObjectRelationInstances();
            if (! empty($relations) && count($relations) > 1) {
                // cache the titles with just 1 query for all related objects
                $this->getItemValues();
            }
            // TODO: render metadata as well
            return implode(
                $context['list_mode'] === 'text' ? "\n" : '<br/>',
                array_map(
                    function ($rel) {
                        return $rel->target->getTitle($this->trackerField->getOption('format'));
                    },
                    $relations
                )
            );
        }
    }

    public function renderOutput($context = [])
    {
        $this->getItemValues(); // this caches the titles and is faster as it does 1 query for all related objects
        $list_mode = $context['list_mode'] ?? '';
        if ($list_mode === 'csv' || $list_mode === 'text') {
            return $this->renderInnerOutput($context);
        } else {
            $display = $this->trackerField->getOption('display');
            if (! in_array($display, ['list', 'count', 'toggle'])) {
                $display = 'list';
            }
            $relations = $this->getObjectRelationInstances();

            foreach ($relations as $key => $rel) {
                if ($rel->target->type != 'trackeritem') {
                    continue;
                }
                $item = Tracker_Item::fromId($rel->target->itemId);
                if ($item && ! $item->canView()) {
                    unset($relations[$key]);
                }
            }
            $relations = array_values($relations);
            if (! empty($relations) && count($relations) > 1) {
                // cache the titles with just 1 query for all related objects
                $this->getItemValues();
            }

            return $this->renderTemplate(
                'trackeroutput/relation.tpl',
                $context,
                [
                    'display' => $display,
                    'relations' => $relations,
                    'format' => $this->trackerField->getOption('format'),
                    'linkTarget' => $this->trackerField->getOption('linkTarget'),
                ]
            );
        }
    }

    public function handleSpecialSave($field)
    {
        $value = $field['value'] ?? null;
        if ($value && ! is_array($value)) {
            $target = explode("\n", trim($value));
        } elseif ($value) {
            $target = $value;
        } else {
            $target = [];
        }

        // saved items should not refresh themselves later => solves odd issues with relation disappearing
        self::$refreshedTargets[] = 'trackeritem:' . $this->getItemId();

        if ($this->trackerField->getOption(TrackerFieldRelation::OPT_READONLY)) {
            if ($this->trackerField->getOption('refresh') == 'save') {
                $this->prepareRefreshRelated($target);
            }

            return [
                'value' => '',
            ];
        }

        $relationlib = TikiLib::lib('relation');
        $relation = $this->trackerField->getOption(TrackerFieldRelation::OPT_RELATION);
        $remoteUrl = rtrim((string) $this->trackerField->getOption('remoteUrl'), '/');
        if ($remoteUrl) {
            $target = $this->normalizeRemoteTargets($remoteUrl, $target);
            $value = is_array($field['value'] ?? null) ? $target : implode("\n", $target);
        }
        $current = TikiLib::lib('relation')->getObjectRelations('trackeritem', $this->getItemId(), $relation);
        if ($this->trackerField->getOption(TrackerFieldRelation::OPT_INVERT)) {
            $current = array_merge($current, TikiLib::lib('relation')->getObjectRelations('trackeritem', $this->getItemId(), $relation, true));
        }
        $map = [];
        foreach ($current as $rel) {
            $key = strval($rel->target);
            $map[$key] = $rel;
        }

        $toRemove = array_diff(array_keys($map), $target);
        $toAdd = array_diff($target, array_keys($map));

        if ($remoteUrl && $target) {
            // Before relations are written: saving a relation reindexes this item immediately.
            $this->cacheRemoteLabels($remoteUrl, $target);
        }

        foreach ($toRemove as $v) {
            $relation = $map[$v];
            $relationlib->remove_relation($relation->id);
        }

        foreach ($toAdd as $key) {
            list($type, $id) = explode(':', $key, 2);

            $isRemote = $remoteUrl && ObjectLib::remoteObjectLocalId($remoteUrl, $id) !== null;
            if ($type == 'trackeritem' && ! $isRemote && ! TikiLib::lib('trk')->get_item_info($id)) {
                continue;
            }

            $metadataItemId = $field['meta'][$key] ?? $field['meta'][$this->getInsertId() . '[objects]'] ?? null;
            $relationlib->add_relation($this->trackerField->getOption(TrackerFieldRelation::OPT_RELATION), 'trackeritem', $this->getItemId(), $type, $id, false, $this->getFieldId(), $metadataItemId);
        }

        if (! empty($field['meta'])) {
            $toKeep = array_intersect(array_keys($map), $target);
            foreach ($toKeep as $key) {
                $relation = $map[$key];
                $metadataItemId = $field['meta'][$key] ?? $field['meta'][$this->getInsertId() . '[objects]'] ?? null;
                if ($relation->getMetadataItemId() != $metadataItemId) {
                    $relationlib->updateMetadataItemId($relation->id, $metadataItemId);
                }
            }
        }

        if ($remoteUrl) {
            foreach ($toRemove as $key) {
                $this->mirrorRemoteRelation($remoteUrl, $key, true);
            }
            foreach ($target as $key) {
                $this->mirrorRemoteRelation($remoteUrl, $key, false);
            }
        }

        if ($this->trackerField->getOption('refresh') == 'save') {
            $this->prepareRefreshRelated(array_merge($target, $toRemove));
        }

        return [
            'value' => $value,
        ];
    }

    public function watchCompare($old, $new)
    {
        return parent::watchCompareList(explode("\n", $old), explode("\n", $new), function ($item) {
            list($type, $object) = explode(':', $item, 2);
            return TikiLib::lib('object')->get_title($type, $object, $this->trackerField->getOption('format'));
        });
    }

    /**
     * Update existing data in relations table when changing the relation name.
     * Used when updating field options.
     *
     * @param $params - array of field options
     */
    public function convertFieldOptions($params)
    {
        if (empty($params['relation'])) {
            return;
        }
        if ($params['relation'] != $this->trackerField->getOption(TrackerFieldRelation::OPT_RELATION)) {
            $relationlib = TikiLib::lib('relation');
            $relationlib->update_relation($this->trackerField->getOption(TrackerFieldRelation::OPT_RELATION), $params['relation'], $this->getFieldId());
        }
    }

    public function handleFieldSave($data)
    {
        $trackerId = $this->getConfiguration('trackerId');
        $options = json_decode($data['options'], true);

        if (preg_match("/tracker_id=[^&]*{$trackerId}/", $options['filter']) && $this->trackerField->getOption(TrackerFieldRelation::OPT_INVERT) && $options['refresh']) {
            Feedback::warning(tr('Self-related fields with Include Invert option set to Yes should not have Force Refresh option on save.'));
        }
    }

    /**
     * When Relation field is removed, clean up the relations table.
     */
    public function handleFieldRemove()
    {
        $relationlib = TikiLib::lib('relation');
        $relationlib->remove_relation_type($this->trackerField->getOption(TrackerFieldRelation::OPT_RELATION), $this->getFieldId());
    }

    /**
     * Remote selector values are stored as type:r{urlhash}_{remoteId} so they
     * cannot collide with a local object that happens to use the same id.
     */
    private function normalizeRemoteTargets(string $remoteUrl, array $targets): array
    {
        $normalized = [];
        foreach ($targets as $key) {
            $key = trim((string) $key);
            if ($key === '' || ! str_contains($key, ':')) {
                continue;
            }
            [$type, $id] = explode(':', $key, 2);
            if ($id === '') {
                continue;
            }
            if (ObjectLib::remoteObjectLocalId($remoteUrl, $id) === null && ! ObjectLib::isRemoteObjectItemId($id)) {
                // Keep real local objects (for example invert links). The selector
                // already sends namespaced ids for objects chosen on the remote site.
                if (! ($type === 'trackeritem' && TikiLib::lib('trk')->get_item_info($id))) {
                    $id = ObjectLib::remoteObjectItemId($remoteUrl, $id);
                }
            }
            $normalized[] = $type . ':' . $id;
        }
        return array_values(array_unique($normalized));
    }

    /**
     * Copy this link onto the remote Tiki so its invert field can see it.
     * The remote call only writes there; it does not call back.
     */
    private function mirrorRemoteRelation(string $remoteUrl, string $key, bool $remove): void
    {
        global $base_url;

        $parts = explode(':', $key, 2);
        if (count($parts) !== 2) {
            return;
        }
        [$type, $storedId] = $parts;
        $remoteItemId = ObjectLib::remoteObjectLocalId($remoteUrl, $storedId);
        if ($type !== 'trackeritem' || $remoteItemId === null || ! $base_url) {
            return;
        }

        $relation = $this->trackerField->getOption(TrackerFieldRelation::OPT_RELATION);
        $ourId = ObjectLib::remoteObjectItemId(rtrim($base_url, '/'), (string) $this->getItemId());
        $title = TikiLib::lib('trk')->get_isMain_value(null, $this->getItemId());
        $href = rtrim($base_url, '/') . '/tiki-view_tracker_item.php?itemId=' . rawurlencode((string) $this->getItemId());

        try {
            $client = new Services_ApiClient($remoteUrl);
            $client->post($client->route('relations-mirror'), [
                'relation' => TrackerFieldRelation::getInvertQualifier($relation),
                'source_type' => 'trackeritem',
                'source_object' => $remoteItemId,
                'target_type' => 'trackeritem',
                'target_object' => $ourId,
                'title' => $title,
                'href' => $href,
                'remove' => $remove ? 1 : 0,
            ]);
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Fetch labels once, via the remote search-lookup API, and store them on tiki_objects.
     */
    private function cacheRemoteLabels(string $remoteUrl, array $targets): void
    {
        $wanted = [];
        foreach ($targets as $key) {
            $parts = explode(':', (string) $key, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$type, $storedId] = $parts;
            $localId = ObjectLib::remoteObjectLocalId($remoteUrl, $storedId);
            if ($localId === null) {
                continue;
            }
            $wanted[] = ['type' => $type, 'storedId' => $storedId, 'localId' => $localId];
        }
        if (! $wanted) {
            return;
        }

        try {
            $client = new Services_ApiClient($remoteUrl);
            $format = $this->trackerField->getOption('format') ?: '{title}';
            $localIds = array_values(array_unique(array_column($wanted, 'localId')));
            $filter = [
                'exact' => ['object_id' => $localIds],
            ];
            $types = array_unique(array_column($wanted, 'type'));
            if (count($types) === 1) {
                $filter['object_type'] = reset($types);
            }
            $result = $client->get($client->route('search-lookup'), [
                'format' => $format,
                'maxRecords' => count($localIds),
                'filter' => $filter,
            ]);
        } catch (Exception $e) {
            return;
        }

        $titles = [];
        foreach ($result['resultset']['result'] ?? [] as $row) {
            $title = $row['title'] ?? '';
            if (is_array($title)) {
                $title = implode(' / ', $title);
            }
            $titles[($row['object_type'] ?? '') . ':' . ($row['object_id'] ?? '')] = (string) $title;
        }

        $objectlib = TikiLib::lib('object');
        foreach ($wanted as $item) {
            $title = $titles[$item['type'] . ':' . $item['localId']] ?? '';
            if ($title === '') {
                continue;
            }
            $objectlib->cacheObjectLabel(
                $item['type'],
                $item['storedId'],
                $title,
                $objectlib->remoteObjectHref($remoteUrl, $item['type'], $item['localId'])
            );
        }
    }

    private function prepareRefreshRelated($target)
    {
        $itemId = $this->getItemId();
        // After saving the field, bind a temporary event on save to refresh child elements

        TikiLib::events()->bind('tiki.trackeritem.save', function ($args) use ($itemId, $target) {
            if ($args['type'] == 'trackeritem' && $args['object'] == $itemId) {
                $utilities = new Services_Tracker_Utilities();

                foreach ($target as $key) {
                    if (in_array($key, self::$refreshedTargets)) {
                        continue;
                    }
                    self::$refreshedTargets[] = $key;

                    list($type, $id) = explode(':', $key, 2);

                    if ($type == 'trackeritem' && ! ObjectLib::isRemoteObjectItemId($id)) {
                        $utilities->resaveItem($id);
                    }
                }
            }
        });
    }

    public static function syncRelationAdded($args)
    {
        self::syncContentUpdate($args, 'add');
    }

    public static function syncRelationRemoved($args)
    {
        self::syncContentUpdate($args, 'remove');
    }

    /**
     * Update field content based on relation add/remove event
     * @param array $args event arguments
     * @param string $action add/remove
     */
    public static function syncContentUpdate(array $args, string $action): void
    {
        $trklib = TikiLib::lib('trk');
        if (! empty($args['sourcefield'])) {
            $field = $trklib->get_field_info($args['sourcefield']);
            $handler = $trklib->get_field_handler($field);
            $relation = $handler->getOption(TrackerFieldRelation::OPT_RELATION);
            if (str_ends_with($relation, '.invert')) {
                $straight = false;
            } else {
                $straight = true;
            }
        } else {
            $straight = true;
        }
        if ($args['sourcetype'] == 'trackeritem') {
            if ($straight) {
                $relation = $args['relation'];
            } else {
                $relation = $args['relation'] . '.invert';
            }
            $trackerId = TikiLib::lib('trk')->get_tracker_for_item($args['sourceobject']);
            if (! $trackerId) {
                return;
            }
            $definition = Tracker_Definition::get($trackerId);
            if (! $definition) {
                return;
            }
            if ($fieldId = $definition->getRelationField($relation)) {
                $itemId = $args['sourceobject'];
                $value = $old_value = explode("\n", TikiLib::lib('trk')->get_item_value($trackerId, $itemId, $fieldId, false));
                $other = $args['type'] . ':' . $args['object'];
                if ($action == 'add') {
                    if (! in_array($other, $value)) {
                        $value[] = $other;
                    }
                } else {
                    $value = array_diff($value, [$other]);
                }
                if ($value != $old_value) {
                    $value = implode("\n", $value);
                    TikiLib::lib('trk')->modify_field($itemId, $fieldId, $value);
                }
            }
        }
        if ($args['type'] == 'trackeritem') {
            if ($straight) {
                $relation = $args['relation'] . '.invert';
            } else {
                $relation = $args['relation'];
            }
            $trackerId = TikiLib::lib('trk')->get_tracker_for_item($args['object']);
            if (! $trackerId) {
                return;
            }
            $definition = Tracker_Definition::get($trackerId);
            if (! $definition) {
                return;
            }
            if ($fieldId = $definition->getRelationField($relation)) {
                $itemId = $args['object'];
                $value = $old_value = explode("\n", TikiLib::lib('trk')->get_item_value($trackerId, $itemId, $fieldId, false));
                $other = $args['sourcetype'] . ':' . $args['sourceobject'];
                if ($action == 'add') {
                    if (! in_array($other, $value)) {
                        $value[] = $other;
                    }
                } else {
                    $value = array_diff($value, [$other]);
                }
                if ($value != $old_value) {
                    $value = implode("\n", $value);
                    TikiLib::lib('trk')->modify_field($itemId, $fieldId, $value);
                }
            }
        }
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory, $mode = '')
    {
        global $prefs;

        $baseKey = $this->getBaseKey();
        $data = $this->getFieldData();
        $value = $this->getValue();
        $format = $this->trackerField->getOption('format');

        // we don't have all the data in the field definition at this point, so just render the labels here
        $labels = [];
        if ($mode !== 'formatting') {
            $objects = [];
            foreach ($data['relations'] as $rel) {
                $objects[] = [
                    'type' => $rel->target->type,
                    'id' => $rel->target->itemId,
                    'metaItemId' => $rel->getMetadataItemId(),
                ];
            }
            if (! empty(self::$relationFieldCache)) {
                foreach ($objects as $object) {
                    if (isset(self::$relationFieldCache[$object['id']])) {
                        $item = self::$relationFieldCache[$object['id']];
                        $labels[] = preg_replace_callback('/\{([\w\.]+)\}/', function ($matches) use ($item, $format) {
                            $key = $matches[1];
                            if (isset($item[$key])) {
                                return $item[$key];
                            } elseif (! $format || $format == '{title}') {
                                return tr('empty');
                            } else {
                                return '';
                            }
                        }, $format);
                    } elseif (ObjectLib::isRemoteObjectItemId((string) $object['id'])) {
                        $labels[] = TikiLib::lib('object')->get_title($object['type'], (string) $object['id'], $format);
                    } else {
                        $labels[] = TikiLib::lib('trk')->get_isMain_value(null, $object['id']);
                    }
                }
            } else {
                $labels = TikiLib::lib('object')->get_titles($objects, $format);
                $labels = array_values($labels);
            }
        }

        $plain = implode(', ', $labels);

        $text = '';
        $count = count($labels);
        for ($i = 0; $i < $count; $i++) {
            $text .= $labels[$i];
            if ($i === $count - 2) {
                $text .= ' ' . tr('and') . ' ';
            } elseif ($i < $count - 1) {
                $text .= ', ';
            }
        }

        $meta = $this->aggregateAllRelatedHandlers($data['relations'], function ($handler) use ($typeFactory) {
            return $handler->getDocumentPart($typeFactory);
        });

        return array_merge([
            $baseKey => $typeFactory->sortable($value),
            "{$baseKey}_multi" => $typeFactory->multivalue(explode("\n", $value)),
            "{$baseKey}_plain" => $typeFactory->plainmediumtext($plain),
            "{$baseKey}_text" => $typeFactory->plainmediumtext($text),
        ], $meta);
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        $data = $this->getFieldData();
        $meta = $this->aggregateAllRelatedHandlers($data['relations'], function ($handler) {
            return $handler->getProvidedFields();
        });
        return array_merge([
            $baseKey,               // comma separated object_type:object_id
            "{$baseKey}_multi",     // array [object_type:object_id]
            "{$baseKey}_plain",     // comma separated formatted object titles
            "{$baseKey}_text",      // comma separated formatted object titles with "and" before the last one
        ], $meta);
    }

    public function getProvidedFieldTypes(): array
    {
        $baseKey = $this->getBaseKey();
        $data = $this->getFieldData();
        $meta = $this->aggregateAllRelatedHandlers($data['relations'], function ($handler) {
            return $handler->getProvidedFieldTypes();
        });
        return array_merge([
            $baseKey => 'sortable',
            "{$baseKey}_multi" => 'multivalue',
            "{$baseKey}_plain" => 'plainmediumtext',
            "{$baseKey}_text" => 'plainmediumtext',
        ], $meta);
    }

    public function getGlobalFields(): array
    {
        $baseKey = $this->getBaseKey();
        return ["{$baseKey}_plain" => true];    // index contents with the object titles
    }

    protected function aggregateAllRelatedHandlers(array $relations, callable $cb)
    {
        $meta = [];
        foreach ($relations as $rel) {
            if ($rel->metadata) {
                if ($item = $rel->metadata->getItem()) {
                    $definition = $item->getDefinition();
                    foreach ($definition->getFields() as $field) {
                        $handler = TikiLib::lib('trk')->get_field_handler($field, $item->getInfo());
                        $handler->setBaseKeyPrefix($this->getConfiguration('permName') . '_meta');
                        $meta = array_merge($meta, $cb($handler));
                    }
                }
            }
        }
        return $meta;
    }

    /**
     * Get related items' formatted values in an array. Useful in
     * Math calculations where individual field values are needed.
     * @return array associated array of field names and values
     */
    public function getItemValues()
    {
        $data = $this->getFieldData();
        $objects = [];
        foreach ($data['relations'] as $rel) {
            $objects[] = [
                'type' => $rel->target->type,
                'id' => $rel->target->itemId,
                'metaItemId' => $rel->getMetadataItemId(),
            ];
        }
        $format = $this->trackerField->getOption('format');
        return TikiLib::lib('object')->getFormattedValues($objects, $format);
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $format = $this->trackerField->getOption('format');

        $schema->addNew($permName, 'raw')
            ->setLabel($name)
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            })
            ;


        $fullLookup = new Tracker\Tabular\Schema\CachedLookupHelper();
        $fullLookup->setLookup(function ($value) use ($format) {
            $relations = explode("\n", $value);
            $labels = [];
            foreach ($relations as $identifier) {
                if (empty($identifier)) {
                    continue;
                }
                list($type, $object) = explode(':', $identifier);
                if (empty($type) || empty($object)) {
                    continue;
                }
                $labels[] = TikiLib::lib('object')->get_title($type, $object, $format);
            }
            return implode(", ", $labels);
        });
        $schema->addNew($permName, 'lookup')
            ->setLabel($name)
            ->setReadOnly(true)
            ->addQuerySource('text', "tracker_field_{$permName}_plain")
            ->setRenderTransform(function ($value, $extra) use ($fullLookup) {
                if (isset($extra['text'])) {
                    return $extra['text'];
                } else {
                    return $fullLookup->get($value);
                }
            })
            ;

        $schema->addNew($permName, 'full')
            ->setLabel($name)
            ->setRenderTransform(function ($value) use ($format) {
                $relations = explode("\n", $value);
                $data = [];
                foreach ($relations as $identifier) {
                    if (empty($identifier)) {
                        continue;
                    }
                    list($type, $object) = explode(':', $identifier);
                    if (empty($type) || empty($object)) {
                        continue;
                    }
                    $label = TikiLib::lib('object')->get_title($type, $object, $format);
                    $data[$identifier] = $label;
                }
                return json_encode($data);
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $values = [];
                $data = json_decode($value, true);
                if (is_array($data)) {
                    foreach ($data as $identifier => $label) {
                        $values[] = $identifier;
                    }
                }
                $info['fields'][$permName] = implode("\n", $values);
            })
            ;

        return $schema;
    }

    public function getFilterCollection()
    {
        $collection = new Tracker\Filter\Collection($this->getTrackerDefinition());
        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');
        $baseKey = $this->getBaseKey();

        $osParams = [
            '_filter' => $this->trackerField->getParsedFilter(),
            '_format' => $this->trackerField->getOption('format'),
        ];
        if ($remoteUrl = $this->trackerField->getOption('remoteUrl')) {
            $osParams['_remoteUrl'] = $remoteUrl;
        }

        $collection->addNew($permName, 'selector')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\ObjectSelector("tf_{$permName}_os", $osParams))
            ->setApplyCondition(function ($control, Search_Query $query) use ($baseKey) {
                $value = $control->getValue();

                if ($value) {
                    $query->filterMultivalue((string) $value, "{$baseKey}_multi");
                }
            })
            ;

        $collection->addNew($permName, 'multiselect')
            ->setLabel($name)
            ->setControl(new Tracker\Filter\Control\ObjectSelector("tf_{$permName}_ms", $osParams, true))
            ->setApplyCondition(function ($control, Search_Query $query) use ($permName, $baseKey, $multivalue) {
                $value = $control->getValue();

                if ($value) {
                    $sub = $query->getSubQuery("ms_$permName");
                    foreach ($value as $v) {
                        if ($v) {
                            $sub->filterMultivalue((string) $v, "{$baseKey}_multi");
                        }
                    }
                }
            });

        return $collection;
    }
}
