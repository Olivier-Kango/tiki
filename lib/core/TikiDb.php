<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Class TikiDb, a singleton representing the entire Tiki database,
 * Implemented by TikiDb_Pdo and TikiDb_Adodb
 */
abstract class TikiDb
{
    /** For SQL performance analysis */
    public const QUERY_OPTION_LOG_GROUP = "QUERY_OPTION_LOG_GROUP";
    public const ERR_DIRECT = true;
    public const ERR_NONE = false;
    public const ERR_EXCEPTION = 'exception';

    private static ?TikiDb $instance = null;

    private $errorHandler;
    private $errorMessage;
    private $errorNo;
    private $serverType;

    protected $savedQuery;

    private $tablePrefix;
    private $usersTablePrefix;

    /**
     * Return the TikiDb global instance, if one has been set
     *
     * There is still a lot of magic in tiki-db.php
     * The instance is actually set from the outside.
     * But you can trust this to return a valid instance, or error out.
     *
     * @return TikiDb
     */
    public static function get(): TikiDb
    {
        if (empty(self::$instance) && (! defined('DB_TIKI_SETUP') || DB_TIKI_SETUP) && ! defined('TIKI_IN_INSTALLER')) {
            // if we are in the console and database setup has completed then this error needs to be ignored.
            global $dbfail_url;

            if (! empty($dbfail_url)) {
                header('location: ' . $dbfail_url);
                exit(1);
            } else {
                if (http_response_code() === false) { // if we are running in cli
                    echo "\nDatabase connection error\n";
                    die(1);
                }
                echo file_get_contents('templates/database_connection_error.html');
                die(1);
            }
        }

        return self::$instance;
    }

    public static function set(TikiDb $instance)
    {
        return self::$instance = $instance;
    }

    /** For exceptional cases, check if the database has been initialised yet */
    public static function isAvailable(): bool
    {
        return self::$instance !== null;
    }

    protected function startTimer()
    {
        list($micro, $sec) = explode(' ', microtime());
        return $micro + $sec;
    }

    protected function stopTimer($starttime)
    {
        global $elapsed_in_db;
        list($micro, $sec) = explode(' ', microtime());
        $now = $micro + $sec;
        $elapsed_in_db += $now - $starttime;
    }

    abstract public function qstr($str);

    /**
     * @param null $query
     * @param null $values
     * @param int $numrows
     * @param int $offset
     * @param bool $reporterrors
     * @param $options additional sql options
     * @return TikiDb_Pdo_Result
     */
    abstract public function query($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = self::ERR_DIRECT, array $options = []);
    /**
     * same as above but return the PDO statement or Adodb result, so it can be scrolled in a memory-efficient way
     * @return TikiDb_Pdo_Result|TikiDb_Adodb_Result
     */
    abstract public function scrollableQuery($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = self::ERR_DIRECT, array $options = []);

    public function lastInsertId()
    {
        return $this->getOne('SELECT LAST_INSERT_ID()');
    }

    public function queryError($query, &$error, $values = null, $numrows = -1, $offset = -1, array $options = [])
    {
        $this->errorMessage = '';
        $result = $this->query($query, $values, $numrows, $offset, self::ERR_NONE, options: $options);
        $error = $this->errorMessage;

        return $result;
    }

    public function queryException($query, $values = null, $numrows = -1, $offset = -1, array $options = [])
    {
        return $this->query($query, $values, $numrows, $offset, self::ERR_EXCEPTION, options: $options);
    }

    public function getOne($query, $values = null, $reporterrors = self::ERR_DIRECT, $offset = 0, array $options = [])
    {
        $result = $this->query($query, $values, 1, $offset, $reporterrors, options: $options);

        if ($result) {
            $res = $result->fetchRow();

            if (empty($res)) {
                return false;
            }

            return reset($res);
        }

        return false;
    }

