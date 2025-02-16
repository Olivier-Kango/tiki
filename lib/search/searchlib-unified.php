<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Search\ContentSource\CreditSource;
use Tiki\Search\ContentSource\GoalSource;
use Tiki\Search\Elastic\ElasticSearchIndexManager;
use Tiki\Search\Manticore\ManticoreSearchIndexManager;
use Tiki\Search\MySql\MysqlSearchIndexManager;
use Tiki\TikiInit;

/**
 *
 */
class UnifiedSearchLib
{
    private const INCREMENT_QUEUE = 'search-increment';
    private const INCREMENT_QUEUE_REBUILD = 'search-increment-rebuild';

    private $batchToken;
    private $isRebuildingNow = false;
    private $indices;

    private static $fields = [];

    /**
     * @return string
     */
    public function startBatch()
    {
        if (! $this->batchToken) {
            $this->batchToken = uniqid();
            return $this->batchToken;
        }
    }

    /**
     * @param $token
     * @param int $count
     */
    public function endBatch($token, $count = 100)
    {
        if ($token && $this->batchToken === $token) {
            $this->batchToken = null;
            $previousLoopCount = null;
            while (($loopCount = $this->getQueueCount()) > 0) {
                if ($previousLoopCount !== null && $previousLoopCount <= $loopCount) {
                    break; // avoid to be blocked in loops if messages can not be processed
                }
                $previousLoopCount = $loopCount;
                $this->processUpdateQueue($count);
            }
            return true;
        }

        return false;
    }

    /**
     * @param int $count
     * @param bool $force run at the end of index rebuild and skip checking conditions
     */
    public function processUpdateQueue($count = 10, $force = false)
    {
        global $prefs;
        if (! isset($prefs['unified_engine']) && ! $force) {
            return;
        }

        if ($this->batchToken && ! $force) {
            return;
        }

        $queuelib = TikiLib::lib('queue');
        $toProcess = $queuelib->pull(self::INCREMENT_QUEUE, $count);
        if ($this->rebuildInProgress() && ! $force) {
            // Requeue to add to new index too (that is rebuilding)
            $queuelib->pushAll(self::INCREMENT_QUEUE_REBUILD, $toProcess);
        }
        $access = TikiLib::lib('access');
        $access->preventRedirect(true);

        if (count($toProcess)) {
            $indexer = null;
            try {
                // Since the object being updated may have category changes during the update,
                // make sure internal permission cache does not refer to the pre-update situation.
                Perms::getInstance()->clear();

                $index = $this->getIndex('data-write');
                $index = new Search_Index_TypeAnalysisDecorator($index);
                $indexer = $this->buildIndexer($index);
                $indexer->update($toProcess);

                if ($prefs['storedsearch_enabled'] == 'y') {
                    // Stored search relation adding may cause residual index backlog
                    $toProcess = $queuelib->pull(self::INCREMENT_QUEUE, $count);
                    $indexer->update($toProcess);
                }

                // Detect newly created identifier fields
                $initial = array_flip($prefs['unified_identifier_fields']);
                $collected = array_flip($index->getIdentifierFields());
                $combined = array_merge($initial, $collected);

                // Store preference only on change
                if (count($combined) > count($initial)) {
                    $tikilib = TikiLib::lib('tiki');
                    $tikilib->set_preference('unified_identifier_fields', array_keys($combined));
                }
            } catch (Exception $e) {
                // Re-queue pulled messages for next update
                foreach ($toProcess as $message) {
                    $queuelib->push(self::INCREMENT_QUEUE, $message);
                }

                Feedback::error(
                    tr('The search index could not be updated. The site is misconfigured. Contact an administrator.') .
                    '<br />' . $e->getMessage()
                );
            }

            if ($indexer) {
                $indexer->clearSources();
            }
        }

        $access->preventRedirect(false);
    }

    /**
     * @return array
     */
    public function getQueueCount()
    {
        $queuelib = TikiLib::lib('queue');
        return $queuelib->count(self::INCREMENT_QUEUE);
    }

    /**
     * @return bool
     */
    public function rebuildInProgress()
    {
        global $prefs;
        if ($prefs['unified_engine'] == 'elastic') {
            $name = $this->getIndexLocation('data');
            $connection = $this->getElasticConnection(true);
            return $connection->isRebuilding($name);
        } elseif ($prefs['unified_engine'] == 'mysql') {
            $lockName = TikiLib::lib('tiki')->get_preference('unified_mysql_index_rebuilding');
            return empty($lockName) ? false : TikiDb::get()->isLocked($lockName);
        } elseif ($prefs['unified_engine'] == 'manticore') {
            return ! empty(TikiLib::lib('tiki')->get_preference('unified_manticore_index_rebuilding'));
        }

        return false;
    }

