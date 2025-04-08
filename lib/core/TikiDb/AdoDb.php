<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Class Tiki\TikiDb\AdoDb
 */

namespace Tiki\TikiDb;

use Exception;
use TikiDb;

class AdoDb extends TikiDb
{
    /** @var ADODB_mysqli */
    private $db;
    /** @var int */
    private $rowCount;

    public function __construct($db)
    {
        if (! $db) {
            throw new Exception('Invalid db object passed to TikiDB constructor');
        }

        $this->db = $db;
        $this->db->SetFetchMode(ADODB_FETCH_ASSOC);
    }

    public function __destruct()
    {
        if ($this->db) {
            $this->db->Close();
        }
    }

    public function getHandler()
    {
        return $this->db;
    }

    public function qstr($str)
    {
        return $this->db->quote($str);
    }

    public function query($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = parent::ERR_DIRECT, array $options = [])
    {
        global $num_queries;
        $num_queries++;

        if ($values === null || is_array($values) && count($values) === 0) {
            $values = false;
        }

        $numrows = (int)$numrows;
        $offset = (int)$offset;
        if ($query == null) {
            $query = $this->getQuery();
        }
        $this->convertQueryTablePrefixes($query);

        $starttime = $this->startTimer();
        if ($numrows == -1 && $offset == -1) {
            $result = $this->db->Execute($query, $values);
        } else {
            $result = $this->db->SelectLimit($query, $numrows, $offset, $values);
        }

        $this->stopTimer($starttime);
        $this->rowCount = $this->db->affected_rows();
        if (! $result) {
            $this->rowCount = 0;
            $this->setErrorMessage($this->db->ErrorMsg());

            $this->handleQueryError($query, $values, $result, $reporterrors);
        }

        global $num_queries;
        $num_queries++;
        $this->setQuery(null);

        return new AdoDbResult($result, $this->rowCount);
    }

    public function scrollableQuery($query = null, $values = null, $numrows = -1, $offset = -1, $reporterrors = parent::ERR_DIRECT, array $options = [])
    {
        // this is already scrollable
        return $this->query($query, $values, $numrows, $offset, $reporterrors);
    }
}
