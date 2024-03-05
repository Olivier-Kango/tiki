<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Manticore;

use Feedback;
use PDO;
use PDOException;

class PdoClient
{
    protected const QUERY_RETRIES = 1;

    protected $dsn;
    protected $port;
    protected $pdo;
    protected $debug;
    protected $log;

    public function __construct($dsn, $port = 9306)
    {
        $this->dsn = $dsn;
        $this->port = $port ?? 9306;
        $this->debug = false;
        $this->connect();
    }

    public function __destruct()
    {
        if (is_resource($this->log)) {
            fclose($this->log);
        }
    }

    public static function distributedIndexName()
    {
        global $prefs;

        if (empty($prefs['federated_manticore_index_prefix'])) {
            $prefix = 'tiki_';
        } else {
            $prefix = $prefs['federated_manticore_index_prefix'];
        }

        return $prefix . 'distributed';
    }

    public function getStatus()
    {
        $status = ['status' => 0];
        $result = $this->query('SHOW STATUS');
        while ($row = $result->fetch()) {
            $status[$row['Counter']] = $row['Value'];
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
        try {
            $stmt = $this->query("SHOW INDEX $index STATUS");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getIndicesByPrefix($prefix, $type = 'rt')
    {
        $results = [];
        $stmt = $this->prepareAndExecute("SHOW TABLES LIKE ?", [$prefix . '%']);
        foreach ($stmt->fetchAll() as $row) {
            if (! empty($type) && $row['Type'] != $type) {
                continue;
            }
            $results[] = $row['Index'];
        }
        return $results;
    }

    public function createIndex($index, $definition, $settings = [], $silent = false)
    {
        $cols = [];
        foreach ($definition as $field => $opts) {
            $def = '`' . $field . '` ' . $opts['type'];
            if (! empty($opts['options'])) {
                $def .= ' ' . implode(' ', $opts['options']);
            }
            $cols[] = $def;
        }
        $sql = "CREATE TABLE $index (" . implode(',', $cols) . ")";
        foreach ($settings as $key => $val) {
            $sql .= ' ' . $key . '=' . "'" . $val . "'";
        }
        $this->prepareAndExecuteWithRetry($sql);
    }

    public function recreateDistributedIndex(array $names)
    {
        $this->deleteIndex(self::distributedIndexName());

        $list = [];
        $bindvars = [];
        $agents = [];
        foreach ($names as $key => $name) {
            $def = $this->parseDistributedIndexDefinition($name);
            if ($def['type'] == 'agent') {
                $key = $def['host'] . ':' . $def['port_agent'];
                if (! isset($agents[$key])) {
                    $agents[$key] = [];
                }
                $agents[$key][] = $def['index'];
            } else {
                $list[] = $def['type'] . '=?';
                $bindvars[] = $def['index'];
            }
        }
        foreach ($agents as $key => $indices) {
            $list[] = 'agent=?';
            $bindvars[] = $key . ':' . implode(',', $indices);
        }
        $this->prepareAndExecuteWithRetry("CREATE TABLE " . self::distributedIndexName() . " type='distributed' " . join(' ', $list), $bindvars);
    }

    public function parseDistributedIndexDefinition($name)
    {
        if (strstr($name, ':')) {
            $parts = explode(':', $name);
            $host = trim($parts[0]);
            if (count($parts) > 2) {
                $ports = trim($parts[1]);
                $ports = explode('|', $ports);
            } else {
                $ports = [];
            }
            $index = array_pop($parts);
            if (count($ports) === 1) {
                // table definition might come from DESC distributed table syntax which includes only one port, get the other one from extwiki table
                $extwikis = \TikiLib::lib('admin')->list_extwiki(0, -1, 'extwikiId', '');
                foreach ($extwikis['data'] as $extwiki) {
                    if (! empty($extwiki['indexname']) && preg_match('/' . $host . ':' . $ports[0] . '\|(\d+):/', $extwiki['indexname'], $m)) {
                        $ports[1] = $m[1];
                    }
                }
            }
            return [
                'type' => 'agent',
                'host' => $host,
                'port_agent' => $ports[0] ?? null,
                'port_sql' => $ports[1] ?? null,
                'index' => $index,
            ];
        } else {
            return [
                'type' => 'local',
                'index' => $name,
            ];
        }
    }

    public function possibleFacetFields($table)
    {
        if ($table == self::distributedIndexName()) {
            $stmt = $this->query("DESC $table");
            $result = $stmt->fetchAll();
            $tables = [];
            foreach ($result as $row) {
                $tables[] = $row['Agent'];
            }
        } else {
            $tables[] = $table;
        }
        $fields = [];
        foreach ($tables as $table) {
            $def = $this->parseDistributedIndexDefinition($table);
            if ($def['type'] == 'agent') {
                // remote agent definition
                $client = new PdoClient($def['host'], $def['port_sql']);
                foreach (explode(',', $def['index']) as $index) {
                    $fields[$def['host'] . ':' . $def['port_agent'] . ':' . $index] = $client->describe($index);
                }
            } else {
                // local index
                $fields[$table] = $this->describe($table);
            }
        }
        $common = [];
        $result = array_shift($fields);
        if (! $result) {
            $result = [];
        }
        foreach ($result as $field => $opts) {
            foreach ($fields as $table => $tableFields) {
                if (! isset($tableFields[$field])) {
                    continue 2;
                }
                if ($opts['types'] != $tableFields[$field]['types']) {
                    continue 2;
                }
                if (strstr($table, ':') && ($field == 'deep_categories' || $field == 'categories')) {
                    continue 2;
                }
            }
            $common[] = $field;
        }
        return $common;
    }

    public function deleteIndex($index)
    {
        try {
            $stmt = $this->query("DESC " . self::distributedIndexName());
            $result = $stmt->fetchAll();
            foreach ($result as $row) {
                $def = $this->parseDistributedIndexDefinition($row['Agent']);
                if ($def['type'] == 'local' && $def['index'] == $index) {
                    Feedback::error(tr("Failed deleting Manticore index %0 as it is currently used in a distributed index.", $index));
                    return false;
                }
            }
        } catch (PDOException $e) {
            // no distributed index, continue normally
        }
        try {
            $this->prepareAndExecute("DROP TABLE IF EXISTS $index");
            return true;
        } catch (PDOException $e) {
            Feedback::error(tr("Failed deleting Manticore index %0: ", $index) . $e->getMessage());
            return false;
        }
    }

    public function describe($index)
    {
        try {
            $stmt = $this->query("DESC $index");
            $result = $stmt->fetchAll();
        } catch (PDOException $e) {
            // describe might be used to check if index exists, so suppress not found error here
            return [];
        }
        $mapping = [];
        foreach ($result as $row) {
            if (! isset($mapping[$row['Field']])) {
                $mapping[$row['Field']] = [
                    'types' => [],
                    'options' => [],
                ];
            }
            $mapping[$row['Field']]['types'][] = $row['Type'];
            $mapping[$row['Field']]['options'] = array_merge($mapping[$row['Field']]['options'], explode(' ', $row['Properties']));
        }
        return $mapping;
    }

    public function alter($index, $operation, $field, $type)
    {
        if ($operation == 'drop') {
            $sql = "ALTER TABLE $index DROP COLUMN `$field`";
        } else {
            $sql = "ALTER TABLE $index ADD COLUMN `$field` " . $type['type'];
            if (! empty($type['options'])) {
                $sql .= ' ' . implode(' ', $type['options']);
            }
        }
        $this->prepareAndExecuteWithRetry($sql);
    }

    public function index($index, array $data)
    {
        $array_fields = [];
        $array_values = [];
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $array_fields[] = $key;
                $array_values[] = '(' . implode(',', $val) . ')';
                unset($data[$key]);
            }
        }
        $keys = implode(', ', array_keys($data));
        if ($keys && $array_fields) {
            $keys .= ', ' . implode(', ', $array_fields);
        }
        $values = implode(',', array_fill(0, count($data), '?'));
        if ($values && $array_values) {
            $values .= ', ' . implode(', ', $array_values);
        }
        $this->prepareAndExecuteWithRetry("INSERT INTO $index (" . $keys . ') VALUES (' . $values . ')', array_values($data));
    }

    public function unindex($index, $type, $id)
    {
        $this->prepareAndExecute("DELETE FROM $index WHERE object_type = :object_type AND object_id = :object_id", ['object_type' => $type, 'object_id' => $id]);
    }

    public function document($index, $type, $id)
    {
        $stmt = $this->prepareAndExecute("SELECT * FROM $index WHERE object_type = :object_type AND object_id = :object_id", ['object_type' => $type, 'object_id' => $id]);
        return new ResultSet($stmt->fetch());
    }

    public function optimize($index)
    {
        $this->prepareAndExecute("OPTIMIZE INDEX $index");
    }

    public function percolate($index, $document)
    {
        try {
            $stmt = $this->prepareAndExecute("CALL PQ(?, ?, 1 as query)", [$index . 'pq', json_encode($document)]);
            $results = $stmt->fetchAll();
            return array_map(function ($item) {
                return $item['tags'];
            }, $results);
        } catch (PDOException $e) {
            throw new Exception($e);
        }
    }

    public function unstoreQuery($index, $name)
    {
        $this->prepareAndExecute("DELETE FROM {$index}pq WHERE tags = :tags", ['tags' => $name]);
    }

    public function fetchAll($query)
    {
        $stmt = $this->query($query);
        return $stmt->fetchAll();
    }

    public function fetchAllRowsets($query, $retry = true)
    {
        try {
            $stmt = $this->query($query);
            $result = [$stmt->fetchAll()];
            while ($stmt->nextRowset()) {
                $result[] = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            if ($retry && strstr($e->getMessage(), 'unknown local table')) {
                // federated index tables might have been rebuilt and distributed one not updated properly -> refresh and retry
                \TikiLib::lib('federatedsearch')->recreateDistributedIndex($this);
                return $this->fetchAllRowsets($query, false);
            } else {
                throw $e;
            }
        }
        if ($retry) {
            $stmt = $this->query('show warnings');
            foreach ($stmt->fetchAll() as $row) {
                if (! empty($row['Message']) && preg_match('/unknown local table/', $row['Message'])) {
                    // remote agent rebuild has invalidated this distributed index, need recreate the distributed index with fresh index table names
                    \TikiLib::lib('federatedsearch')->recreateDistributedIndex($this);
                    return $this->fetchAllRowsets($query, false);
                }
            }
        }
        return $result;
    }

    public function quote($string)
    {
        return $this->pdo->quote($string);
    }

    public function turnOnDebug()
    {
        global $tikipath;
        $this->debug = true;
        $this->log = fopen($tikipath . TEMP_PATH . '/manticore-debug.sql', "w");
    }

    protected function debug($query, $params = [])
    {
        if (! $this->debug) {
            return;
        }
        $i = 0;
        $query = preg_replace_callback('/(\?|:[\w+_]+)/', function ($matches) use ($params, $i) {
            if ($matches[1] == '?') {
                return $this->quote($params[$i++]);
            } else {
                return $this->query($params[substr($matches[1], 1)] ?? '');
            }
        }, $query);
        fwrite($this->log, $query . ";\n");
    }

    protected function connect()
    {
        $dsn = rtrim($this->dsn, '/');
        $parsed = parse_url($dsn);
        if ($parsed === false) {
            throw new Exception(tr("Malformed Manticore connection url: %0", $this->dsn));
        }
        if (empty($parsed['host']) && ! empty($parsed['path'])) {
            $parsed['host'] = $parsed['path'];
        }

        $dsn = "mysql:host=" . $parsed['host'] . ";port=" . $this->port;

        try {
            $this->pdo = new PDO($dsn);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception(tr("Error connecting to Manticore service: %0", $e->getMessage()));
        }
    }

    protected function executeWithRetry($stmt, $params = [], $tries = 0)
    {
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            if (strstr($e->getMessage(), "server has gone away")) {
                $this->connect();
                if ($tries < self::QUERY_RETRIES) {
                    $this->executeWithRetry($stmt, $params, $tries + 1);
                }
            } else {
                throw new Exception($e->getMessage());
            }
        }
    }

    protected function query($sql)
    {
        $this->debug($sql);
        return $this->pdo->query($sql);
    }

    protected function prepareAndExecute($sql, $params = [])
    {
        $this->debug($sql, $params);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function prepareAndExecuteWithRetry($sql, $params = [])
    {
        $this->debug($sql, $params);
        $stmt = $this->pdo->prepare($sql);
        $this->executeWithRetry($stmt, $params);
        return $stmt;
    }
}