    /**
     * @param int  $loggit   0=no logging, 1=log to Search_Indexer.log, 2=log to Search_Indexer_console.log
     * @param bool $fallback If the fallback index is being rebuild
     * @param Symfony\Component\Console\Helper\ProgressBar $progress progress bar object from rebuild console command
     *
     * @return array|bool
     * @throws Exception
     */
    public function rebuild($loggit = 0, $fallback = false, $progress = null)
    {
        global $prefs;
        $engineResults = null;

        if (! $fallback && $this->rebuildInProgress()) {
            Feedback::error(tr("Index is being rebuilt at the moment and cannot start another rebuild process."));
            if ($prefs['unified_engine'] == 'manticore') {
                Feedback::error(tr('If you are sure the previous rebuild task has finished, you can clean the current lock by executing the following console command: php console.php preferences:delete unified_manticore_index_rebuilding'));
            }
            return [];
        }

        $tikilib = TikiLib::lib('tiki');

        switch ($prefs['unified_engine']) {
            case 'elastic':
                $connection = $this->getElasticConnection(true);
                $aliasName = $prefs['unified_elastic_index_prefix'] . 'main';
                $indexName = $aliasName . '_' . uniqid();
                $index = new Search_Elastic_Index($connection, $indexName);
                $engineResults = new Search_EngineResult_Elastic($index);
                $index->setCamelCaseEnabled($prefs['unified_elastic_camel_case'] == 'y');

                TikiLib::events()->bind(
                    'tiki.process.shutdown',
                    function () use ($indexName, $index) {
                        global $prefs;
                        if ($prefs['unified_elastic_index_current'] !== $indexName) {
                            $index->destroy();
                        }
                    }
                );
                break;
            case 'mysql':
                $indexName = 'index_' . uniqid();
                $indexesToRestore = $this->getIndexesToRestore();
                $index = new Search_MySql_Index(TikiDb::get(), $indexName);
                $engineResults = new Search_EngineResult_MySQL($index);
                $tikilib->set_preference('unified_mysql_index_rebuilding', $indexName);
                TikiDb::get()->getLock($indexName);

                TikiLib::events()->bind(
                    'tiki.process.shutdown',
                    function () use ($indexName, $index) {
                        global $prefs;
                        if ($prefs['unified_mysql_index_current'] !== $indexName) {
                            $index->destroy();
                        }
                    }
                );
                break;
            case 'manticore':
                $indexName = $prefs['unified_manticore_index_prefix'] . 'main_' . uniqid();
                $index = new \Search\Manticore\Index($this->getManticoreClient('http'), $this->getManticoreClient('mysql'), $indexName);
                $engineResults = new Search_EngineResult_Manticore($index);
                $tikilib->set_preference('unified_manticore_index_rebuilding', $indexName);
                $tikilib->set_preference('unified_date_fields', json_encode([]));

                TikiLib::events()->bind(
                    'tiki.process.shutdown',
                    function () use ($indexName, $index, $tikilib) {
                        global $prefs;
                        if (! empty($prefs['unified_manticore_index_current']) && $prefs['unified_manticore_index_current'] !== $indexName) {
                            $index->destroy();
                            $indexpq = new \Search\Manticore\Index($this->getManticoreClient('http'), $this->getManticoreClient('mysql'), $indexName . 'pq');
                            $indexpq->destroy();
                        }
                        $tikilib->delete_preference('unified_manticore_index_rebuilding');
                    }
                );
                break;
            default:
                Feedback::error(tr('Unsupported index type "%0". Needs to be one of "mysql", "elastic" or "manticore". Try resaving the Search Control Panel', $prefs['unified_engine']));
                return [];
        }

        // Build in -new
        if (! $fallback) {
            TikiLib::lib('queue')->clear(self::INCREMENT_QUEUE);
            TikiLib::lib('queue')->clear(self::INCREMENT_QUEUE_REBUILD);
        }

        $access = TikiLib::lib('access');
        $access->preventRedirect(true);

        $this->isRebuildingNow = true;

        $stat = [];
        $indexer = null;
        $totalFieldsUsedIn = 'total fields used in the ' . $prefs['unified_engine'] . ' search index: ';
        try {
            $indexDecorator = new Search_Index_TypeAnalysisDecorator($index);
            $indexer = $this->buildIndexer($indexDecorator, $loggit);
            $lastStats = $tikilib->get_preference('unified_last_rebuild_stats', [], true);

            $stat = $tikilib->allocate_extra(
                'unified_rebuild',
                function () use ($indexer, $lastStats, $progress) {
                    return $indexer->rebuild($lastStats, $progress);
                }
            );

            if (! empty($indexesToRestore)) {
                $index->restoreOldIndexes($indexesToRestore, $indexName);
                $index->endUpdate();
            }

            $stat['total tiki fields indexed'] = $indexDecorator->getFieldCount();

            if (! is_null($engineResults)) {
                $fieldsCount = $engineResults->getEngineFieldsCount();

                if ($fieldsCount !== $stat['total tiki fields indexed']) {
                    $stat[$totalFieldsUsedIn] = $fieldsCount;
                }
                $tikilib->set_preference('unified_total_fields', $fieldsCount);
            }

            $tikilib->set_preference('unified_field_count', $indexDecorator->getFieldCount());
            $tikilib->set_preference('unified_identifier_fields', $indexDecorator->getIdentifierFields());

            $stats = [];
            $stats['default'] = $stat;

            // Force destruction to clear locks
            if ($indexer) {
                $indexer->clearSources();
                $this->formatStats($stats, function ($line) use ($indexer) {
                    $indexer->log->info($line);
                });
                unset($indexer);
            }

            unset($indexDecorator, $index);

            $oldIndices = null;
            switch ($prefs['unified_engine']) {
                case 'elastic':
                    $oldIndices = null; // assignAlias will handle the clean-up
                    $tikilib->set_preference('unified_elastic_index_current', $indexName);

                    $connection->assignAlias($aliasName, $indexName);

                    break;
                case 'mysql':
                    // Obtain the old index and destroy it after permanently replacing it.
                    $oldIndices = [$this->getIndex('data', false)];

                    $tikilib->set_preference('unified_mysql_index_current', $indexName);
                    TikiDb::get()->releaseLock($indexName);

                    break;
                case 'manticore':
                    // Obtain the old index and destroy it after permanently replacing it
                    $oldIndices = [$this->getIndex('data', false)];
                    // Obtain the list of older indices with the same prefix as we might not be pointing to the old index or there might be multiple stacked old indices
                    if (! empty($prefs['unified_manticore_index_prefix'])) {
                        $client = $this->getManticoreClient('mysql');
                        $existing = $client->getIndicesByPrefix($prefs['unified_manticore_index_prefix'] . 'main');
                        $oldIndices = [];
                        foreach ($existing as $existingName) {
                            if ($existingName != $indexName) {
                                $oldIndices[] = new \Search\Manticore\Index($this->getManticoreClient('http'), $this->getManticoreClient('mysql'), $existingName);
                            }
                        }
                    }
                    $tikilib->set_preference('unified_manticore_index_current', $indexName);
                    if ($prefs['federated_enabled'] === 'y') {
                        TikiLib::lib('federatedsearch')->recreateDistributedIndex($this->getManticoreClient('mysql'));
                    }
                    break;
            }

            if ($oldIndices) {
                foreach ($oldIndices as $oldIndex) {
                    if (! $oldIndex->destroy()) {
                        Feedback::error(tr('Failed to delete the old index.'));
                    }
                }
            }
        } catch (Exception $e) {
            $stats['default']['error'] = true;
            $stats['default']['error_message'] = tr('The search index could not be rebuilt.') . ' ' . $e->getMessage();
            Feedback::error(tr('The search index could not be rebuilt.') . '<br />' . $e->getMessage());
        }



        if ($fallback) {
            // Fallback index was rebuilt. Proceed with default index operations
            return $stats['default'];
        }

        // Rebuild mysql as fallback for elasticsearch engine
        list($fallbackEngine, $fallbackEngineName, $fallbackVersion) = TikiLib::lib('unifiedsearch')->getFallbackEngineDetails();
        if (! $fallback && $fallbackEngine) {
            $defaultEngine = $prefs['unified_engine'];
            $prefs['unified_engine'] = $fallbackEngine;
            $stats['fallback'] = $this->rebuild($loggit, true);
            $prefs['unified_engine'] = $defaultEngine;

            $log = new Laminas\Log\Writer\Stream($this->getLogFilename($loggit, $defaultEngine), 'a');
            $loggerInstance = new Laminas\Log\Logger();
            $loggerInstance->addWriter($log);

            $loggerInstance->info('Fallback:');
            $loggerInstance->info("  Engine $fallbackEngineName" . (empty($fallbackVersion) ? '' : ", version $fallbackVersion"));
            $loggerInstance->info("  Index $indexName");
            $loggerInstance->info('  Detailed information at ' . $this->getLogFilename($loggit, $fallbackEngine));
        }

        // Requeue messages that were added and processed in old index,
        // while rebuilding the new index
        $queueLib = TikiLib::lib('queue');
        $toProcess = $queueLib->pull(
            self::INCREMENT_QUEUE_REBUILD,
            $queueLib->count(self::INCREMENT_QUEUE_REBUILD)
        );
        $queueLib->pushAll(self::INCREMENT_QUEUE, $toProcess);

        // Process the documents updated while we were processing the update
        $this->processUpdateQueue(1000, true);

        if ($prefs['storedsearch_enabled'] == 'y') {
            TikiLib::lib('storedsearch')->reloadAll();
        }

        $tikilib->set_preference('unified_last_rebuild', $tikilib->now);
        $tikilib->set_preference('unified_last_rebuild_stats', $stats);

        $this->isRebuildingNow = false;
        $access->preventRedirect(false);

        return $stats;
    }

    /**
     * Return the current engine for unified search, version and current index name/table
     * @return array
     */
    public function getCurrentEngineDetails()
    {
        global $prefs;
        global $tikilib;

        switch ($prefs['unified_engine']) {
            case 'elastic':
                $elasticsearch = Search_Elastic_Connection::buildFromPrefs();
                $engine = 'Elastic';
                $version = $elasticsearch->getVersion();
                $index = $prefs['unified_elastic_index_current'];
                break;
            case 'mysql':
                $engine = 'MySQL';
                $version = $tikilib->getMySQLVersion();
                $index = $prefs['unified_mysql_index_current'];
                break;
            case 'manticore':
                $manticore = $this->getManticoreClient();
                $engine = 'Manticore';
                $version = $manticore->getVersion();
                $index = $prefs['unified_manticore_index_current'] ?? '';
                break;
            default:
                $engine = '';
                $version = '';
                $index = '';
                break;
        }

        return [$engine, $version, $index];
    }

