<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Class Tiki\TikiDb\PdoResult
 *
 * Returns result set along with affected rows
 */
namespace Tiki\TikiDb;

class PdoResult
{
    /** @var array */
    public $result;
    /** @var int */
    public $numrows;

    /**
     * Tiki\TikiDb\PdoResult constructor.
     * @param $result
     * @param $rowCount
     */
    public function __construct($result, $rowCount)
    {
        $this->result = &$result;
        $this->numrows = is_numeric($rowCount) ? $rowCount : count($this->result);
    }

    /** @return array */
    public function fetchRow()
    {
        return is_array($this->result) ? array_shift($this->result) : 0;
    }

    /** @return int */
    public function numRows()
    {
        return (int) $this->numrows;
    }
}
