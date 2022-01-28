<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Search_Manticore_PdoClient
{
    protected $pdo;

    public function __construct($dsn)
    {
        $dsn = rtrim($dsn, '/');
        $parsed = parse_url($dsn);
        if ($parsed === false) {
            throw new Search_Manticore_Exception(tr("Malformed Manticore connection url: %0", $this->dsn));
        }

        $dsn = "mysql:host=".$parsed['host'].";port=".$parsed['port'];

        $this->pdo = new PDO($dsn);
    }

    public function getStatus()
    {
        $status = ['status' => 0];
        $result = $this->pdo->query('SHOW STATUS');
        $result = $result->fetch();
        if (! empty($result['data'])) {
            foreach ($result['data'] as $row) {
                $status[$row['Counter']] = $row['Value'];
            }
        }
        return $status;
    }

    public function getVersion()
    {
        $status = $this->getStatus();
        return $status['version'] ?? 0;
    }

    public function getIndexStatus($index = '')
    {
        $stmt = $this->pdo->prepare("SHOW INDEX $index STATUS");
        return $stmt->execute();
    }

    public function createIndex($index, $definition, $settings = [], $silent = false)
    {
        $cols = [];
        foreach ($definition as $field => $opts) {
            $def = $field.' '.$opts['type'];
            if (! empty($opts['options'])) {
                $def .= ' '.implode(' ', $opts['options']);
            }
            $cols[] = $def;
        }
        $sql = "CREATE TABLE $index (".implode(',', $cols).")";
        foreach ($settings as $key => $val) {
            $sql .= ' '.$key.'='."'".$val."'";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
    }

    public function deleteIndex($index)
    {
        $stmt = $this->pdo->prepare("DROP TABLE $index");
        $stmt->execute();
    }

    public function describe($index)
    {
        $stmt = $this->pdo->prepare("DESC $index");
        $result = $stmt->execute();
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
        if ($operation == 'drop') {
            $sql = "ALTER TABLE $index DROP COLUMN $field";
        } else {
            $sql = "ALTER TABLE $index ADD COLUMN $field $type";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([]);
    }

    public function search($index, $query)
    {
        // TODO
        return [];
    }

    public function storeQuery($index, $query, $name)
    {
        // TODO
    }

    public function unstoreQuery($index, $name)
    {
        // TODO
    }

    public function percolate($index, $document)
    {
        // TODO
    }

    public function index($index, array $data)
    {
        $stmt = $this->pdo->prepare("INSERT INTO $index (".implode(', ', array_keys($data)).') VALUES ('.implode(',', array_fill(0, count($data), '?')).')');
        $stmt->execute(array_values($data));
    }

    public function unindex($index, $type, $id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM $index WHERE object_type = :object_type AND object_id = :object_id");
        $stmt->execute(['object_type' => $type, 'object_id' => $id]);
    }

    public function document($index, $type, $id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM $index WHERE object_type = :object_type AND object_id = :object_id");;
        $result = $stmt->execute(['object_type' => $type, 'object_id' => $id]);
        return new ResultSet($result);
    }

    public function optimize()
    {
        $stmt = $this->pdo->prepare("OPTIMIZE INDEX $index");
        $stmt->execute();
    }
}