    /**
     * Get the index location depending on $tikidomain for multi-tiki
     *
     * @param string $indexType
     * @param string $engine If not set, it uses default unified search engine
     * @return string    path to index directory
     * @throws Exception
     */
    public function getIndexLocation($indexType = 'data', $engine = null)
    {
        global $prefs, $tikidomain, $indexComparisonInProgess;
        $mapping = [
            'elastic' => [
                'data' => $prefs['unified_elastic_index_current'],
                'preference' => $prefs['unified_elastic_index_prefix'] . 'pref_' . $prefs['language'],
                'connect' => $prefs['unified_elastic_index_prefix'] . 'connect',
                'ondemand' => $prefs['unified_elastic_index_prefix'] . 'ondemand',
            ],
            'mysql' => [
                'data' => $prefs['unified_mysql_index_current'],
                'preference' => 'index_' . 'pref_' . $prefs['language'],
                'connect' => 'index_connect',
                'ondemand' => 'index_ondemand',
            ],
            'manticore' => [
                'data' => $prefs['unified_manticore_index_current'] ?? '',
                'preference' => $prefs['unified_manticore_index_prefix'] . 'pref_' . str_replace('-', '_', $prefs['language']),
                'connect' => $prefs['unified_manticore_index_prefix'] . 'connect',
                'ondemand' => $prefs['unified_manticore_index_prefix'] . 'ondemand',
            ],
        ];

        // make sure current index is prefixed by the configured prefix, otherwise refuse to use it
        // this fixes problems when cloning data, copying databases to other Tikies and reusing the same production index
        // This condition should not apply in the case where the index:comapre-engines command is running
        if (! empty($prefs['unified_elastic_index_prefix']) && substr($mapping['elastic']['data'], 0, strlen($prefs['unified_elastic_index_prefix'] . 'main')) !== $prefs['unified_elastic_index_prefix'] . 'main' && ! isset($indexComparisonInProgess)) {
            $mapping['elastic']['data'] = '';
        }
        if (! empty($prefs['unified_manticore_index_prefix']) && substr($mapping['manticore']['data'], 0, strlen($prefs['unified_manticore_index_prefix'] . 'main')) !== $prefs['unified_manticore_index_prefix'] . 'main' && ! isset($indexComparisonInProgess)) {
            $mapping['manticore']['data'] = '';
        }

        $engine = $engine ?: $prefs['unified_engine'];

        if (isset($mapping[$engine][$indexType])) {
            $index = $mapping[$engine][$indexType];

            return $index;
        } else {
            throw new Exception('Internal: Invalid index requested: ' . $indexType);
        }
    }

    /**
     * @param $type
     * @param $objectId
     */
    public function invalidateObject($type, $objectId)
    {
        TikiLib::lib('queue')->push(
            self::INCREMENT_QUEUE,
            [
                'object_type' => $type,
                'object_id' => $objectId
            ]
        );
    }

    /**
     * Invalidate the indices cache
     */
    public function invalidateIndicesCache()
    {
        $this->indices = [];
    }

    /**
     * Replace index in the cache - used by tests.
     */
    public function replaceIndexCache($indexType, $index)
    {
        $this->indices[$indexType] = $index;
    }

    /**
     * @return array
     */
    public function getSupportedTypes()
    {
        global $prefs;
        $types = [];

        if ($prefs['feature_wiki'] == 'y') {
            $types['wiki page'] = tra('wiki page');
        }

        if ($prefs['feature_blogs'] == 'y') {
            $types['blog'] = tra('blog');
            $types['blog post'] = tra('blog post');
        }

        if ($prefs['feature_articles'] == 'y') {
            $types['article'] = tra('article');
        }

        if ($prefs['feature_file_galleries'] == 'y') {
            $types['file'] = tra('file');
            $types['file gallery'] = tra('file gallery');
        }

        if ($prefs['feature_forums'] == 'y') {
            $types['forum post'] = tra('forum post');
            $types['forum'] = tra('forum');
        }

        if ($prefs['feature_trackers'] == 'y') {
            $types['trackeritem'] = tra('tracker item');
            $types['tracker'] = tra('tracker');
            $types['trackerfield'] = tra('tracker field');
        }

        if ($prefs['feature_sheet'] == 'y') {
            $types['sheet'] = tra('sheet');
        }

        if (
            $prefs['feature_wiki_comments'] == 'y'
            || $prefs['feature_article_comments'] == 'y'
            || $prefs['feature_poll_comments'] == 'y'
            || $prefs['feature_file_galleries_comments'] == 'y'
            || $prefs['feature_trackers'] == 'y'
        ) {
            $types['comment'] = tra('comment');
        }

        if ($prefs['feature_categories'] === 'y') {
            $types['category'] = tra('category');
        }

        if ($prefs['feature_webservices'] === 'y') {
            $types['webservice'] = tra('webservice');
        }

        if ($prefs['activity_basic_events'] === 'y' || $prefs['activity_custom_events'] === 'y') {
            $types['activity'] = tra('activity');
        }

        if ($prefs['feature_calendar'] === 'y') {
            $types['calendaritem'] = tra('calendar item');
            $types['calendar'] = tra('calendar');
        }

        $types['user'] = tra('user');
        $types['group'] = tra('group');

        return $types;
    }

    /**
     * Read log files
     *
     * @param int $num Number of items to return
     * @param string $needle Search for a string
     * @param bool $reverse Reverse the results to return the first or the last lines
     * @return array
     */
    public function getLogItems($num = -1, $needle = '', $reverse = false)
    {
        $files['web'] = $this->getLogFilename(1);
        $files['console'] = $this->getLogFilename(2);
        $resultLines = [];
        foreach ($files as $type => $filename) {
            $count = 1;
            $handle = fopen($filename, "r");
            if ($handle) {
                $resultLines[$type]['logs'] = [];
                $resultLines[$type]['file'] = $filename;
                while (($line = fgets($handle)) !== false) {
                    $pos = strpos($line, $needle);
                    if (empty($needle) || $pos !== false) {
                        array_push($resultLines[$type]['logs'], $line);
                    }
                    $count++;
                }
                fclose($handle);
            }
            if (! empty($resultLines[$type]['logs'])) {
                if ($reverse) {
                    $resultLines[$type]['logs'] = array_reverse($resultLines[$type]['logs']);
                }
                if ($num > -1) {
                    $resultLines[$type]['logs'] = array_slice($resultLines[$type]['logs'], -$num);
                }
            } else {
                unset($resultLines[$type]);
            }
        }

        return $resultLines;
    }

    public function getLastLogItem()
    {
        $files['web'] = $this->getLogFilename(1);
        $files['console'] = $this->getLogFilename(2);
        foreach ($files as $type => $file) {
            if ($fp = @fopen($file, "r")) {
                $pos = -2;
                $t = " ";
                while ($t != "\n") {
                    if (! fseek($fp, $pos, SEEK_END)) {
                        $t = fgetc($fp);
                        $pos = $pos - 1;
                    } else {
                        rewind($fp);
                        break;
                    }
                }
                $t = fgets($fp);
                fclose($fp);
                $ret[$type] = $t;
            } else {
                $ret[$type] = '';
            }
        }
        return $ret;
    }