    /**
     * Fetch all rows for a query
     *
     * @param [type] $query
     * @param array|null $values Any values for bound parameters in the query.  Must match the number of bound parameters in the query
     * @param integer $numrows
     * @param integer $offset
     * @param [type] $reporterrors
     * @return array|false false denotes an error in the query, not an empty result
     */
    public function fetchAll($query = null, ?array $values = null, $numrows = -1, $offset = -1, $reporterrors = self::ERR_DIRECT, array $options = []): array|false
    {
        $result = $this->query($query, $values, $numrows, $offset, $reporterrors, options: $options);

        $rows = [];

        if ($result) {
            while ($row = $result->fetchRow()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function fetchMap($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = self::ERR_DIRECT, array $options = [])
    {
        $result = $this->fetchAll($query, $values, $numrows, $offset, $reporterrors, options: $options);

        $map = [];

        foreach ($result as $row) {
            $key = array_shift($row);
            $value = array_shift($row);

            $map[$key] = $value;
        }

        return $map;
    }

    public function setErrorHandler(TikiDb_ErrorHandler $handler)
    {
        $this->errorHandler = $handler;
    }

    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;
    }

    public function setUsersTablePrefix($prefix)
    {
        $this->usersTablePrefix = $prefix;
    }

    public function getServerType()
    {
        return $this->serverType;
    }

    public function setServerType($type)
    {
        $this->serverType = $type;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    protected function setErrorMessage($message)
    {
        $this->errorMessage = $message;
    }

    public function getErrorNo()
    {
        return $this->errorNo;
    }

    protected function setErrorNo($no)
    {
        $this->errorNo = $no;
    }

    protected function handleQueryError($query, $values, $result, $mode)
    {
        if ($mode === self::ERR_NONE) {
            return null;
        } elseif ($mode === self::ERR_DIRECT && $this->errorHandler) {
            $this->errorHandler->handle($this, $query, $values, $result);
        } elseif ($mode === self::ERR_EXCEPTION || ! $this->errorHandler) {
            TikiDb_Exception::classify($this->errorMessage);
        }
    }

    protected function convertQueryTablePrefixes(&$query)
    {
        $db_table_prefix = $this->tablePrefix;
        $common_users_table_prefix = $this->usersTablePrefix;

        if (! is_null($db_table_prefix) && ! empty($db_table_prefix)) {
            if (! is_null($common_users_table_prefix) && ! empty($common_users_table_prefix)) {
                $query = str_replace("`users_", "`" . $common_users_table_prefix . "users_", $query);
            } else {
                $query = str_replace("`users_", "`" . $db_table_prefix . "users_", $query);
            }

            $query = str_replace("`tiki_", "`" . $db_table_prefix . "tiki_", $query);
            $query = str_replace("`messu_", "`" . $db_table_prefix . "messu_", $query);
            $query = str_replace("`sessions", "`" . $db_table_prefix . "sessions", $query);
        }
    }

    public function convertSortMode($sort_mode, $fields = null)
    {
        if (! $sort_mode) {
            return '1';
        }
        // parse $sort_mode for evil stuff
        $sort_mode = str_replace('pref:', '', $sort_mode);
        $sort_mode = preg_replace('/[^A-Za-z_,.]/', '', $sort_mode);

        // Do not process empty sort modes
        if (empty($sort_mode)) {
            return '1';
        }

        if ($sort_mode == 'random') {
            return "RAND()";
        }

        $sorts = [];
        foreach (explode(',', $sort_mode) as $sort) {
            // force ending to either _asc or _desc unless it's "random"
            $sep = strrpos($sort, '_');
            $dir = substr($sort, $sep);
            if (($dir !== '_asc') && ($dir !== '_desc')) {
                if ($sep != (strlen($sort) - 1)) {
                    $sort .= '_';
                }
                $sort .= 'asc';
            }

            // When valid fields are specified, skip those not available
            if (is_array($fields) && preg_match('/^(.*)_(asc|desc)$/', $sort, $parts)) {
                if (! in_array($parts[1], $fields)) {
                    continue;
                }
            }

            $sort = preg_replace('/_asc$/', '` asc', $sort);
            $sort = preg_replace('/_desc$/', '` desc', $sort);
            $sort = '`' . $sort;
            $sort = str_replace('.', '`.`', $sort);
            $sorts[] = $sort;
        }

        if (empty($sorts)) {
            return '1';
        }

        $sort_mode = implode(',', $sorts);
        return $sort_mode;
    }

    public function validateSortColumn($table, $column)
    {
        // Removing the _desc or _asc suffix
        $base_column = preg_replace('/_(desc|asc)$/', '', $column);
        $query = "SHOW COLUMNS FROM $table LIKE '$base_column'";
        $result = $this->query($query);

        return $result->numrows > 0;
    }

    public function getQuery()
    {
        return $this->savedQuery;
    }

    public function setQuery($sql)
    {
        $this->savedQuery = $sql;
    }

    public function ifNull($field, $ifNull)
    {
        return " COALESCE($field, $ifNull) ";
    }

    public function in($field, $values, &$bindvars)
    {
        $parts = explode('.', $field);
        foreach ($parts as &$part) {
            $part = '`' . $part . '`';
        }
        $field = implode('.', $parts);
        $bindvars = array_merge($bindvars, $values);

        if (count($values) > 0) {
            $values = rtrim(str_repeat('?,', count($values)), ',');
            return " $field IN( $values ) ";
        } else {
            return " 0 ";
        }
    }

    public function parentObjects(&$objects, $table, $childKey, $parentKey)
    {
        $query = "select `$childKey`, `$parentKey` from `$table` where `$childKey` in (" . implode(',', array_fill(0, count($objects), '?')) . ')';
        foreach ($objects as $object) {
            $bindvars[] = $object['itemId'];
        }
        $result = $this->query($query, $bindvars);
        while ($res = $result->fetchRow()) {
            $ret[$res[$childKey]] = $res[$parentKey];
        }
        foreach ($objects as $i => $object) {
            $objects[$i][$parentKey] = $ret[$object['itemId']];
        }
    }

    public function concat()
    {
        $arr = func_get_args();

        // suggestion by andrew005@mnogo.ru
        $s = implode(',', $arr);
        if (strlen($s) > 0) {
            return "CONCAT($s)";
        } else {
            return '';
        }
    }

    public function table($tableName, $autoIncrement = true)
    {
        return new TikiDb_Table($this, $tableName, $autoIncrement);
    }

    public function begin()
    {
        return new TikiDb_Transaction();
    }

    /**
    * Get a list of installed engines in the MySQL instance
    * $return array of engine names
    */
    public function getEngines()
    {
        static $engines = [];
        if (empty($engines)) {
            $result = $this->query('show engines');
            if ($result) {
                while ($res = $result->fetchRow()) {
                    $engines[] = $res['Engine'];
                }
            }
        }
        return $engines;
    }

    /**
     * Check if InnoDB is an avaible engine
     * @return true if the InnoDB engine is available
     */
    public function hasInnoDB()
    {
        $engines = $this->getEngines();
        foreach ($engines as $engine) {
            if (strcmp(strtoupper($engine), 'INNODB') == 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detect the engine used in the current schema.
     * Assumes that all tables use the same table engine
     * @return string identifying the current engine, or an empty string if not installed
     */
    public function getCurrentEngine()
    {
        static $engine = '';
        if (empty($engine)) {
            $result = Installer::getInstance()->query('SHOW TABLE STATUS LIKE "tiki_schema"', countQueries: false);
            if ($result) {
                $res = $result->fetchRow();
                $engine  = $res['Engine'];
            }
        }
        return $engine;
    }

    /**
     * Determine if MySQL fulltext search is supported by the current DB engine
     * Assumes that all tables use the same table engine.
     * Fulltext search is assumed supported if
     * 1) engine = MyISAM
     * 2) engine = InnoDB and MySQL version >= 5.6
     * @return true if it is supported, otherwise false
     */
    public function isMySQLFulltextSearchSupported()
    {
        $currentEngine = $this->getCurrentEngine();
        if (strcasecmp($currentEngine, "MyISAM") == 0) {
            return true;
        } elseif (strcasecmp($currentEngine, "INNODB") == 0) {
            $versionNr = $this->getMySQLVersionNr();
            if ($versionNr >= 5.6) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }


    /**
     * Read the MySQL version string.
     * @return version string
     */
    public function getMySQLVersion()
    {
        static $version = '';
        if (empty($version)) {
            $result = $this->query('select version() as Version');
            if ($result) {
                $res = $result->fetchRow();
                $version  = $res['Version'];
            }
        }
        return $version;
    }
    /**
     * Read the MySQL version number, e.g. 5.5
     * @return version float
     */
    public function getMySQLVersionNr()
    {
        $versionNr = 0.0;
        $version = $this->getMySQLVersion();
        $versionNr = (float)$version;
        return $versionNr;
    }

    public function listTables()
    {
        $result = $this->fetchAll("show tables");
        $list = [];

        if ($result) {
            foreach ($result as $row) {
                $list[] = reset($row);
            }
        }

        return $list;
    }

    /*
    *   isMySQLConnSSL
    *   Check if MySQL is using an SSL connection
    *   @return true if MySQL uses SSL. Otherwise false;
    */
    public function isMySQLConnSSL()
    {
        if (! $this->haveMySQLSSL()) {
            return false;
        }
        $result = $this->query('show status like "Ssl_cipher"');
        $ret = $result->fetchRow();
        $cypher = $ret['Value'];
        return ! empty($cypher);
    }

    /*
    *   Check if the MySQL installation has SSL activated
    *   @return true is SSL is supported and activated on the current MySQL server
    */
    public function haveMySQLSSL()
    {
        static $haveMySQLSSL = null;

        if (! isset($haveMySQLSSL)) {
            $result = $this->query('show variables like "have_ssl"');
            $ret = $result->fetchRow();
            if (empty($ret)) {
                $result = $this->query('show variables like "have_openssl"');
                $ret = $result->fetchRow();
            }
            if (! isset($ret)) {
                $haveMySQLSSL = false;
            }
            $ssl = $ret['Value'];
            if (empty($ssl)) {
                $haveMySQLSSL = false;
            } else {
                $haveMySQLSSL = $ssl == 'YES';
            }
        }
        return $haveMySQLSSL;
    }


    /**
     * Obtain a lock with a name given by the string $str, using a $timeout of timeout seconds
     * @param $str
     * @param int $timeout
     * @return bool if lock was created
     */
    public function getLock($str, $timeout = 1)
    {
        if ($this->isLocked($str)) {
            return false;
        }
        $result = $this->getOne("SELECT GET_LOCK(?, ?) as isLocked", [$str, $timeout]);
        return (bool)((int)$result);
    }

    /**
     * Releases the lock named by the string $str
     * @param $str
     * @return bool
     */
    public function releaseLock($str)
    {
        $result = $this->getOne("SELECT RELEASE_LOCK(?) as isReleased", [$str]);
        return (bool)((int)$result);
    }

    /**
     * Checks whether the lock named $str is in use (that is, locked)
     * @param $str
     * @return bool
     */
    public function isLocked($str)
    {
        $result = $this->getOne("SELECT IS_USED_LOCK(?) as isLocked", [$str]);
        return (bool)((int)$result);
    }

    public static function splitSqlStatements($command)
    {
        $statements = [];
        $buffer = '';
        $insideFunction = false;

        // Split by line breaks to process each line
        $lines = preg_split("/\r\n|\n|\r/", $command);

        foreach ($lines as $line) {
            // Check if we're starting a CREATE FUNCTION or CREATE PROCEDURE block
            if (preg_match('/^\s*CREATE\s+(FUNCTION|PROCEDURE)/i', $line)) {
                $insideFunction = true;
            }

            // If we're inside a function/procedure, just add the line without splitting on semicolons
            if ($insideFunction) {
                $buffer .= $line . "\n";

                // Detect the end of the function/procedure block (usually 'END;' or 'END$$')
                if (preg_match('/^\s*END\s*;/i', $line)) {
                    $statements[] = trim($buffer);
                    $buffer = '';
                    $insideFunction = false;
                }
            } else {
                // If not inside a function, split by semicolon
                $buffer .= $line . "\n";
                if (preg_match('/;\s*$/', $line)) {
                    $statements[] = trim($buffer);
                    $buffer = '';
                }
            }
        }

        // Add any remaining buffer content as a statement
        if (! empty(trim($buffer))) {
            $statements[] = trim($buffer);
        }

        return $statements;
    }
}
