<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Manticore_Index implements Search_Index_Interface, Search_Index_QueryRepository
{
    private $client;
    private $pdo_client;
    private $index;
    private $indexer;
    private $providedMappings = [];

    private $facetCount = 10;

    private $multisearchIndices;
    private $multisearchStack;


    public function __construct(Search_Manticore_Client $client, Search_Manticore_PdoClient $pdo_client, $index)
    {
        $this->client = $client;
        $this->pdo_client = $pdo_client;
        $this->index = $index;
        $this->indexer = null;
        $this->providedMappings = $this->pdo_client->describe($index);
    }

    public function setIndexer($indexer)
    {
        $this->indexer = $indexer;
    }

    public function destroy()
    {
        if ($this->pdo_client->deleteIndex($this->index)) {
            $this->providedMappings = [];
            return true;
        }
        return false;
    }

    public function exists()
    {
        $indexStatus = $this->pdo_client->getIndexStatus($this->index);

        if (empty($indexStatus) || ! empty($indexStatus['error'])) {
            return false;
        }

        return true;
    }

    public function addDocument(array $data)
    {
        $this->pdo_client->index($this->index, $this->generateDocument($data));
    }

    private function generateDocument(array $data)
    {
        $objectType = $data['object_type']->getValue();

        $this->generateMapping($objectType, $data);

        $data = array_map(
            function ($entry) {
                return $entry->getValue() ?? "";
            },
            $data
        );

        return $data;
    }

    private function generateMapping($type, $data)
    {
        // stored conversion of manticore field names to tiki field names
        $fieldMapping = $this->getUnifiedFieldMapping();

        // correct mapping back to tiki field names (possibly come from manticore server desc statement)
        $providedMappingsCorrectName = array_flip(array_map(function ($field) use ($fieldMapping) {
            return $fieldMapping[$field] ?? $field;
        }, array_keys($this->providedMappings)));

        // extract the difference of the new data only and convert to manticore types
        $mapping = array_map(
            array($this, 'convertToManticoreType'),
            array_diff_key($data, $providedMappingsCorrectName)
        );

        // observe 256 full-text fields index limit - convert the rest to string attributes
        $indexedFields = $this->getIndexedFields();
        foreach ($mapping as $field => $type) {
            if ($type['type'] == 'text' && ! in_array($field, $indexedFields)) {
                $mapping[$field] = ['type' => 'string'];
            }
        }

        // create or update the index
        if (empty($this->providedMappings)) {
            $this->pdo_client->createIndex($this->index, $mapping, $this->getIndexSettings());
        } else {
            foreach ($mapping as $field => $type) {
                $this->pdo_client->alter($this->index, 'add', $field, $type);
            }
        }

        // save provided mappings and the field names conversion
        foreach ($mapping as $field => $type) {
            $this->providedMappings[$field] = [
                'types' => $type['type'] == 'text' ? ['text', 'string'] : [$type['type']],
                'options' => $type['options'] ?? [],
            ];
            $fieldMapping[strtolower($field)] = $field;
        }

        TikiLib::lib('tiki')->set_preference('unified_field_mapping', json_encode($fieldMapping));
    }

    private function getIndexSettings()
    {
        global $prefs;

        $stopwords_file = sys_get_temp_dir().DIRECTORY_SEPARATOR.'manticore-stopwords-'.$this->index;
        file_put_contents($stopwords_file, implode("\n", $prefs['unified_stopwords']));

        $settings = [
            'min_infix_len' => 2,
            'stopwords' => $stopwords_file,
            'morphology' => $prefs['unified_manticore_morphology'] ?? '',
            // TODO: see what other options to support https://manual.manticoresearch.com/Creating_an_index/Local_indexes/Plain_and_real-time_index_settings#Natural-language-processing-specific-settings
        ];

        return $settings;
    }

    private function getIndexedFields()
    {
        global $prefs;

        static $data = null;

        if (! is_null($data)) {
            return $data;
        }

        if (! empty($prefs['unified_manticore_always_index'])) {
            $data = preg_split('/\s*,\s*/', $prefs['unified_manticore_always_index']);
        } else {
            $data = [];
        }

        if (! empty($prefs['unified_default_content'])) {
            $data = array_merge($data, $prefs['unified_default_content']);
            $data = array_unique($data);
        }

        if (count($data) > 256) {
            $data = null;
            throw new Search_Manticore_FatalException(tr('Fatal Manticore error: you have defined more than 256 fields to be indexed as full-text. Please edit search setting "unified_manticore_always_index" to decrease the number of fields.'));
        }

        if (! $this->indexer) {
            return $data;
        }

        $typeFactory = $this->getTypeFactory();

        $fields = $this->indexer->getAvailableFieldTypes();
        foreach ($fields as $name => $type) {
            if (empty($type)) {
                continue;
            }
            $searchType = $typeFactory->$type('');
            $field = $this->convertToManticoreType($searchType);
            if ($field['type'] == 'text' && count($data) < 256 && ! in_array($name, $data)) {
                $data[] = $name;
            }
        }

        return $data;
    }

    private function convertToManticoreType($entry)
    {
        if ($entry instanceof Search_Type_Numeric) {
            return [
                "type" => "float",
            ];
        } elseif ($entry instanceof Search_Type_Whole) {
            return [
                "type" => "string",
            ];
        } elseif ($entry instanceof Search_Type_MultivalueJson) {
            return [
                "type" => "json",
            ];
        } elseif ($entry instanceof Search_Type_MultivalueInt) {
            return [
                "type" => "multi",
            ];
        } elseif ($entry instanceof Search_Type_JsonEncoded) {
            return [
                "type" => "json",
            ];
        } elseif ($entry instanceof Search_Type_DateTime) {
            return [
                "type" => "timestamp",
            ];
        } elseif ($entry instanceof Search_Type_Timestamp) {
            return [
                "type" => "timestamp",
            ];
        } else {
            return [
                "type" => "text",
                "options" => ["indexed", "attribute"]
            ];
        }
    }

    public function optimize()
    {
        $this->pdo_client->optimize($this->index);
    }

    public function endUpdate()
    {
        $this->optimize();
    }

    public function invalidateMultiple(array $objectList)
    {
        foreach ($objectList as $object) {
            $this->pdo_client->unindex($this->index, $object['object_type'], $object['object_id']);
        }
    }

    public function initSearch()
    {
        $search = new \Manticoresearch\Search($this->client->getClient());
        $search->setIndex($this->index);
        return $search;
    }

    /**
     * @param Search_Query $query
     * @param int $resultStart
     * @param int $resultCount
     * @return Search_Manticore_ResultSet
     */
    public function find(Search_Query_Interface $query, $resultStart, $resultCount)
    {
        $search = $this->initSearch();

        $decorator = new Search_Manticore_QueryDecorator($search, $this);
        $decorator->setDocumentReader($this->createDocumentReader());
        $decorator->decorate($query->getExpr());

        $decorator = new Search_Manticore_OrderDecorator($search, $this);
        $decorator->decorate($query->getSortOrder());

        $decorator = new Search_Manticore_FacetDecorator($search, $this, $this->facetCount);
        $decorator->decorate($query->getFacets());

        // TODO: multi-query/foreign indexes (federation)
        // $foreign = array_map(function ($query) use ($builder) {
        //     return $builder->build($query->getExpr());
        // }, $query->getForeignQueries());

        $search->offset($resultStart)
            ->limit($resultCount)
            ->highlight(['contents'], ['pre_tags' => '<em>', 'post_tags' => '</em>'])
            ->option('cutoff', 0);

        if ($resultStart + $resultCount > 1000) {
            $search->maxMatches($resultStart + $resultCount);
        }

        $result = $search->get();

        $fieldMapping = $this->getUnifiedFieldMapping();

        $timestampFields = [];
        foreach ($this->providedMappings as $field => $mapping) {
            if (in_array('timestamp', $mapping['types'])) {
                $timestampFields[] = $field;
            }
        }

        $entries = [];
        foreach ($result as $entry) {
            $data = (array) $entry->getData();

            if (isset($data['_score'])) {
                $data['score'] = round($data['_score'], 2);
            }

            $highlight = $entry->getHighlight();
            if (! empty($highlight['contents'])) {
                $data['_highlight'] = implode('...', $highlight['contents']);
            } else {
                $data['_highlight'] = '';
            }

            // Manticore stores datetimes as timestamp values while MySQL/ES store as datetime strings
            // Tiki interface expects datetime strings in GMT, so we need a conversion before using the result
            foreach ($timestampFields as $tsField) {
                if (! empty($data[$tsField])) {
                    $dt = new Search_Type_DateTime($data[$tsField]);
                    $data[$tsField] = $dt->getValue();
                    $data['ignored_fields'][] = $fieldMapping[$tsField] ?? $tsField;
                }
            }

            // convert lowercase stored field names back to Tiki equivalents
            if ($fieldMapping) {
                $mapped = [];
                foreach ($data as $key => $value) {
                    $key = $fieldMapping[$key] ?? $key;
                    $mapped[$key] = $value;
                }
                $data = $mapped;
                unset($mapped);
            }

            $entries[] = $data;
        }

        $resultSet = new Search_Manticore_ResultSet($entries, $result->getTotal(), $resultStart, $resultCount);

        $words = $this->getWords($query->getExpr());
        $resultSet->setHighlightHelper(new Search_MySql_HighlightHelper($words));

        $reader = new Search_Manticore_FacetReader($result);
        foreach ($query->getFacets() as $facet) {
            if ($filter = $reader->getFacetFilter($facet)) {
                $resultSet->addFacetFilter($filter);
            }
        }

        return $resultSet;
    }

    public function scroll(Search_Query_Interface $query)
    {
        $perPage = 100;
        $hasMore = true;

        for ($from = 0; $hasMore; $from += $perPage) {
            $result = $this->find($query, $from, $perPage);
            foreach ($result as $row) {
                yield $row;
            }

            $hasMore = $result->hasMore();
        }
    }

    public function getTypeFactory()
    {
        return new Search_Manticore_TypeFactory();
    }

    private function createDocumentReader()
    {
        $pdo_client = $this->pdo_client;
        $index = $this->index;
        return function ($type, $object) use ($pdo_client, $index) {
            static $previous, $content;

            $now = "$index~$type~$object";
            if ($previous === $now) {
                return $content;
            }

            $previous = $now;
            $content = (array) $pdo_client->document($index, $type, $object);
            return $content;
        };
    }

    public function getMatchingQueries(array $document)
    {
        $document = $this->generateDocument($document);
        return $this->client->percolate($this->index, $document);
    }

    public function store($name, Search_Expr_Interface $expr)
    {
        $search = $this->initSearch();
        $decorator = new Search_Manticore_QueryDecorator($search, $this);
        $decorator->setDocumentReader($this->createDocumentReader());
        $decorator->decorate($expr);

        $doc = $search->compile();
        $this->client->storeQuery($this->index, $doc, $name);
    }

    public function unstore($name)
    {
        $this->client->unstoreQuery($this->index, $name);
    }

    public function setFacetCount($count)
    {
        $this->facetCount = (int) $count;
    }

    public function getFieldMapping($field)
    {
        if (array_key_exists(strtolower($field), $this->providedMappings)) {
            return $this->providedMappings[strtolower($field)];
        }
        return [];
    }

    public function getFieldMappings()
    {
        return $this->providedMappings;
    }

    /**
     * Manticore/sphinx stores attribute names in lower case, so we need a mapping when building the result set from searches
     * @return array of key/value pairs with mapping between Manticore and Tiki fields
     */
    public function getFieldNameMapping()
    {
        $fields = array_keys($this->providedMappings);
        return array_combine(array_map(function ($field) {
            return strtolower($field);
        }, $fields), $fields);
    }

    protected function getUnifiedFieldMapping()
    {
        global $prefs;

        $fieldMapping = $prefs['unified_field_mapping'] ?? [];
        if ($fieldMapping) {
            $fieldMapping = json_decode($fieldMapping, true);
        }

        return $fieldMapping;
    }

    protected function getWords($expr)
    {
        $words = [];
        $factory = new Search_Type_Factory_Direct();
        $expr->walk(
            function ($node) use (&$words, $factory) {
                if ($node instanceof Search_Expr_Token && $node->getField() !== 'searchable') {
                    $word = $node->getValue($factory)->getValue();
                    if (is_string($word) && ! in_array($word, $words)) {
                        $words[] = $word;
                    }
                }
            }
        );

        return $words;
    }
}