    /**
     * @param $index
     * @param int $loggit 0=no logging, 1=log to Search_Indexer.log, 2=log to Search_Indexer_console.log
     * @return Search_Indexer
     */
    public function buildIndexer($index, $loggit = 0)
    {
        global $prefs;

        $isRepository = $index instanceof Search_Index_QueryRepository;

        if (! $isRepository && method_exists($index, 'getRealIndex')) {
            $isRepository = $index->getRealIndex() instanceof Search_Index_QueryRepository;
        }

        if (! $this->isRebuildingNow && $isRepository && $prefs['storedsearch_enabled'] == 'y') {
            $index = new Search_Index_QueryAlertDecorator($index);
        }

        if (! empty($prefs['unified_excluded_categories'])) {
            $index = new Search_Index_CategoryFilterDecorator(
                $index,
                array_filter(
                    array_map(
                        'intval',
                        $prefs['unified_excluded_categories']
                    )
                )
            );
        }

        $logWriter = null;

        if ($loggit) {
            $logWriter = new Laminas\Log\Writer\Stream($this->getLogFilename($loggit), 'w');
        }

        $indexer = new Search_Indexer($index, $logWriter);
        $this->addSources($indexer, 'indexing');

        if ($prefs['unified_tokenize_version_numbers'] == 'y') {
            $indexer->addContentFilter(new Search_ContentFilter_VersionNumber());
        }

        return $indexer;
    }

    public function getDocuments($type, $object)
    {
        $indexer = $this->buildIndexer($this->getIndex());
        return $indexer->getDocuments($type, $object);
    }

    /**
     * Retrieve available fields in the search index by object type
     * and split for each tracker. Value is cached in the static property.
     */
    public function getAvailableFields()
    {
        if (self::$fields) {
            return self::$fields;
        }
        $indexer = $this->buildIndexer($this->getIndex());
        $fields = $indexer->getAvailableFields();
        // trackeritem object type needs to further split fields by tracker ID, so we know which are relevant to each tracker item object
        if (isset($fields['object_types']['trackeritem'])) {
            $types_by_tracker_id = [];
            $all_fields = TikiLib::lib('trk')->table('tiki_tracker_fields')->fetchMap('permName', 'trackerId', []);
            foreach ($all_fields as $permName => $trackerId) {
                foreach ($fields['object_types']['trackeritem'] as $field) {
                    if (str_starts_with($field, 'tracker_field_' . $permName)) {
                        $types_by_tracker_id['trackeritem' . $trackerId][] = $field;
                    }
                }
            }
            foreach ($types_by_tracker_id as $type => $_) {
                foreach ($fields['object_types']['trackeritem'] as $field) {
                    if (! str_starts_with($field, 'tracker_field_')) {
                        $types_by_tracker_id[$type][] = $field;
                    }
                }
            }
            $fields['object_types'] = array_merge($fields['object_types'], $types_by_tracker_id);
        }
        self::$fields = $fields;
        return self::$fields;
    }

    /**
     * Ability to set available fields in the search index, used by tests.
     */
    public function setAvailableFields($fields)
    {
        self::$fields = $fields;
    }

    /**
     * @param Search_Indexer $aggregator
     * @param string $mode
     */
    private function addSources($aggregator, $mode = 'indexing')
    {
        global $prefs;

        $types = $this->getSupportedTypes();

        // Content Sources
        if (isset($types['trackeritem'])) {
            $aggregator->addContentSource('trackeritem', new Search_ContentSource_TrackerItemSource($mode));
            $aggregator->addContentSource('tracker', new Search_ContentSource_TrackerSource());
            $aggregator->addContentSource('trackerfield', new Search_ContentSource_TrackerFieldSource());
        }

        if (isset($types['forum post'])) {
            $aggregator->addContentSource('forum post', new Search_ContentSource_ForumPostSource());
            $aggregator->addContentSource('forum', new Search_ContentSource_ForumSource());
        }

        if (isset($types['blog'])) {
            $aggregator->addContentSource('blog', new Search_ContentSource_BlogSource());
        }

        if (isset($types['blog post'])) {
            $aggregator->addContentSource('blog post', new Search_ContentSource_BlogPostSource());
        }

        if (isset($types['article'])) {
            $articleSource = new Search_ContentSource_ArticleSource();
            $aggregator->addContentSource('article', $articleSource);
            $aggregator->addGlobalSource(new Search_GlobalSource_ArticleAttachmentSource($articleSource));
        }

        if (isset($types['file'])) {
            $fileSource = new Search_ContentSource_FileSource();
            $aggregator->addContentSource('file', $fileSource);
            $aggregator->addContentSource('file gallery', new Search_ContentSource_FileGallerySource());
            $aggregator->addGlobalSource(new Search_GlobalSource_FileAttachmentSource($fileSource));
        }

        if (isset($types['sheet'])) {
            $aggregator->addContentSource('sheet', new Search_ContentSource_SheetSource());
        }

        if (isset($types['comment'])) {
            $commentTypes = [];
            if ($prefs['feature_wiki_comments'] == 'y') {
                $commentTypes[] = 'wiki page';
            }
            if ($prefs['feature_article_comments'] == 'y') {
                $commentTypes[] = 'article';
            }
            if ($prefs['feature_poll_comments'] == 'y') {
                $commentTypes[] = 'poll';
            }
            if ($prefs['feature_file_galleries_comments'] == 'y') {
                $commentTypes[] = 'file gallery';
            }
            if ($prefs['feature_trackers'] == 'y') {
                $commentTypes[] = 'trackeritem';
            }

            $aggregator->addContentSource('comment', new Search_ContentSource_CommentSource($commentTypes));
            $aggregator->addGlobalSource(new Search_GlobalSource_CommentSource());
        }

        if (isset($types['user'])) {
            $aggregator->addContentSource('user', new Search_ContentSource_UserSource($prefs['user_in_search_result']));
        }

        if (isset($types['group'])) {
            $aggregator->addContentSource('group', new Search_ContentSource_GroupSource());
        }

        if (isset($types['calendar'])) {
            $aggregator->addContentSource('calendaritem', new Search_ContentSource_CalendarItemSource());
            $aggregator->addContentSource('calendar', new Search_ContentSource_CalendarSource());
        }

        if ($prefs['activity_custom_events'] == 'y' || $prefs['activity_basic_events'] == 'y' || $prefs['monitor_enabled'] == 'y') {
            if ($prefs['activity_stream_disable_indexing'] !== 'y') {
                $aggregator->addContentSource('activity', new Search_ContentSource_ActivityStreamSource($aggregator instanceof Search_Indexer ? $aggregator : null));
            }
        }

        if ($prefs['goal_enabled'] == 'y') {
            $aggregator->addContentSource('goal', new GoalSource());
            $aggregator->addContentSource('goalevent', new Search_ContentSource_GoalEventSource());
        }

        if ($prefs['feature_credits'] === 'y') {
            $aggregator->addContentSource('credit', new CreditSource());
        }

        if ($prefs['feature_webservices'] === 'y') {
            $aggregator->addContentSource('webservice', new Search_ContentSource_WebserviceSource());
        }

        if (isset($types['wiki page'])) {
            $aggregator->addContentSource('wiki page', new Search_ContentSource_WikiSource());
        }

        // Global Sources
        if ($prefs['feature_categories'] == 'y') {
            $aggregator->addGlobalSource(new Search_GlobalSource_CategorySource());
            $aggregator->addContentSource('category', new Search_ContentSource_CategorySource());
        }

        if ($prefs['feature_freetags'] == 'y') {
            $aggregator->addGlobalSource(new Search_GlobalSource_FreeTagSource());
        }

        if ($prefs['rating_advanced'] == 'y' && $mode == 'indexing') {
            $aggregator->addGlobalSource(new Search_GlobalSource_AdvancedRatingSource($prefs['rating_recalculation'] == 'indexing'));
        }

        $aggregator->addGlobalSource(new Search_GlobalSource_Geolocation());

        if ($prefs['feature_search_show_visit_count'] === 'y') {
            $aggregator->addGlobalSource(new Search_GlobalSource_VisitsSource());
        }

        if ($prefs['feature_friends'] === 'y') {
            $aggregator->addGlobalSource(new Search_GlobalSource_SocialSource());
        }

        if ($prefs['federated_enabled'] === 'y' && $prefs['unified_engine'] == 'manticore') {
            $aggregator->addGlobalSource(new Search_GlobalSource_IndexNameSource());
        }

        if ($mode == 'indexing') {
            $aggregator->addGlobalSource(new Search_GlobalSource_PermissionSource(Perms::getInstance()));
            $aggregator->addGlobalSource(new Search_GlobalSource_RelationSource());
        }

        $aggregator->addGlobalSource(new Search_GlobalSource_TitleInitialSource());
        $aggregator->addGlobalSource(new Search_GlobalSource_SearchableSource());
        $aggregator->addGlobalSource(new Search_GlobalSource_UrlSource());
    }

