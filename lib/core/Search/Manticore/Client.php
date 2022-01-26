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
            $status = ['status' => 0];
            $result = $this->client->sql([
                'mode' => 'raw',
                'body' => [
                    'query' => 'SHOW STATUS',
                ],
            ]);
            if (! empty($result['data'])) {
                foreach ($result['data'] as $row) {
                    $status[$row['Counter']] = $row['Value'];
                }
            }
            return $status;
        } catch (ManticoreException $e) {
            return [
                'status' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getVersion()
    {
        $status = $this->getStatus();
        return $status['version'] ?? 0;
    }

    public function getIndexStatus($index = '')
    {
        try {
            $index = $this->client->index($index);
            return $index->status();
        } catch (ManticoreException $e) {
            throw new Search_Manticore_ClientException($e);
        }
    }

    public function createIndex($index, $definition, $settings = [], $silent = false)
    {
        try {
            $index = $this->client->index($index);
            $response = $index->create($definition, $settings, $silent);
            if (! empty($response['error'])) {
                throw new Search_Manticore_Exception($response['error']);
            }
        } catch (ManticoreException $e) {
            throw new Search_Manticore_ClientException($e);
        }
    }

    public function deleteIndex($index)
    {
        try {
            $index = $this->client->index($index);
            return $index->drop(true);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_ClientException($e);
        }
    }

    public function describe($index)
    {
        try {
            $index = $this->client->index($index);
            $result = $index->describe();
        } catch (ManticoreException $e) {
            $result = [
                'error' => $e->getMessage()
            ];
        }
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
    }

    public function alter($index, $operation, $field, $type)
    {
        try {
            $index = $this->client->index($index);
            return $index->alter($operation, $field, $type);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_ClientException($e);
        }
    }

    public function search($index, $query)
    {
        try {
            $index = $this->client->index($index);
            return $index->search($query);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_ClientException($e);
        }
    }

    public function storeQuery($index, $query, $name)
    {
        try {
            return $this->client->pq()->doc([
                'index' => $index,
                'body' => [
                    'query' => $query,
                    'tags' => [$name],
                ]
            ]);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_ClientException($e);
        }
    }

    public function unstoreQuery($index, $name)
    {
        try {
            return $this->client->pq()->deleteByQuery([
                'index' => $index,
                'body' => [
                    'tags' => [$name],
                ]
            ]);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_ClientException($e);
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
            throw new Search_Manticore_ClientException($e);
        }
    }

    public function index($index, array $data)
    {
        try {
            $index = $this->client->index($index);
            return $index->addDocument($data);
        } catch (ManticoreException $e) {
            throw new Search_Manticore_ClientException($e);
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
            throw new Search_Manticore_ClientException($e);
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
            throw new Search_Manticore_ClientException($e);
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
