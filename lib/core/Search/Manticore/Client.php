<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use \Manticoresearch\Exceptions\ExceptionInterface as ManticoreException;

class Search_Manticore_Client
{
    protected $dsn;
    protected $client;

    public function __construct($dsn)
    {
        $this->dsn = rtrim($dsn, '/');

        $parsed = parse_url($this->dsn);
        if ($parsed === false) {
            throw new Search_Manticore_Exception(tr("Malformed Manticore connection url: %0", $this->dsn));
        }

        $config = [
            'transport' => $parsed['scheme'] == 'https' ? 'Https' : 'Http',
            'scheme' => $parsed['scheme'],
            'host' => $parsed['host'],
            'port' => $parsed['port']
        ];
        try {
            $this->client = new \Manticoresearch\Client($config, new Tiki_Log('Manticore', \Psr\Log\LogLevel::WARNING));
        } catch (ManticoreException $e) {
            throw new Search_Manticore_ClientException($e);
        }
    }

    public function getStatus()
    {
        try {
            $result = $this->client->sql([
                'query' => 'SHOW STATUS'
            ]);

            if ($result) {
                $result['status'] = 200;
            } else {
                $result = ['status' => 0];
            }

            return $result;
        } catch (ManticoreException $e) {
            return [
                'status' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getIndexStatus($index = '')
    {
        try {
            $index = $this->client->index($index);
            return $index->status();
        } catch (ManticoreException $e) {
            throw new Search_Manticore_Exception($e);
        }
    }

    public function createIndex($index, $definition, $settings = [], $silent = false)
    {
        try {
            $index = $this->client->index($index);
            $index->create($definition, $settings, $silent);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_Exception($e);
        }
    }

    public function deleteIndex($index)
    {
        try {
            $index = $this->client->index($index);
            return $index->drop();
        } catch (ManticoreException $e) {
            throw new Search_Manticore_Exception($e);
        }
    }

    public function describe($index)
    {
        try {
            $index = $this->client->index($index);
            $result = $index->describe();
            if (empty($result['error']) && !empty($result['data'])) {
                $mapping = [];
                foreach ($result['data'] as $row) {
                    $mapping[$row['Field']] = [
                        'type' => $row['Type'],
                        'options' => explode(' ', $row['Properties'])
                    ];
                }
                return $mapping;
            } else {
                return [];
            }
        } catch (ManticoreException $e) {
            throw new Search_Manticore_Exception($e);
        }
    }

    public function alter($operation, $field, $type)
    {
        try {
            $index = $this->client->index($index);
            return $index->alter($operation, $field, $type);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_Exception($e);
        }
    }

    public function search($index, $query)
    {
        try {
            $index = $this->client->index($index);
            return $index->search($query);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_Exception($e);
        }
    }

    public function storeQuery($index, $query)
    {
        try {
            return $this->client->pq()->doc([
                'index' => $index,
                'body' => $query
            ]);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_Exception($e);
        }
    }

    public function unstoreQuery($index, $query)
    {
        try {
            return $this->client->pq()->deleteByQuery([
                'index' => $index,
                'body' => $query
            ]);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_Exception($e);
        }
    }

    public function percolate($index, $document)
    {
        try {
            return $this->client->pq()->search([
                'index' => $index,
                'body' => [
                    'query' => [
                        'percolate' => [
                            'document' => $document
                        ],
                    ],
                ],
            ]);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_Exception($e);
        }
    }

    public function index($index, array $data)
    {
        try {
            $index = $this->client->index($index);
            return $index->addDocument($data);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_Exception($e);
        }
    }

    public function unindex($index, $type, $id)
    {
        try {
            $index = $this->client->index($index);
            return $index->deleteDocuments([
                'object_type' => $type,
                'object_id' => $id,
            ]);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_Exception($e);
        }
    }

    public function document($index, $type, $id)
    {
        try {
            $params = [
                'body' => [
                    'index' => $this->index,
                    'query' => [
                        'equals' => [
                            'object_type' => $type,
                            'object_id' => $id,
                        ]
                    ]
                ]
            ];
            $result = new ResultSet($this->client->search($params, true));
            return $result->current();
        } catch (ManticoreException $e) {
            throw new Search_Manticore_Exception($e);
        }
    }

    public function getIndex($index)
    {
        return $this->client->index($index);
    }

    public function getClient()
    {
        return $this->client;
    }
}