    /**
     * @return Search_Index_Interface
     */
    public function getIndex($indexType = 'data', $useCache = true)
    {
        global $prefs, $tiki_p_admin;

        if (isset($this->indices[$indexType]) && $useCache) {
            return $this->indices[$indexType];
        }

        $writeMode = false;
        if ($indexType == 'data-write') {
            $indexType = 'data';
            $writeMode = true;
        }

        $engine = $prefs['unified_engine'];
        $fallbackMySQL = false;

        if ($engine == 'elastic' && $index = $this->getIndexLocation($indexType)) {
            $connection = $this->getElasticConnection($writeMode);
            if ($connection->getStatus()->status === 200) {
                $index = new Search_Elastic_Index($connection, $index);
                $index->setCamelCaseEnabled($prefs['unified_elastic_camel_case'] == 'y');
                $index->setPossessiveStemmerEnabled($prefs['unified_elastic_possessive_stemmer'] == 'y');
                $index->setFacetCount($prefs['search_facet_default_amount']);

                if ($useCache) {
                    $this->indices[$indexType] = $index;
                }
                return $index;
            }

            if ($prefs['unified_elastic_mysql_search_fallback'] === 'y') {
                $fallbackMySQL = true;
                $prefs['unified_incremental_update'] = 'n';
            }
        }

        if (($engine == 'mysql' || $fallbackMySQL) && $index = $this->getIndexLocation($indexType, 'mysql')) {
            $index = new Search_MySql_Index(TikiDb::get(), $index);

            if ($useCache) {
                $this->indices[$indexType] = $index;
            }
            return $index;
        }

        if ($engine == 'manticore' && $index = $this->getIndexLocation($indexType)) {
            try {
                $index = new \Search\Manticore\Index($this->getManticoreClient('http'), $this->getManticoreClient('mysql'), $index);

                if ($useCache) {
                    $this->indices[$indexType] = $index;
                }
                return $index;
            } catch (\Search\Manticore\Exception $e) {
                if ($tiki_p_admin == 'y') {
                    Feedback::error($e->getMessage());
                }
            }
        }

        // Do nothing, provide a fake index.
        if ($tiki_p_admin != 'y') {
            Feedback::error(tr('Contact the site administrator. The index needs rebuilding.'));
        }

        return new Search_Index_Memory();
    }

    /**
     * Return the number of documents created in the last rebuild.
     * @param string $index Index name
     * @return int
     */
    public function getLastRebuildDocsCount($index = 'default')
    {
        global $tikilib;
        $lastStats = $tikilib->get_preference('unified_last_rebuild_stats', [], true);
        if (! isset($lastStats[$index]['counts'])) {
            return 0;
        }
        return array_sum($lastStats[$index]['counts']);
    }

    public function getEngineInfo()
    {
        global $prefs;

        switch ($prefs['unified_engine']) {
            case 'mysql':
                return $this->getMySqlEngineInfo();
            case 'elastic':
                $info = [];

                try {
                    $connection = $this->getElasticConnection(true);
                    $root = $connection->rawApi('/');
                    $info[tr('Client Node')] = $root->name;
                    $info[tr('Elasticsearch Version')] = $root->version->number;
                    $info[tr('Lucene Version')] = $root->version->lucene_version;

                    $cluster = $connection->rawApi('/_cluster/health');
                    $info[tr('Cluster Name')] = $cluster->cluster_name;
                    $info[tr('Cluster Status')] = $cluster->status;
                    $info[tr('Cluster Node Count')] = $cluster->number_of_nodes;

                    if (version_compare($root->version->number, '1.0.0') === -1) {
                        $status = $connection->rawApi('/_status');
                        foreach ($status->indices as $indexName => $data) {
                            if (strpos($indexName, $prefs['unified_elastic_index_prefix']) === 0) {
                                $info[tr('Index %0', $indexName)] = tr(
                                    '%0 documents, totaling %1',
                                    $data->docs->num_docs,
                                    $data->index->primary_size
                                );
                            }
                        }

                        $nodes = $connection->rawApi('/_nodes/jvm/stats');
                        foreach ($nodes->nodes as $node) {
                            $info[tr('Node %0', $node->name)] = tr('Using %0, since %1', $node->jvm->mem->heap_used, $node->jvm->uptime);
                        }
                    } else {
                        $status = $connection->getIndexStatus();

                        foreach ($status->indices as $indexName => $data) {
                            if (strpos($indexName, $prefs['unified_elastic_index_prefix']) === 0) {
                                if (isset($data->primaries)) {  // v2
                                    $info[tr('Index %0', $indexName)] = tr(
                                        '%0 documents, totaling %1 bytes',
                                        $data->primaries->docs->count,
                                        number_format($data->primaries->store->size_in_bytes)
                                    );
                                } else {                    // v1
                                    $info[tr('Index %0', $indexName)] = tr(
                                        '%0 documents, totaling %1 bytes',
                                        $data->docs->num_docs,
                                        number_format($data->index->primary_size_in_bytes)
                                    );
                                }
                            }
                        }

                        $nodes = $connection->rawApi('/_nodes/stats');
                        foreach ($nodes->nodes as $node) {
                            $info[tr('Node %0', $node->name)] = tr('Using %0 bytes, since %1', number_format($node->jvm->mem->heap_used_in_bytes), date('Y-m-d H:i:s', $node->jvm->timestamp / 1000));
                        }

                        $field_count_on_last_rebuild = $prefs['unified_total_fields'] ?? $prefs['unified_field_count'] ?? null;
                        if (! is_null($field_count_on_last_rebuild)) {
                            $info[tr('Field Count Tried on Last Rebuild')] = $field_count_on_last_rebuild;
                            if ($field_count_on_last_rebuild > $prefs['unified_elastic_field_limit']) {
                                $info[tr('Warning')] = tr('Field limit setting is lower than Tiki needs to store in the index!');
                            }
                        }
                    }
                } catch (Search_Elastic_Exception $e) {
                    $info[tr('Information Missing')] = $e->getMessage();
                }

                if ($prefs['unified_elastic_mysql_search_fallback'] === 'y') {
                    $info = array_merge($info, $this->getMySqlEngineInfo());
                }

                return $info;
            case 'manticore':
                // TODO: use META stats
                return [];
            default:
                return [];
        }
    }

    /**
     * Retrieve Mysql Engine Stats
     * @return array
     */
    private function getMySqlEngineInfo(): array
    {
        global $tikilib;

        $info = [];
        $totalDocuments = $this->getLastRebuildDocsCount();

        list($engine, $version) = $this->getCurrentEngineDetails();
        if (! empty($version)) {
            $info['MySQL Version'] = $version;
        }

        $query = "SELECT table_name as tbl_name, table_rows as tbl_rows FROM information_schema.tables " .
            "WHERE table_schema = DATABASE() AND table_name like 'index_%'";
        $indexResult = TikiDb::get()->query($query)->result;

        foreach ($indexResult as $index) {
            $indexName = $index['tbl_name'] ?: '';
            if (preg_match('/index_([a-z0-9_]+$)/', $indexName)) {
                $info[tr('MySQL Index %0', $indexName)] = tr(
                    '%0 documents, using %1 of %2 indexes',
                    $totalDocuments,
                    count(TikiDb::get()->fetchAll("SHOW INDEXES FROM $indexName")),
                    Search_MySql_Table::MAX_MYSQL_INDEXES_PER_TABLE
                );
                continue;
            }

            $info[tr('MySQL Index %0', $indexName)] = tr('%0 documents', $index['tbl_rows'] ?: 0);
        }

        $lastRebuild = $tikilib->get_preference('unified_last_rebuild');
        if (! empty($lastRebuild)) {
            $info['MySQL Last Rebuild Index'] = $tikilib->get_long_date($lastRebuild) . ', ' . $tikilib->get_long_time($lastRebuild);
        }

        return $info;
    }

