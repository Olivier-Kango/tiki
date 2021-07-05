<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

/**
 * Class Table_Code_WidgetOptionsPager
 *
 * Creates code for the pager widget of the Tablesorter code, including the code for ajax
 *
 * @package Tiki
 * @subpackage Table
 * @uses Table_Code_WidgetOptions
 */
class Table_Code_WidgetOptionsPager extends Table_Code_WidgetOptions
{

    public function getOptionArray()
    {
        $p = [];
        $pre = 'pager_';
        //add pager controls
        if (parent::$pager) {
            $p[] = $pre . 'size: ' . parent::$s['pager']['max'];
            //pager css
            $pc[] = 'container: \'ts-pager\'';
            $p[] = $this->iterate($pc, $pre . 'css: {', $this->nt3 . '}', $this->nt4, '');
            //pager selectors
            $ps[] = 'container : \'div#' . parent::$s['pager']['controls']['id'] . '\'';
            $p[] = $this->iterate($ps, $pre . 'selectors: {', $this->nt3 . '}', $this->nt4, '');
            $p[] = $pre . 'output: \'{startRow} - {endRow} / {filteredRows} ({totalRows})\'';
        }

        //ajax settings
        if (parent::$ajax) {
            $total = ! empty(parent::$s['total']) ? parent::$s['total'] : 0;
            $p[] = $pre . 'processAjaxOnInit: false';
            $p[] = $pre . 'initialRows: {total: ' . $total . ', filtered: ' . $total . '}';
            $p[] = $pre . 'ajaxObject: {dataType: \'html\'}';
            $p[] = $pre . 'ajaxUrl : \'' . parent::$s['ajax']['url']['file']
                . parent::$s['ajax']['url']['query'] . '\'';
            $p[] = $pre . 'savePages: false';

            //ajax processing - this part grabs the html, usually from the smarty template file
            //first prepare code to add a row total column using the math widget if set
            $addcol = ! empty(parent::$s['math']['totals']['row']) ?
                json_encode(parent::$s['math']['totals']['row']) : 0;
            $ap = [
                'var rows = tsAjaxGetRows(data, \'' . parent::$tid . '\', ' . $addcol . ', ' . $total . ');',
                'return rows;'
            ];
            $p[] = $this->iterate(
                $ap,
                $pre . 'ajaxProcessing: function(data, table){',
                $this->nt3 . '}',
                $this->nt4,
                '',
                ''
            );

            //customAjaxUrl: takes the url parameters generated by Tablesorter and converts to parameters that can
            //be used by Tiki
            $ajax = parent::$s['ajax'];
            if (isset($ajax['sort'])) {
                $ajax['asort'] = $ajax['sort'];
                unset($ajax['sort']);
            }
            $ajax['numrows'] = ! empty($ajax['numrows']) ? $ajax['numrows'] : 'numrows';
            $ajax['colselector'] = parent::$usecolselector ? 1 : 0;
            $ajax['tableid'] = parent::$tid;
            $ca = [
                'var p = table.config.pager, newurl = tsAjaxUrl(url, p, ' . json_encode($ajax) . ');',
                'return newurl;'
            ];
            if (count($ca) > 0) {
                array_filter($ca);
                $p[] = $this->iterate(
                    $ca,
                    $pre . 'customAjaxUrl: function(table, url) {',
                    $this->nt3 . '}',
                    $this->nt4,
                    '',
                    ''
                );
            }
        }
        if (count($p) > 0) {
            return $p;
        } else {
            return false;
        }
    }
}
