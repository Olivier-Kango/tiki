<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Manticore_Index implements Search_Index_Interface, Search_Index_QueryRepository
{
    private $client;
    private $index;
    private $providedMappings = [];

    private $facetCount = 10;

    private $multisearchIndices;
    private $multisearchStack;

    public function __construct(Search_Manticore_Client $client, $index)
    {
        $this->client = $client;
        $this->index = $index;
        $this->providedMappings = $this->client->describe($index);
    }

    public function destroy()
    {
        $this->client->deleteIndex($this->index);
        return true;
    }

    public function exists()
    {
        $indexStatus = $this->client->getIndexStatus($this->index);

        if (empty($indexStatus) || ! empty($indexStatus['error'])) {
            return false;
        }

        return true;
    }

    public function addDocument(array $data)
    {
        $this->client->index($this->index, $this->generateDocument($data));
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
                } else {
                    return [
                        "type" => "text",
                        "options" => ["indexed", "attribute"]
                    ];
                }
            },
            array_diff_key($data, $this->providedMappings)
        );
        if (empty($this->providedMappings)) {
            $this->client->createIndex($this->index, $mapping, $this->getIndexSettings());
            $this->providedMappings = $mapping;
        } else {
            foreach($mapping as $field => $type) {
                $this->client->alter($this->index,'add', $field, $type['type']);
            }
            $this->providedMappings = array_merge($this->providedMappings, $mapping);
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
        $this->client->optimize($this->index);
    }

    public function endUpdate()
    {
        $this->optimize();
    }

    public function invalidateMultiple(array $objectList)
    {
        foreach ($objectList as $object) {
            $this->client->unindex($this->index, $object['object_type'], $object['object_id']);
        }
    }

    public function initSearch()
    {
        $search = new \Manticoresearch\Search($this->client->getClient());
        $search->setIndex($this->client->getIndex($this->index));
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
        global $prefs;

        $search = $this->initSearch();

        $decorator = new Search_Manticore_QueryDecorator($search, $this);
        $decorator->setDocumentReader($this->createDocumentReader());
        $decorator->decorate($query->getExpr());

        $decorator = new Search_Manticore_OrderDecorator($search, $this);
        $decorator->decorate($query->getSrotOrder());

        $decorator = new Search_Manticore_FacetDecorator($search, $this, $this->facetCount);
        $decorator->decorate($query->getFacets());

        // TODO: multi-query/foreign indexes (federation)
        // $foreign = array_map(function ($query) use ($builder) {
        //     return $builder->build($query->getExpr());
        // }, $query->getForeignQueries());

        // TODO: maxMatches which defaults to 1000
        $result = $search
            ->offset($resultStart)
            ->limit($resultCount)
            ->get();

        $entries = array_map(
            function ($entry) {
                $data = (array) $entry->getData();
                $data['score'] = round($data['_score'], 2);
                return $data;
            },
            $result
        );

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

            $hasMore = $result->count();
        }
    }

    public function getTypeFactory()
    {
        return new Search_Manticore_TypeFactory();
    }

    private function createDocumentReader()
    {
        $client = $this->client;
        $index = $this->index;
        return function ($type, $object) use ($client, $index) {
            static $previous, $content;

            $now = "$index~$type~$object";
            if ($previous === $now) {
                return $content;
            }

            $previous = $now;
            $content = (array) $client->document($index, $type, $object);
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
        $this->connection->unstoreQuery($this->index, $name);
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
        return new stdClass();
    }

    public function getFieldMappings()
    {
        return $this->providedMappings;
    }
}