    public function getElasticIndexInfo($indexName)
    {
        $connection = $this->getElasticConnection(false);

        try {
            $mapping = $connection->rawApi("/$indexName/_mapping");

            return $mapping;
        } catch (Search_Elastic_Exception $e) {
            return false;
        }
    }

    private function getElasticConnection($useMasterOnly)
    {
        global $prefs;
        static $connections = [];

        $target = $prefs['unified_elastic_url'];

        if (! $useMasterOnly && $prefs['federated_elastic_url']) {
            $target = $prefs['federated_elastic_url'];
        }

        if (! empty($connections[$target])) {
            return $connections[$target];
        }

        $connection = Search_Elastic_Connection::buildFromPrefs($target);
        $connection->startBulk();
        $connection->persistDirty(TikiLib::events());

        $connections[$target] = $connection;
        return $connection;
    }

    private function getManticoreClient($type = 'http')
    {
        global $prefs;
        static $clients = [];

        $target = $prefs['unified_manticore_url'];

        if (! empty($clients[$target . $type])) {
            return $clients[$target . $type];
        }

        switch ($type) {
            case "http":
                $client = new \Search\Manticore\Client($target, $prefs['unified_manticore_http_port']);
                break;
            case "mysql":
                $client = new \Search\Manticore\PdoClient($target, $prefs['unified_manticore_mysql_port']);
                $client->startBulk();
                break;
            default:
                throw new Exception(tr('Invalid Manticore Search client type: %0', $type));
        }

        $clients[$target . $type] = $client;
        return $client;
    }

    /**
     * @param string $mode
     * @return Search_Formatter_DataSource_Interface
     */
    public function getDataSource($mode = 'formatting')
    {
        global $prefs;

        $dataSource = new Search_Formatter_DataSource_Declarative();

        $this->addSources($dataSource, $mode);

        if ($mode === 'formatting') {
            if ($prefs['unified_engine'] === 'mysql') {
                $dataSource->setPrefilter(
                    function ($fields, $entry) {
                        return (new Search_MySql_Prefilter())->get($fields, $entry);
                    }
                );
            } elseif ($prefs['unified_engine'] === 'elastic') {
                $connection = $this->getElasticConnection(false);

                if ($connection->getStatus()->status === 200) {
                    $dataSource->setPrefilter(function ($fields, $entry) {
                        return (new Search_Elastic_Prefilter())->get($fields, $entry);
                    });
                }
            } elseif ($prefs['unified_engine'] === 'manticore') {
                $client = $this->getManticoreClient();
                $status = $client->getStatus();

                if (isset($status['version'])) {
                    $dataSource->setPrefilter(function ($fields, $entry) {
                        return (new \Search\Manticore\Prefilter())->get($fields, $entry);
                    });
                }
            }

            // If prefilter was not loaded, the main search engine might not be working properly, lets use the fallback one if possible
            if (! $dataSource->isPrefilterSet()) {
                if ($prefs['unified_elastic_mysql_search_fallback'] === 'y') {
                    $dataSource->setPrefilter(
                        function ($fields, $entry) {
                            return (new Search_MySql_Prefilter())->get($fields, $entry);
                        }
                    );
                }
            }
        }

        return $dataSource;
    }

    public function getProfileExportHelper()
    {
        $helper = new Tiki_Profile_Writer_SearchFieldHelper();
        $this->addSources($helper, 'indexing'); // Need all fields, so use indexing

        return $helper;
    }

