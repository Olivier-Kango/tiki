<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Tracker_SyncController
{
    private $utilities;

    public function setUp()
    {
        global $prefs;
        $this->utilities = new Services_Tracker_Utilities();

        if ($prefs['feature_trackers'] != 'y') {
            throw new Services_Exception_Disabled('feature_trackers');
        }

        if ($prefs['tracker_remote_sync'] != 'y') {
            throw new Services_Exception_Disabled('tracker_remote_sync');
        }

        if (! Perms::get()->admin_trackers) {
            throw new Services_Exception(tr('Reserved for tracker administrators'), 403);
        }
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'trackers';
    }

    public function action_clone_remote($input)
    {
        if (! Perms::get()->admin) {
            throw new Services_Exception(tr('Permission Denied'), 401);
        }

        $url = $input->url->url();
        $remoteTracker = $input->remote_tracker_id->int();

        if ($url) {
            $url = rtrim($url, '/');
            $tracker = $this->findTrackerInfo($url, $remoteTracker);

            if (! $tracker) {
                // Prepare the list for tracker selection
                $trackers = $this->getRemoteTrackerList($url);
                return [
                    'url' => $url,
                    'tracker_list' => $trackers['list'],
                ];
            } else {
                // Proceed with the tracker import
                $export = $this->getRemoteTrackerFieldExport($url, $remoteTracker);

                $trackerId = $this->utilities->createTracker($tracker);
                $this->createSynchronizedFields(
                    $trackerId,
                    $export,
                    ['provider' => $url, 'source' => $remoteTracker, 'last' => 0]
                );
                $this->utilities->createField(
                    [
                        'trackerId' => $trackerId,
                        'type' => 't',
                        'name' => tr('Remote Source'),
                        'permName' => 'syncSource',
                        'description' => tr('Automatically generated field for synchronized trackers. Contains the itemId of the remote item.'),
                        'options' => $this->utilities->buildOptions(
                            ['prepend' => $url . '/item'],
                            't'
                        ),
                        'isMandatory' => false,
                    ]
                );

                $this->registerSynchronization($trackerId, $url, $remoteTracker);

                return [
                    'trackerId' => $trackerId,
                ];
            }
        }

        return [
            'url' => $url,
            'title' => tr('Clone Remote Tracker'),
        ];
    }

    public function action_sync_meta($input)
    {
        list($trackerId, $definition, $syncInfo) = $this->readTracker($input);
        $factory = $definition->getFieldFactory();

        $export = $this->getRemoteTrackerFieldExport($syncInfo['provider'], $syncInfo['source']);
        foreach ($export as $info) {
            $localField = $definition->getFieldFromPermName($info['permName']);
            if (! $localField) {
                continue;
            }

            $handler = $factory->getHandler($info);
            if (! $handler instanceof \Tracker\Field\SynchronizableInterface) {
                continue;
            }

            $importable = $handler->importRemoteField($info, $syncInfo);
            $this->utilities->updateField($trackerId, $localField['fieldId'], $importable);
        }

        return [];
    }

    public function action_sync_refresh($input)
    {
        list($trackerId, $definition, $syncInfo) = $this->readTracker($input);

        set_time_limit(0); // Expected to take a while on larger trackers

        $this->utilities->clearTracker($trackerId);

        $itemMap = [];

        $remoteDefinition = $this->getRemoteDefinition($definition);
        $factory = $remoteDefinition->getFieldFactory();
        foreach ($this->getRemoteItems($syncInfo) as $item) {
            foreach ($item['fields'] as $key => & $value) {
                $field = $remoteDefinition->getFieldFromPermName($key);
                if ($field && $definition->getFieldFromPermName($key)) {
                    $handler = $factory->getHandler($field);
                    $value = $handler->importRemote($value);
                }
            }

            $item['fields']['syncSource'] = $item['itemId'];
            $newItem = $this->utilities->insertItem($definition, $item);

            $itemMap[ $item['itemId'] ] = $newItem;
        }

        if ($definition->getLanguageField()) {
            $this->attachTranslations($syncInfo, 'trackeritem', $itemMap);
        }

        $this->registerSynchronization($trackerId, $syncInfo['provider'], $syncInfo['source']);
        TikiLib::lib('unifiedsearch')->processUpdateQueue(count($itemMap) * 3); // Process lots of inserts
        return [];
    }

    public function action_sync_new($input)
    {
        list($trackerId, $definition, $syncInfo) = $this->readTracker($input);

        $items = $input->items->int();

        $trklib = TikiLib::lib('trk');
        $syncField = $definition->getFieldFromPermName('syncSource');
        $itemIds = $trklib->get_items_list($trackerId, $syncField['fieldId'], '', 'opc');

        if ($items) {
            set_time_limit(30 + 10 * count($items)); // 10 sec per item plus some initial overhead
            $itemIds = array_intersect($itemIds, $items);
            $table = TikiDb::get()->table('tiki_tracker_items');
            $items = $this->utilities->getItems(['trackerId' => $trackerId, 'itemId' => $itemIds]);

            $remoteDefinition = $this->getRemoteDefinition($definition);
            foreach ($items as $item) {
                $remoteItemId = $this->insertRemoteItem($remoteDefinition, $definition, $item);

                if ($remoteItemId) {
                    $item['fields']['syncSource'] = $remoteItemId;
                    $this->utilities->updateItem($definition, $item);
                } else {
                    Feedback::error(tr('Remote item not created, itemId = %0', $item['itemId']));
                }
            }
            TikiLib::lib('unifiedsearch')->processUpdateQueue();

            return [
            ];
        } else {
            return [
                'trackerId' => $trackerId,
                'sets' => ['items'],
                'items' => $this->getItemList($itemIds),
            ];
        }
    }

    public function action_sync_edit($input)
    {
        list($trackerId, $definition, $syncInfo) = $this->readTracker($input);

        // Collect local IDs that were modified
        $items = TikiDb::get()->table('tiki_tracker_items');
        $itemIds = $items->fetchColumn(
            'itemId',
            [
                'trackerId' => $trackerId,
                'created' => $items->lesserThan($syncInfo['last']),
                'lastModif' => $items->greaterThan($syncInfo['last']),
            ]
        );

        // Collect remote IDs that were modified
        $remoteItems = $this->getRemoteItems($syncInfo, ['modifiedSince' => $syncInfo['last']]);

        $modifiedIds = [];
        foreach ($remoteItems as $item) {
            $modifiedIds[] = $item['itemId'];
        }

        // Map from remote ID to local ID
        $syncField = $definition->getFieldFromPermName('syncSource');
        $fields = TikiDb::get()->table('tiki_tracker_item_fields');
        $itemMap = $fields->fetchMap(
            'itemId',
            'value',
            ['fieldId' => $syncField['fieldId'], 'value' => $fields->in($modifiedIds)]
        );

        $modifiedIds = array_keys($itemMap);
        $automatic = array_diff($itemIds, $modifiedIds);
        $manual = array_intersect($itemIds, $modifiedIds);

        set_time_limit(30 + 10 * count($automatic) + 10 * count($manual)); // 10 sec per item plus some initial overhead

        if ($input->automatic->int() || $input->manual->int()) {
            $remoteDefinition = $this->getRemoteDefinition($definition);
            $this->processUpdates('automatic', $automatic, $input, $definition, $remoteDefinition);
            $this->processUpdates('manual', $manual, $input, $definition, $remoteDefinition);
        }

        $manualList = $this->getItemList($manual);
        require_once 'lib/smarty_tiki/modifier.sefurl.php';
        foreach ($manualList as & $item) {
            $itemId = $item['itemId'];
            $item['remoteUrl'] = $syncInfo['provider'] . '/' . smarty_modifier_sefurl($itemMap[$itemId], 'trackeritem', '', '', 'n');
        }

        return [
            'trackerId' => $trackerId,
            'sets' => ['automatic', 'manual'],
            'automatic' => $this->getItemList($automatic),
            'manual' => $manualList,
        ];
    }

    private function createSynchronizedFields($trackerId, $data, $syncInfo)
    {
        if (! $data) {
            throw new Services_Exception(tr('Invalid data provided'), 400);
        }

        $definition = Tracker_Definition::get($trackerId);
        $factory = $definition->getFieldFactory();
        foreach ($data as $info) {
            $handler = $factory->getHandler($info);
            if ($handler instanceof \Tracker\Field\SynchronizableInterface) {
                $importable = $handler->importRemoteField($info, $syncInfo);
                $this->utilities->importField($trackerId, new JitFilter($importable), false);
            }
        }
    }

    private function getRemoteTrackerList($serviceUrl)
    {
        static $cache = [];
        if (isset($cache[$serviceUrl])) {
            return $cache[$serviceUrl];
        }

        $client = new Services_ApiClient($serviceUrl);
        $data = $client->get($client->route('trackers'));
        return $cache[$serviceUrl] = $data;
    }

    private function getRemoteTrackerFieldExport($serviceUrl, $trackerId)
    {
        $client = new Services_ApiClient($serviceUrl);
        $export = $client->get($client->route('trackerfields-export', ['trackerId' => $trackerId]));

        return TikiLib::lib('tiki')->read_raw($export['export']);
    }

    private function findTrackerInfo($serviceUrl, $trackerId)
    {
        $trackers = $this->getRemoteTrackerList($serviceUrl);
        foreach ($trackers['data'] as $info) {
            if ($info['trackerId'] == $trackerId) {
                unset($info['trackerId']);
                return $info;
            }
        }
    }

    private function registerSynchronization($localTrackerId, $serviceUrl, $remoteTrackerId)
    {
        $attributelib = TikiLib::lib('attribute');
        $attributelib->set_attribute('tracker', $localTrackerId, 'tiki.sync.provider', rtrim($serviceUrl, '/'));
        $attributelib->set_attribute('tracker', $localTrackerId, 'tiki.sync.source', $remoteTrackerId);
        $attributelib->set_attribute('tracker', $localTrackerId, 'tiki.sync.last', time()); // Real sync time, not tiki initial load
    }

    private function getRemoteItems($syncInfo, array $conditions = [])
    {
        $client = new Services_ApiClient($syncInfo['provider']);
        $route = $client->route('trackers-view', ['trackerId' => $syncInfo['source']]);
        return $client->getResultLoader($route, array_merge($conditions, ['format' => 'raw']));
    }

    private function insertRemoteItem($remoteDefinition, $definition, $item)
    {
        $syncInfo = $definition->getSyncInformation();

        $item['fields'] = $this->exportFields($item['fields'], $remoteDefinition, $definition);

        $client = new Services_ApiClient($syncInfo['provider']);
        $data = $client->post($client->route('trackeritems-create', ['trackerId' => $syncInfo['source']]), $item);

        if (isset($data['itemId']) && $data['itemId']) {
            return $data['itemId'];
        }
    }

    private function updateRemoteItem($remoteDefinition, $definition, $item)
    {
        $syncInfo = $definition->getSyncInformation();

        $item['fields'] = $this->exportFields($item['fields'], $remoteDefinition, $definition);

        $client = new Services_ApiClient($syncInfo['provider']);
        $client->post($client->route('trackeritems-update', ['trackerId' => $syncInfo['source'], 'itemId' => $item['fields']['syncSource']]), $item);
    }

    private function exportFields($fields, $remoteDefinition, $definition)
    {
        unset($fields['syncSource']);
        $factory = $definition->getFieldFactory();
        foreach ($fields as $key => & $value) {
            $field = $remoteDefinition->getFieldFromPermName($key);
            if ($field && $definition->getFieldFromPermName($key)) {
                $handler = $factory->getHandler($field);
                $value = $handler->exportRemote($value);
            }
        }

        return $fields;
    }

    private function attachTranslations($syncInfo, $type, $objectMap)
    {
        $unprocessed = $objectMap;
        $utilities = new Services_Language_Utilities();

        while (reset($unprocessed)) {
            $remoteSource = key($unprocessed);

            unset($unprocessed[$remoteSource]);

            $translations = $this->getRemoteTranslations($syncInfo, $type, $remoteSource);
            foreach ($translations as $remoteTarget) {
                unset($unprocessed[$remoteTarget]);
                $utilities->insertTranslation($type, $objectMap[ $remoteSource ], $objectMap[ $remoteTarget ]);
            }
        }
    }

    private function getRemoteTranslations($syncInfo, $type, $remoteSource)
    {
        $client = new Services_ApiClient($syncInfo['provider']);
        $data = $client->get($client->route('translations', ['type' => $type, 'source' => $remoteSource]));

        $out = [];

        if ($data['translations']) {
            foreach ($data['translations'] as $translation) {
                if ($translation['objId'] != $remoteSource) {
                    $out[] = $translation['objId'];
                }
            }
        }

        return $out;
    }

    private function getRemoteDefinition($definition)
    {
        $syncInfo = $definition->getSyncInformation();

        return Tracker_Definition::createFake(
            $definition->getInformation(),
            $this->getRemoteTrackerFieldExport($syncInfo['provider'], $syncInfo['source'])
        );
    }

    private function readTracker($input)
    {
        $trackerId = $input->trackerId->int();
        $definition = Tracker_Definition::get($trackerId);

        if (! $definition) {
            throw new Services_Exception(tr('Tracker does not exist'), 404);
        }

        $syncInfo = $definition->getSyncInformation();

        if (! $syncInfo) {
            throw new Services_Exception(tr('Tracker is not synchronized with a remote source.'), 409);
        }

        return [$trackerId, $definition, $syncInfo];
    }

    private function getItemList($itemIds)
    {
        $trklib = TikiLib::lib('trk');
        require_once 'lib/smarty_tiki/modifier.sefurl.php';

        $out = [];
        foreach ($itemIds as $itemId) {
            $out[] = [
                'itemId' => $itemId,
                'title' => $trklib->get_isMain_value(null, $itemId),
                'localUrl' => smarty_modifier_sefurl($itemId, 'trackeritem'),
            ];
        }

        return $out;
    }

    private function processUpdates($inputType, &$list, $input, $definition, $remoteDefinition)
    {
        $values = $input->$inputType->int();
        if (! is_array($values)) {
            return;
        }

        $toProcess = array_intersect($list, $values);
        $list = array_diff($list, $values);

        $table = TikiDb::get()->table('tiki_tracker_items');
        $itemList = $this->utilities->getItems(['trackerId' => $definition->getConfiguration('trackerId'), 'itemId' => $toProcess]);
        foreach ($itemList as $item) {
            $this->updateRemoteItem($remoteDefinition, $definition, $item);
            $this->utilities->removeItem($item['itemId']);
        }
    }
}
