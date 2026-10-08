<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

/**
 * Class Table_Code_WidgetOptionsOutput
 *
 * Creates the code for the output (export) widget options portion of the Tablesorter jQuery code
 *
 * @package Tiki
 * @subpackage Table
 * @uses Table_Code_WidgetOptions
 */
class Table_Code_WidgetOptionsOutput extends Table_Code_WidgetOptions
{
    protected function getOptionArray()
    {
        $pre = 'output_';
        $m = [];

        if (parent::$output) {
            foreach (parent::$s['output'] as $key => $val) {
                if ($key === 'button') {
                    if (isset(parent::$s['output']['button']['id'])) {
                        $m[] = $pre . $key . ' : "button#' . parent::$s['output']['button']['id'] . '"';
                    }
                } elseif ($key !== 'callback' && strcasecmp((string) $key, 'utf8bom') !== 0 && ! is_numeric($key)) {
                    $m[] = $pre . $key . ' : "' . $val . '"';
                }
            }

            if ($this->utf8BomRequested()) {
                $m[] = $pre . 'callback : function(config, data){return \'\\uFEFF\' + data;}';
            }
        }

        return $m;
    }

    /**
     * tsoutput="...;utf8bom:y" requests a UTF-8 BOM on CSV download.
     */
    private function utf8BomRequested()
    {
        foreach (parent::$s['output'] as $key => $val) {
            if (is_string($key) && strcasecmp($key, 'utf8bom') === 0) {
                return $val === true || strtolower((string) $val) === 'y';
            }
        }
        return false;
    }
}
