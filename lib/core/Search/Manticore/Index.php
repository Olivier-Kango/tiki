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
    private $providedMappings = [];

    private $facetCount = 10;

    private $multisearchIndices;
    private $multisearchStack;

    public function __construct(Search_Manticore_Client $client, Search_Manticore_PdoClient $pdo_client, $index)
    {
        $this->client = $client;
        $this->pdo_client = $pdo_client;
        $this->index = $index;
        $this->providedMappings = $this->pdo_client->describe($index);
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
        $fieldMapping = $this->getUnifiedFieldMapping();

        $providedMappingsCorrectName = array_flip(array_map(function($field) use ($fieldMapping) {
            return $fieldMapping[$field] ?? $field;
        }, array_keys($this->providedMappings)));

        $mapping = array_map(
            function ($entry) {
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
                } elseif ($entry instanceof Search_Type_JsonEncoded) {
                    return [
                        "type" => "json",
                    ];
                } elseif ($entry instanceof Search_Type_GeoPoint) {
                    return [
                        "type" => "string",
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
            },
            array_diff_key($data, $providedMappingsCorrectName)
        );

        if (empty($this->providedMappings)) {
            $this->pdo_client->createIndex($this->index, $mapping, $this->getIndexSettings());
            $is_update = false;
        } else {
            foreach ($mapping as $field => $type) {
                $this->pdo_client->alter($this->index, 'add', $field, $type['type']);
            }
            $is_update = true;
        }

        foreach ($mapping as $field => $type) {
            $this->providedMappings[$field] = [
                'types' => $type['type'] == 'text' ? ['text', 'string'] : [$type['type']],
                'options' => $type['options'] ?? [],
            ];
            if ($is_update) {
                $fieldMapping[strtolower($field)] = $field;
            }
        }

        if ($is_update && $mapping) {
            TikiLib::lib('tiki')->set_preference('unified_field_mapping', json_encode($fieldMapping));
        }
    }

    private function getIndexSettings()
    {
        global $prefs;

        $stopwords_file = realpath("temp").'/manticore-stopwords';
        file_put_contents($stopwords_file, implode("\n", $prefs['unified_stopwords']));

        $settings = [
            'stopwords' => $stopwords_file,
            'morphology' => $prefs['unified_manticore_morphology'] ?? '',
            // TODO: see what other options to support https://manual.manticoresearch.com/Creating_an_index/Local_indexes/Plain_and_real-time_index_settings#Natural-language-processing-specific-settings
        ];

        return $settings;
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

        $result = $search
            ->offset($resultStart)
            ->limit($resultCount)
            ->get();

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

        $resultSet = new Search_ResultSet($entries, $result->getTotal(), $resultStart, $resultCount);
        // TODO: highlights

        // TODO: facet reader

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
        if (array_key_exists($field, $this->providedMappings)) {
            return $this->providedMappings[$field];
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
        return array_combine(array_map(function($field) {
            return strtolower($field);
        }, $fields), $fields);
    }

    protected function getUnifiedFieldMapping() {
        global $prefs;

        $fieldMapping = $prefs['unified_field_mapping'] ?? '';
        if ($fieldMapping) {
            $fieldMapping = json_decode($fieldMapping, true);
        }

        return $fieldMapping;
    }
}
