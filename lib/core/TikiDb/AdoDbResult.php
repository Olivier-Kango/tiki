<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Class Tiki\TikiDb\AdoDbResult
 *
 * Returns result set along with affected rows
 */
namespace Tiki\TikiDb;

class AdoDbResult
{
    /** @var ADORecordSet */
    public $result;
    /** @var int */
    public $numrows;

    /**
     * Tiki\TikiDb\AdoDbResult constructor.
     * @param $result
     * @param $rowCount
     */
    public function __construct($result, $rowCount)
    {
        $this->result = &$result;
        $this->numrows = is_numeric($rowCount) ? $rowCount : $this->result->RowCount();
    }

    /** @return array|int|false */
    public function fetchRow()
    {
        if (is_object($this->result)) {
            return $this->result->fetchRow();
        } elseif (is_array($this->result)) {
            return array_shift($this->result);
        } else {
            return 0;
        }
    }

    /** @return int */
    public function numRows()
    {
        return (int) $this->numrows;
    }
}