    /**
     * @return Search_Query_WeightCalculator_Field
     */
    public function getWeightCalculator()
    {
        global $prefs;

        $lines = explode("\n", $prefs['unified_field_weight']);

        $weights = [];
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) == 2) {
                $parts = array_map('trim', $parts);

                $weights[$parts[0]] = $parts[1];
            }
        }

        return new Search_Query_WeightCalculator_Field($weights);
    }

    public function initQuery(Search_Query $query)
    {
        $this->initQueryBase($query);
        $this->initQueryPermissions($query);
        $this->initQueryPresentation($query);
    }

    public function initQueryBase($query, $applyJail = true)
    {
        global $prefs;

        $query->setWeightCalculator($this->getWeightCalculator());
        $query->setIdentifierFields($prefs['unified_identifier_fields']);

        $categlib = TikiLib::lib('categ');
        if ($applyJail && $jail = $categlib->get_jail(false)) {
            $query->filterCategory(implode(' or ', $jail), true);
        }
    }

    public function initQueryPermissions($query)
    {
        global $user;

        if (! Perms::get()->admin) {
            $query->filterPermissions(Perms::get()->getGroups(), $user);
        }
    }

    public function initQueryPresentation($query)
    {
        $query->applyTransform(new Search_Formatter_Transform_DynamicLoader($this->getDataSource('formatting')));
    }

    /**
     * @param array $filter
     * @return Search_Query
     */
    public function buildQuery(array $filter, $query = null)
    {
        if (! $query) {
            $query = new Search_Query();
            $this->initQuery($query);
        }

        if (! is_array($filter)) {
            throw new Exception('Invalid filter type provided in query. It must be an array.');
        }

        if (isset($filter['type']) && $filter['type']) {
            $query->filterType($filter['type']);
        }

        if (isset($filter['categories']) && $filter['categories']) {
            $query->filterCategory($filter['categories'], isset($filter['deep']));
        }

        if (isset($filter['tags']) && $filter['tags']) {
            $query->filterTags($filter['tags']);
        }

        if (isset($filter['content']) && $filter['content']) {
            $o = TikiLib::lib('tiki')->get_preference('unified_default_content', ['contents'], true);
            if (count($o) == 1 && empty($o[0])) {
                // Use "contents" field by default, if no default is specified
                $query->filterContent($filter['content'], ['contents']);
            } else {
                $query->filterContent($filter['content'], $o);
            }
        }

        if (isset($filter['autocomplete']) && $filter['autocomplete']) {
            $query->filterInitial($filter['autocomplete']);
        }

        if (isset($filter['language']) && $filter['language']) {
            $q = $filter['language'];
            if (preg_match('/^\w+\-\w+$/', $q)) {
                $q = "\"$q\"";
            }

            if (isset($filter['language_unspecified'])) {
                $q = "($q) or unknown";
            }

            $query->filterLanguage($q);
        }

        if (isset($filter['groups'])) {
            $query->filterMultivalue($filter['groups'], 'groups');
        }

        if (isset($filter['prefix']) && is_array($filter['prefix'])) {
            foreach ($filter['prefix'] as $field => $prefix) {
                $query->filterInitial((string) $prefix, $field);
            }

            unset($filter['prefix']);
        }

        if (isset($filter['not_prefix']) && is_array($filter['not_prefix'])) {
            foreach ($filter['not_prefix'] as $field => $prefix) {
                $query->filterNotInitial((string) $prefix, $field);
            }

            unset($filter['not_prefix']);
        }

        if (
            isset($filter['distance']) && is_array($filter['distance']) &&
                    isset($filter['distance']['distance'], $filter['distance']['lat'], $filter['distance']['lon'])
        ) {
            $query->filterDistance($filter['distance']['distance'], $filter['distance']['lat'], $filter['distance']['lon']);

            unset($filter['distance']);
        }

        if (isset($filter['range']) && is_array($filter['range']) && isset($filter['range']['from'], $filter['range']['to'])) {
            $field = isset($filter['range']['field']) ? $filter['range']['field'] : 'date';
            $query->filterRange($filter['range']['from'], $filter['range']['to'], $field);

            unset($filter['range']);
        }

        unset($filter['type']);
        unset($filter['categories']);
        unset($filter['deep']);
        unset($filter['tags']);
        unset($filter['content']);
        unset($filter['language']);
        unset($filter['language_unspecified']);
        unset($filter['autocomplete']);
        unset($filter['groups']);

        foreach ($filter as $key => $value) {
            if ($value) {
                $query->filterContent($value, $key);
            }
        }
        return $query;
    }

    public function getFacetProvider()
    {
        global $prefs;
        $types = $this->getSupportedTypes();

        $facets = [
            Search_Query_Facet_Term::fromField('object_type')
                ->setLabel(tr('Object Type'))
                ->setRenderMap($types),
        ];

        if ($prefs['feature_multilingual'] == 'y') {
            $facets[] = Search_Query_Facet_Term::fromField('language')
                ->setLabel(tr('Language'))
                ->setRenderMap(TikiLib::lib('language')->get_language_map());
        }

        if ($prefs['search_date_facets'] == 'y') {
            $facets[] = Search_Query_Facet_DateHistogram::fromField('date')
                ->setName(tr('date_histogram'))
                ->setLabel(tr('Date Histogram'))
                ->setInterval($prefs['search_date_facets_interval'])
                ->setRenderCallback(function ($date) {
                    $out = TikiLib::lib('tiki')->get_short_date($date / 1000);
                    return $out;
                });

            if ($prefs['search_date_facets_ranges']) {
                $facet = Search_Query_Facet_DateRange::fromField('date')
                    ->setName(tr('date_range'))
                    ->setLabel(tr('Date Range'))
                    ->setRenderCallback(function ($label) {
                        return $label;
                    });

                $ranges = explode("\n", $prefs['search_date_facets_ranges']);
                foreach (array_filter($ranges) as & $range) {
                    $range = explode(',', $range);
                    if (count($range) > 2) {
                        $facet->addRange($range[1], $range[0], $range[2]);
                    } elseif (count($range) > 1) {
                        $facet->addRange($range[1], $range[0]);
                    }
                }


                $facets[] = $facet;
            }
        }

        if ($prefs['federated_enabled'] === 'y') {
            $tiki_extwiki = TikiDb::get()->table('tiki_extwiki');

            $indexMap = [
                $this->getIndexLocation() => tr('Local Search'),
            ];

            foreach (TikiLib::lib('federatedsearch')->getIndices() as $indexname => $index) {
                if (strstr($indexname, ':')) {
                    $parts = explode(':', $indexname);
                    $prefix = array_pop($parts);
                } else {
                    $prefix = $indexname;
                }
                $indexMap[$prefix] = $tiki_extwiki->fetchOne('name', [
                    'indexname' => $indexname,
                ]);
            }

            $facets[] = Search_Query_Facet_Term::fromField($prefs['unified_engine'] == 'manticore' ? 'index_name' : '_index')
                ->setLabel(tr('Federated Search'))
                ->setRenderCallback(function ($index) use (&$indexMap) {
                    $out = tr('Index not found');
                    if (isset($indexMap[$index])) {
                        $out = $indexMap[$index];
                    } else {
                        foreach ($indexMap as $candidate => $name) {
                            if (0 === strpos($index, $candidate . '_')) {
                                $indexMap[$index] = $name;
                                $out = $name;
                                break;
                            }
                        }
                    }
                    return $out;
                });
        }

        $provider = new Search_FacetProvider();
        $provider->addFacets($facets);
        $this->addSources($provider);

        return $provider;
    }

    public function getRawArray($document)
    {
        return array_map(function ($entry) {
            if (is_object($entry)) {
                if (method_exists($entry, 'getRawValue')) {
                    return $entry->getRawValue();
                } else {
                    return $entry->getValue();
                }
            } else {
                return $entry;
            }
        }, $document);
    }

    public function isOutdated()
    {

        global $prefs;

        // If incremental update is enabled we cannot rely on the unified_last_rebuild date.
        if ($prefs['feature_search'] == 'n' || $prefs['unified_incremental_update'] == 'y') {
            return false;
        }

        $tikilib = TikiLib::lib('tiki');

        $last_rebuild = $tikilib->get_preference('unified_last_rebuild');
        $threshold = strtotime('+ ' . $prefs['search_index_outdated'] . ' days', $last_rebuild);

        $types = $this->getSupportedTypes();

        // Content Sources
        if (isset($types['wiki page'])) {
            $last_page = $tikilib->list_pages(0, 1, 'lastModif_desc', '', '', true, false, false, false);
            if (! empty($last_page['data'][0]['lastModif']) && $last_page['data'][0]['lastModif'] > $threshold) {
                return true;
            }
        }

        if (isset($types['forum post'])) {
            $commentslib = TikiLib::lib('comments');

            $last_forum_post = $commentslib->get_all_comments('forum', 0, -1, 'commentDate_desc');
            if (! empty($last_forum_post['data'][0]['commentDate']) && $last_forum_post['data'][0]['commentDate'] > $threshold) {
                return true;
            }

            $last_forum = $commentslib->list_forums(0, 1, 'created_desc');
            if (! empty($last_forum['data'][0]['created']) && $last_forum['data'][0]['created'] > $threshold) {
                return true;
            }
        }

        if (isset($types['blog post'])) {
            $last_blog_post = TikiLib::lib('blog')->list_blog_posts(0, false, 0, 1, 'lastModif_desc');
            if (! empty($last_blog_post['data'][0]['lastModif']) && $last_blog_post['data'][0]['lastModif'] > $threshold) {
                return true;
            }
        }

        if (isset($types['article'])) {
            $last_article = TikiLib::lib('art')->list_articles(0, 1, 'lastModif_desc');
            if (! empty($last_article['data'][0]['lastModif']) && $last_article['data'][0]['lastModif'] > $threshold) {
                return true;
            }
        }

        if (isset($types['file'])) {
            // todo: files are indexed automatically, probably nothing to do here.
        }

        if (isset($types['trackeritem'])) {
            $trackerlib = TikiLib::lib('trk');

            $last_tracker_item = $trackerlib->list_tracker_items(-1, 0, 1, 'lastModif_desc', null);
            if (! empty($last_tracker_item['data'][0]['lastModif']) && $last_tracker_item['data'][0]['lastModif'] > $threshold) {
                return true;
            }

            $last_tracker = $trackerlib->list_trackers(0, 1, 'lastModif_desc');
            if (! empty($last_tracker['data'][0]['lastModif']) && $last_tracker['data'][0]['lastModif'] > $threshold) {
                return true;
            }

            // todo: Missing tracker_fields
        }

        if (isset($types['sheet'])) {
            $sheetlib = TikiLib::lib('sheet');

            $last_sheet = $sheetlib->list_sheets(0, 1, 'begin_desc');
            if (! empty($last_sheet['data'][0]['begin']) && $last_sheet['data'][0]['begin'] > $threshold) {
                return true;
            }
        }

        if (isset($types['comment'])) {
            $commentTypes = [];
            if ($prefs['feature_wiki_comments'] == 'y') {
                $commentTypes[] = 'wiki page';
            }
            if ($prefs['feature_article_comments'] == 'y') {
                $commentTypes[] = 'article';
            }
            if ($prefs['feature_poll_comments'] == 'y') {
                $commentTypes[] = 'poll';
            }
            if ($prefs['feature_file_galleries_comments'] == 'y') {
                $commentTypes[] = 'file gallery';
            }
            if ($prefs['feature_trackers'] == 'y') {
                $commentTypes[] = 'trackeritem';
            }

            $commentslib = TikiLib::lib('comments');

            $last_comment = $commentslib->get_all_comments($commentTypes, 0, 1, 'commentDate_desc');
            if (! empty($last_comment['data'][0]['commentDate']) && $last_comment['data'][0]['commentDate'] > $threshold) {
                return true;
            }
        }

        if (isset($types['user'])) {
            $userlib = TikiLib::lib('user');

            $last_user = $userlib->get_users(0, 1, 'created_desc');
            if (! empty($last_user['data'][0]['created']) && $last_user['data'][0]['created'] > $threshold) {
                return true;
            }
        }

        if (isset($types['group'])) {
            // todo: unable to track groups by dates
        }
    }

    /**
     * Provide the name of the log file
     *
     * @param int $rebuildType    0: no log, 1: browser rebuild, 2: console rebuild
     * @return string
     */
    public function getLogFilename($rebuildType = 0, $engine = ''): string
    {
        global $prefs;

        $logName = 'Search_Indexer';

        if (empty($engine)) {
            $engine = $prefs['unified_engine'];
        }

        switch ($engine) {
            case 'elastic':
                $logName .= '_elastic_' . rtrim($prefs['unified_elastic_index_prefix'], '_');
                break;
            case 'mysql':
                $logName .= '_mysql_' . TikiDb::get()->getOne('SELECT DATABASE()');
                break;
            case 'manticore':
                $logName .= '_manticore_' . rtrim($prefs['unified_manticore_index_prefix'], '_');
                break;
        }
        if ($rebuildType == 2) {
            $logName .= '_console';
        }
        $logName = $prefs['tmpDir'] . (substr($prefs['tmpDir'], -1) === '/' ? '' : '/') . $logName . '.log';
        return $logName;
    }

    /**
     * Return the fallback search engine name
     *
     * @return array|null
     */
    public function getFallbackEngineDetails()
    {
        global $prefs, $tikilib;

        if ($prefs['unified_engine'] == 'elastic' && $prefs['unified_elastic_mysql_search_fallback'] === 'y') {
            $engine = 'mysql';
            $engineName = 'MySQL';
            $version = $tikilib->getMySQLVersion();
            $index = $prefs['unified_mysql_index_current'];

            return [$engine, $engineName, $version, $index];
        }

        return null;
    }

    /**
     * Get Indexes to restore depending on the Tiki's configuration
     *
     * @return array
     */
    private function getIndexesToRestore()
    {
        global $prefs;

        $currentIndex = $prefs['unified_mysql_index_current'];
        if (
            $prefs['unified_mysql_restore_indexes'] == 'n'
            || empty($currentIndex)
        ) {
            return [];
        }

        // The excluded indexes are hardcoded on the index table creation query
        $indexesToRestore = TikiDb::get()->fetchAll(
            "SHOW INDEXES
            FROM $currentIndex
            WHERE Key_name != 'PRIMARY'
            AND (Key_name != 'object_type' AND Column_name != 'object_type')
            AND (Key_name != 'object_type' AND Column_name != 'object_id')"
        );

        return $indexesToRestore;
    }

    /**
     * Check Elasticsearch service
     *
     * @return array
     */
    public function checkElasticsearch()
    {
        global $prefs;
        $searchIndex = [
            'error'           => false,
            'feedback'        => '',
            'connectionError' => false,
        ];

        if ($prefs['unified_engine'] !== 'elastic') {
            return $searchIndex;
        }

        $connection = $this->getElasticConnection(false);
        $connectionStatus = $connection->getStatus();
        if ($connectionStatus->status !== 200) {
            $searchIndex = [
                'error'           => true,
                'feedback'        => $connectionStatus->error ?? '',
                'connectionError' => true,
            ];
        }

        return $searchIndex;
    }

    /**
     * Check MySQL index
     *
     * @return array
     */
    public function checkMySql()
    {
        global $prefs, $dbs_tiki;

        $searchIndex = [
            'error'    => false,
            'feedback' => '',
        ];

        if ($prefs['unified_engine'] !== 'mysql') {
            return $searchIndex;
        }

        $local_php = TikiInit::getCredentialsFile();
        if (file_exists($local_php)) {
            include($local_php);
        }

        $result = TikiDb::get()->fetchMap(
            'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME LIKE ?;',
            [$dbs_tiki, 'index_%']
        );

        if (! array_key_exists($prefs['unified_mysql_index_current'], $result)) {
            $feedback = tra('MySql Search index table not found.');
            $searchIndex = [
                'error'    => true,
                'feedback' => $feedback,
            ];
        }

        return $searchIndex;
    }

    /**
     * Check Manticore service
     *
     * @return array
     */
    public function checkManticore()
    {
        global $prefs;
        $searchIndex = [
            'error'           => false,
            'feedback'        => '',
            'connectionError' => false,
        ];

        if ($prefs['unified_engine'] !== 'manticore') {
            return $searchIndex;
        }

        $client = $this->getManticoreClient();
        $connectionStatus = $client->getStatus();
        if (! isset($connectionStatus['version'])) {
            $searchIndex = [
                'error'           => true,
                'feedback'        => $connectionStatus->error ?? '',
                'connectionError' => true,
            ];
        }

        return $searchIndex;
    }

    /**
     * Format indexing stats for output and use a writer function to write the output
     * @param closure function to write the log entry
     */
    public function formatStats(array $stats, closure $logit)
    {
        global $prefs;

        $logit("Indexed");
        foreach ($stats['default']['counts'] as $key => $val) {
            $logit("  $key: $val");
        }
        $logit("  total tiki fields indexed: {$stats['default']['total tiki fields indexed']}");
        $key = 'total fields used in the ' . $prefs['unified_engine'] . ' search index: ';
        if (isset($stats['default'][$key])) {
            $logit("  $key{$stats['default'][$key]}");
        }
        if (isset($stats['default']['fulltext fields'])) {
            $logit("Fulltext fields");
            foreach ($stats['default']['fulltext fields'] as $key => $val) {
                if (is_array($val)) {
                    $logit("  $key: " . implode(', ', $val));
                } else {
                    $logit("  $key: $val");
                }
            }
        }
    }

    public function listAllUnusedIndexes($currentEngine)
    {
        global $prefs;

        $indices = [];
        list($engine, $version, $currentIndex) = $currentEngine;

        switch ($engine) {
            case 'Elastic':
                try {
                    $indexPrefix = $prefs['unified_elastic_index_prefix'];
                    $connUrl = $prefs['unified_elastic_url'];
                    $manager = new ElasticSearchIndexManager($currentIndex, $indexPrefix, $connUrl);
                    $indices['indices'] = $manager->getUnusedIndexes();
                } catch (\Exception $e) {
                    $indices['error'] = "An error occurred while getting indices for Elasticsearch: " . $e->getMessage();
                }
                break;
            case 'MySQL':
                try {
                    $mysqlManager = new MysqlSearchIndexManager($currentIndex);
                    $indices['indices'] = $mysqlManager->getUnusedIndexes();
                } catch (\Exception $e) {
                    $indices['error'] = "An error occurred while getting indices for MYSQL: " . $e->getMessage();
                }
                break;
            case 'Manticore':
                try {
                    $indexPrefix = $prefs['unified_manticore_index_prefix'] . 'main';
                    $dsn = $prefs['unified_manticore_url'];
                    $pdoPort = $prefs['unified_manticore_mysql_port'] ?: 9306;
                    $manticoreManager = new ManticoreSearchIndexManager($currentIndex, $indexPrefix, $dsn, $pdoPort);
                    $indices['indices'] = $manticoreManager->getUnusedIndexes();
                } catch (\Exception $e) {
                    $indices['error'] = "An error occurred while getting indices for Manticore: " . $e->getMessage();
                }
                break;
            default:
                break;
        }

        return $indices;
    }
}
