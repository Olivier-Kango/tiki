<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
    header("location: index.php");
    exit;
}

ini_set('include_path', ini_get('include_path') . ":lib/sheet");

// Nice dependencies, mostly for excel support. Don't try changing the order.
require_once("lib/sheet/excel/reader_ole.php");
require_once("lib/sheet/excel/reader.php");
require_once("lib/sheet/excel/writer/format.php");
require_once("lib/sheet/excel/writer/biffwriter.php");
require_once("lib/sheet/excel/writer/worksheet.php");
require_once("lib/sheet/excel/writer/workbook.php");
require_once("lib/sheet/excel/writer/parser.php");
require_once("lib/sheet/ole/pps.php");
require_once("lib/sheet/ole/pps/root.php");
require_once("lib/sheet/ole/pps/file.php");
require_once("lib/sheet/ole.php");
require_once("lib/sheet/excel/writer.php");
//require_once( "lib/sheet/conf/config.inc.php" );
require_once("lib/encoding/lib-encoding.php");
include_once 'lib/diff/Diff.php';
include_once 'lib/diff/Renderer.php';
// Constants

/*
DATA:
End values will be preserved.

CALC:
The calculations will be preserved.

CELL:
The cell merging will be preserved.

FORMAT:
The display format of the data of the cell
*/

define('TIKISHEET_SAVE_DATA', 0x00010000);
define('TIKISHEET_SAVE_CALC', 0x00020000);
define('TIKISHEET_SAVE_CELL', 0x00040000);
define('TIKISHEET_SAVE_FORMAT', 0x00080000);

define('TIKISHEET_LOAD_DATA', 0x00000001);
define('TIKISHEET_LOAD_CALC', 0x00000002);
define('TIKISHEET_LOAD_CELL', 0x00000004);
define('TIKISHEET_LOAD_FORMAT', 0x00000008);

// Initial amount of rows and columns at TikiSheet initialisation
define('INITIAL_ROW_COUNT', 15);
define('INITIAL_COL_COUNT', 5);

// Map array indexes.
define('TS_THEAD', 0);
define('TS_TBODY', 1);
define('TS_TFOOT', 2);

define('TS_DEFAULT', 'default');


// Registration function
function TIKISHEET_REGISTER_HANDLER($class)
{
    global $globalHandlers;
    $globalHandlers[] = $class;
}

/** TikiSheetDataFormat Class
 * Class containing the different supported data formats by TikiSheet.
 * The formats coded in this class should also exist in lib/sheet/formula.js
 */
class TikiSheetDataFormat
{
    public function currency($value, $before = '', $after = '')
    {
        return $before . sprintf("%.2f", (float)$value) . $after;
    }

    public function currency_ca($value)
    {
        return TikiSheetDataFormat::currency($value, '', '$');
    }

    public function currency_us($value)
    {
        return TikiSheetDataFormat::currency($value, '$');
    }
}

 /** TikiSheet Class
 * Calculation sheet data container. Used as a bridge between
 * different formats.
 * @author Louis-Philippe Huberdeau (lphuberdeau@phpquebec.org)
 */
class TikiSheet
{
     // Attributes
    /**
     * Two dimensional array, grid containing the end values ([y][x])
     */
    public $dataGrid;

    /**
     * Two dimensional array, grid containing the raw values ([y][x])
     */
    public $calcGrid;

    /**
     * Two dimensional array, grid containing an associative arrays
     * with 'height' and 'width' values.
     */
    public $cellInfo;

    /**
     * Row and column count once finalized.
     */
    public $rowCount;
    public $columnCount;
    public $metadata;

    /**
     * Layout parameters.
     */
    public $headerRow;
    public $footerRow;
    public $parseValues;
    public $cssName;

    /**
     * Internal values.
     */
    public $COLCHAR;
    public $indexes;
    public $lastIndex;
    public $lastID;

    public $usedRow;
    public $usedCol;

    public $errorFlag;

    public $contributions;
    public $id;
    public $name;
    public $type;

    public $rangeBeginRow = -1;
    public $rangeEndRow   = -1;
    public $rangeBeginCol = -1;
    public $rangeEndCol = -1;

    public $className;

    public function getRangeBeginRow()
    {
        return $this->rangeBeginRow > -1 ? $this->rangeBeginRow : 0;
    }

    public function getRangeEndRow()
    {
        return $this->rangeEndRow > -1 ? $this->rangeEndRow : $this->getRowCount();
    }

    public function getRangeBeginCol()
    {
        return $this->rangeBeginCol > -1 ? $this->rangeBeginCol : 0;
    }

    public function getRangeEndCol()
    {
        return $this->rangeEndCol > -1 ? $this->rangeEndCol : $this->getColumnCount();
    }

    /** getHandlerList
     * Returns an array containing the list of all valid
     * handlers for general file import/export.
     * @return array.
     * @static
     */
    public function getHandlerList()
    {
        return [
            'TikiSheetCSVHandler',
            'TikiSheetCSVExcelHandler',
        ];
    }

    /** TikiSheet
     * Initializes the data container.
     */
    public function __construct()
    {
        $this->dataGrid = [];
        $this->calcGrid = [];
        $this->cellInfo = [];

        $this->COLCHAR = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $this->indexes = [ $this->COLCHAR[0] => 0 ];
        $this->lastIndex = 0;
        $this->lastID = $this->COLCHAR[0];

        $this->rowCount = INITIAL_ROW_COUNT;
        $this->columnCount = INITIAL_COL_COUNT;

        $this->headerRow = 0;
        $this->footerRow = 0;
        $this->parseValues = 'n';
        $this->className = '';
    }

    /** configureLayout
     * Assigns the different parameters for the output
     * @param $className    String The class that will be assigned
     *                      to the table tag of the output.
     *                      If used for an other output than
     *                      HTML, it can be used as an identifier
     *                      for the type of layout.
     * @param $headerRow    Integer The amount of rows that are considered
     *                      as part of the header.
     * @param $footerRow    Integer The amount of rows that are considered
     *                      as part of the footer.
     * @param $parseValues  String Parse cell values as wiki text if ='y'
     *                      when using output handler
     */
    public function configureLayout($className, $headerRow = 0, $footerRow = 0, $parseValues = 'n', $metadata = '')
    {
        $this->cssName = $className;
        $this->headerRow = $headerRow;
        $this->footerRow = $footerRow;
        $this->parseValues = $parseValues;
        $this->metadata = json_decode($metadata ?? "");
    }

    /** getColumnIndex
     * Returns the index of the column from a cell ID.
     * @param $id String Cell ID in [A-Z]+[0-9]+ format.
     * @return Integer Zero-based column index.
     */
    public function getColumnIndex($id)
    {
        if (! preg_match("/^([A-Z]+)([0-9]+)$/", $id, $parts)) {
            return false;
        }

        if (! isset($this->indexes[ $parts[1] ])) {
            while ($this->lastID != $parts[1]) {
                $this->lastID = $this->increment($this->lastID);
                $this->lastIndex++;

                $this->indexes[$this->lastID] = $this->lastIndex;
            }

            return $this->lastIndex;
        } else {
            return $this->indexes[ $parts[1] ];
        }
    }

    /** getRowIndex
     * Returns the index of the row from a cell ID.
     * @param $id String Cell ID in [A-Z]+[0-9]+ format.
     * @return Integer Zero-based row index.
     */
    public function getRowIndex($id)
    {
        if (! preg_match("/^([A-Z]+)([0-9]+)$/", $id, $parts)) {
            return false;
        }

        return $parts[2] - 1;
    }

    /** equals
     * Determines if the value, calculation and size are equal at
     * certain coordinates in the current and the given sheet.
     * @param $sheet TikiSheet The sheet to compare.
     * @param $rowIndex Integer The row coordinate.
     * @param $columnIndex Integer The column coordinate.
     * @return Boolean True if all values are equal.
     */
    public function equals(TikiSheet &$sheet, $rowIndex, $columnIndex)
    {
        if (isset($this->dataGrid[$rowIndex][$columnIndex]) && ! isset($sheet->dataGrid[$rowIndex][$columnIndex])) {
            return false;
        }

        if (isset($this->calcGrid[$rowIndex][$columnIndex]) && ! isset($sheet->calcGrid[$rowIndex][$columnIndex])) {
            return false;
        }

        $dataGrid = $this->dataGrid[$rowIndex][$columnIndex];
        $calcGrid = $this->calcGrid[$rowIndex][$columnIndex];
        $cellInfo = $this->cellInfo[$rowIndex][$columnIndex];

        $sheetDataGrid = $sheet->dataGrid[$rowIndex][$columnIndex];
        $sheetCalcGrid = $sheet->calcGrid[$rowIndex][$columnIndex];
        $sheetCellInfo = $sheet->cellInfo[$rowIndex][$columnIndex];

        return (
            $dataGrid == $sheetDataGrid
            && $calcGrid == $sheetCalcGrid
            && isset($sheetCellInfo['value'])
            && isset($cellInfo['value'])
            && isset($sheetCellInfo['calculation'])
            && isset($cellInfo['calculation'])
            && $cellInfo['value'] == $sheetCellInfo['value']
            && $cellInfo['calculation'] == $sheetCellInfo['calculation']
            && $cellInfo['width'] == $sheetCellInfo['width']
            && $cellInfo['height'] == $sheetCellInfo['height']
            && $cellInfo['format'] == $sheetCellInfo['format']
            && $cellInfo['style'] == $sheetCellInfo['style']
            && $cellInfo['class'] == $sheetCellInfo['class']
        );
    }

    /** export
     * Exports the content of the calculation sheet
     * to the given format handler.
     * @param $handler The format handler.
     * @return True on success.
     */
    public function export(&$handler)
    {
        return $handler->_save($this);
    }

    /**
     * @param $incsubs boolean Include sub-sheets
     * @param $date
     * @return String
     */
    public function getTableHtml($incsubs = true, $date = null)
    {
        global $prefs;

        $isParse = isset($_REQUEST['parse']) && $_REQUEST['parse'] != 'n';

        $sheetlib = TikiLib::lib('sheet');
        $filegallib = TikiLib::lib("filegal");

        $handler = new TikiSheetOutputHandler(null, ($this->parseValues == 'y' && $isParse));

        $this->export($handler);

        $data = $handler->output;

        if ($incsubs == true) {
            //get sheets from db first
            foreach ($sheetlib->get_related_sheet_ids($this->id) as $childSheetId) {
                $handler = new TikiSheetDatabaseHandler($childSheetId, $date);
                $childSheet = new TikiSheet();
                $childSheet->import($handler);
                $childSheet->parseValues = true;
                $data .= $childSheet->getTableHtml(false);
            }
        }
        foreach ($sheetlib->get_related_file_ids($this->id) as $childFileId) {
            $fileInfo = $filegallib->get_file_info($childFileId);

            switch ($fileInfo['filetype']) {
                case 'text/csv':
                    $handler = new TikiSheetCSVHandler($fileInfo);
                    break;
                default:
                        $handler = false;
            }

            if (! empty($handler)) {
                $childSheet = new TikiSheet();
                $childSheet->import($handler);
                $data .= $childSheet->getTableHtml();
            }
        }

        foreach ($sheetlib->get_related_tracker_ids($this->id) as $childTrackerId) {
            $handler = new TikiSheetTrackerHandler($childTrackerId);
            $childSheet = new TikiSheet();
            $childSheet->import($handler);
            $data .= $childSheet->getTableHtml();
        }


        return $data;
    }

    /** finalize
     * Analyses the content of the sheet and complete the
     * the load.
     */
    public function finalize()
    {
        $maxRow = 0;
        $maxCol = 0;

        $this->finalizeGrid($this->dataGrid, $maxRow, $maxCol);
        $this->finalizeGrid($this->calcGrid, $maxRow, $maxCol);
        $this->finalizeGrid($this->cellInfo, $maxRow, $maxCol, true);

        $this->rowCount = ($maxRow >= INITIAL_ROW_COUNT || $maxRow > 0 ? $maxRow : INITIAL_ROW_COUNT);
        $this->columnCount = ($maxCol >= INITIAL_COL_COUNT || $maxCol > 0 ? $maxCol : INITIAL_COL_COUNT);

        $base = [ 'width' => 1, 'height' => 1, 'format' => null, 'style' => '', 'class' => '' ];
        for ($y = 0; $this->rowCount > $y; $y++) {
            for ($x = 0; $this->columnCount > $x; $x++) {
                if (! isset($this->dataGrid[$y])) {
                    $this->dataGrid[$y] = [];
                }
                if (! isset($this->calcGrid[$y])) {
                    $this->calcGrid[$y] = [];
                }
                if (! isset($this->cellInfo[$y])) {
                    $this->cellInfo[$y] = [];
                }

                if (! isset($this->dataGrid[$y][$x])) {
                    $this->dataGrid[$y][$x] = '';
                }
                if (! isset($this->calcGrid[$y][$x])) {
                    $this->calcGrid[$y][$x] = '';
                }
                if (! isset($this->cellInfo[$y][$x])) {
                    $this->cellInfo[$y][$x] = $base;
                }


                $this->cellInfo[$y][$x] = array_merge($base, $this->cellInfo[$y][$x]);
            }
        }
        return true;
    }

    /** finalizeGrid
     * Locates the maximal values in a grid if they are above the initial ones.
     * @param $grid Array The grid to scan
     * @param $maxRow Integer The highest row index.
     * @param $maxCol Integer The highest column index.
     * @param $addIndex Boolean value, used for merged cells, determines
     *                  if the actual value should be added when calculating
     *                  the maximal values. As merged cells use more space,
     *                  they should be considered as more cells.
     */
    public function finalizeGrid($grid, &$maxRow, &$maxCol, $addIndex = false)
    {
        foreach ($grid as $key => $row) {
            $this->finalizeRow($row, $maxRow, $maxCol, $key, $addIndex);
        }
    }

    /** finalizeRow
     * Identifies the largest key in an array and set it as the new maximum.
     * @param $row Integer The row to scan.
     * @param $maxRow Integer The current maximum value of the row.
     * @param $maxCol Integer The current maximum value of the column.
     * @param $rowIndex Integer
     * @param $addIndex Boolean Used for merged cells. Leave value blank (false) if the current scan is not on the
     * merged cell grid. Other possible values are 'width' and 'height' which should be used based on which side of the
     * grid is being scanned.
     */
    public function finalizeRow($row, &$maxRow, &$maxCol, $rowIndex, $addIndex = false)
    {
        $localMax = max(array_keys($row));

        $total = $localMax;
        if ($addIndex) {
            $total += $row[$localMax]['width'];
        }

        if ($total > $maxCol) {
            $maxCol = $total;
        }

        if ($addIndex) {
            foreach ($row as $info) {
                if (isset($info['height'])) {
                    $total = $rowIndex + $info['height'];
                }

                if ($total > $maxRow) {
                    $maxRow = $total;
                }
            }
        } else {
            if ($rowIndex > $maxRow) {
                $maxRow = $rowIndex;
            }
        }
    }

    /** getColumnCount
     * Returns the column count.
     */
    public function getColumnCount()
    {
        return $this->columnCount == 0 ? INITIAL_COL_COUNT : $this->columnCount;
    }

    /** getRange
     * Reutrns an array containing the values located in
     * a given range (ex: A1:B9)
     */
    public function getRange($range)
    {
        if (preg_match('/^([A-Z]+)([0-9]+):([A-Z]+)([0-9]+)$/', strtoupper($range), $parts)) {
            $beginRow = $parts[2] - 1;
            $endRow = $parts[4] - 1;
            $beginCol = $this->getColumnNumber($parts[1]);
            $endCol = $this->getColumnNumber($parts[3]);

            if ($beginRow > $endRow) {
                $a = $endRow;
                $endRow = $beginRow;
                $beginRow = $a;
            }
            if ($beginCol > $endCol) {
                $a = $endCol;
                $endCol = $beginCol;
                $beginCol = $a;
            }

            $data = [];
            for ($row = $beginRow; $endRow + 1 > $row; $row++) {
                for ($col = $beginCol; $endCol + 1 > $col; $col++) {
                    if (isset($this->dataGrid[$row]) && isset($this->dataGrid[$row][$col])) {
                        $data[] = $this->dataGrid[$row][$col];
                    }
                }
            }

            return $data;
        } else {
            return false;
        }
    }

    /** setRange
     * Limits display (so far)
     * a given range (ex: A1:B9)
     */
    public function setRange($range)
    {
        if (preg_match('/^([A-Z]+)([0-9]+):([A-Z]+)([0-9]+)$/', strtoupper($range), $parts)) {
            $this->rangeBeginRow = (int)$parts[2] - 1;
            $this->rangeEndRow = (int)$parts[4];
            $this->rangeBeginCol = $this->getColumnNumber($parts[1]);
            $this->rangeEndCol = $this->getColumnNumber($parts[3]) + 1;
        }
    }

    /** getRowCount
     * Returns the row count.
     */
    public function getRowCount()
    {
        return $this->rowCount;
    }

    public function name()
    {
        return $this->name;
    }

    /** import
     * Fills the content of the calculation sheet with
     * data from the given handler.
     * @param $handler Object The format handler.
     * @return True on success.
     */
    public function import(&$handler)
    {
        $this->name = $handler->name();
        if (isset($handler->id)) {
            $this->id = $handler->id;
        }
        $this->type = (isset($handler->type) ? $handler->type : $this->type);
        $this->cssName = $handler->cssName;
        $this->rowCount = (isset($handler->rowCount) ? $handler->rowCount : $this->rowCount);
        $this->columnCount = (isset($handler->columnCount) ? $handler->columnCount : $this->columnCount);

        $this->dataGrid = [];
        $this->calcGrid = [];
        $this->cellInfo = [];
        $this->errorFlag = false;

        set_error_handler([ &$this, "error_handler" ]);
        if (! $handler->_load($this) || $this->errorFlag) {
            restore_error_handler();
            return false;
        }

        restore_error_handler();
        return $this->finalize();
    }

    /** increment
     * Implementation of the column ID incrementation used
     * on client side.
     * @param $val String The value to increment.
     * @return Integer The incremented value.
     */
    public function increment($val)
    {
        if (empty($val)) {
            return substr($this->COLCHAR, 0, 1);
        }

        $n = strpos($this->COLCHAR, substr($val, -1)) + 1;

        if ($n < strlen($this->COLCHAR)) {
            return substr($val, 0, -1) . substr($this->COLCHAR, $n, 1);
        } else {
            return $this->increment(substr($val, 0, -1)) . substr($this->COLCHAR, 0, 1);
        }
    }

    /** initCell
     * Indicates the next cell that will be filled.
     * @param $cellID Integer The Identifier of the cell or the row index
     *                  if there are 2 parameters.
     * @param $col Integer The index of the column.
     * @return True on success.
     */
    public function initCell($cellID, $col = null)
    {
        if ($col === null) {
            $this->usedRow = $this->getRowIndex($cellID);
            $this->usedCol = $this->getColumnIndex($cellID);
        } else {
            $this->usedRow = $cellID;
            $this->usedCol = $col;
        }

        return $this->usedRow !== false && $this->usedCol !== false;
    }

    /** isEmpty
     * Determines if the value, calculation and size are equal at
     * certain coordinates in the current and the given sheet.
     * @param $rowIndex Integer The row coordinate.
     * @param $columnIndex Integer The column coordinate.
     * @return True if all values are empty.
     */
    public function isEmpty($rowIndex, $columnIndex)
    {
        return $this->dataGrid[$rowIndex][$columnIndex] == ''
            && $this->calcGrid[$rowIndex][$columnIndex] == ''
            && ( $this->cellInfo[$rowIndex][$columnIndex]['width'] == ''
            ||   $this->cellInfo[$rowIndex][$columnIndex]['width'] == 1 )
            && ( $this->cellInfo[$rowIndex][$columnIndex]['height'] == ''
            ||   $this->cellInfo[$rowIndex][$columnIndex]['height'] == 1 );
    }

    /** setCalculation
     * Assigns a calculation to the currently initialized
     * cell.
     * @param $calculation String The calculation to set.
     */
    public function setCalculation($calculation)
    {
        $this->calcGrid[$this->usedRow][$this->usedCol]['calculation'] = $calculation;
    }

    /** setFormat
     * Indicates the cell's data format during display.
     * The format is a text identifier that matches a function
     * name that will be executed.
     */
    public function setFormat($format)
    {
        if (empty($format) || ! method_exists(new TikiSheetDataFormat(), $format)) {
            $format = null;
        }
        $this->cellInfo[$this->usedRow][$this->usedCol]['format'] = $format;
    }

    /** setRowSpan
     * Sets the cell's row span
     * @param $rowSpan Number row span
     */
    public function setRowSpan($rowSpan)
    {
        $this->cellInfo[$this->usedRow][$this->usedCol]["height"] = $rowSpan;
    }

    /** setSize
     * Sets the size of the last initialized cell.
     * @param $colSpan Number col span
     */
    public function setColSpan($colSpan)
    {
        $this->cellInfo[$this->usedRow][$this->usedCol]["width"] = $colSpan;
    }

    public function setDeadCells()
    {
        $usedRow = $this->usedRow;
        $usedCol = $this->usedCol;
        $cellInfo = $this->cellInfo[$this->usedRow][$this->usedCol];

        for ($y = $usedRow; $usedRow + $cellInfo['height'] > $y; $y++) {
            for ($x = $usedCol; $usedCol + $cellInfo['width'] > $x; $x++) {
                if (! ($y == $usedRow && $x == $usedCol)) {
                    $this->createDeadCell($x, $y);
                }
            }
        }
    }

    /** setValue
     * Assigns a value to the currently initialized
     * cell.
     * @param $value String The value to set.
     */
    public function setValue($value)
    {
        $this->dataGrid[$this->usedRow][$this->usedCol]['value'] = $value;
    }

    /** setStyle
     * Sets html style,if any, to the currently initialized
     * cell.
     * @param $style String The value to set.
     */
    public function setStyle($style = '')
    {
        $this->cellInfo[$this->usedRow][$this->usedCol]['style'] = $style;
    }

    /** setClass
     * Sets html class, if any, to the currently initialized
     * cell.
     * @param $class String The value to set.
     */
    public function setClass($class = '')
    {
        $this->cellInfo[$this->usedRow][$this->usedCol]['class'] = $class;
    }

    /** createDeadCell
     * Assigns the cell as overlapped by a wide cell.
     * @param $x Integer Coordinate of the cell
     * @param $y Integer Coordinate of the cell
     */
    public function createDeadCell($x, $y)
    {
        $this->dataGrid[$y][$x] = null;
        $this->cellInfo[$y][$x] = [ "width" => 0, "height" => 0, "format" => null, "style" => "", "class" => "" ];
    }

    /** getClass
     * Returns the class of a the current cell if it exist.
     */
    public function getClass()
    {
        if (isset($this->cellInfo[$this->usedRow][$this->usedCol]['class'])) {
            return $this->cellInfo[$this->usedRow][$this->usedCol]['class'];
        } else {
            return "";
        }
    }

    /** getColumnNumber
     * Returns the column number from the letter-style.
     */
    public function getColumnNumber($letter)
    {
        $val = 0;
        $len = strlen($letter);

        for ($i = 0; $len > $i; $i++) {
            $pow = pow(26, $len - $i - 1);
            $val += $pow * ( ord($letter[$i]) - 64 );
        }
        $val--;

        return $val;
    }

    /** error_handler
     * Callback error handler function. Used by import.
     * @see http://ca.php.net/set_error_handler
     */
    public function error_handler($errno, $errstr, $errfile, $errline)
    {
        echo $errstr . ': ' . $errfile . ' (' . $errline . ')';
        $this->errorFlag = true;
    }
}

/** TikiSheetDataHandler
 * Base data handler to link the sheet to the data
 * source. Before being sent as an handler, the object
 * must know the target location of the data if they
 * are required.
 */
class TikiSheetDataHandler
{
    public $maxrows = 300;
    public $maxcols = 26;
    public $output = "";
    public $cssName;

    /** name
     * Identifies the handler in a readable form.
     * @return The name of the handler.
     */
    public function name()
    {
        throw new BadMethodCallException(tr("Abstract method call. %0 not defined in %1", __FUNCTION__ . '()', get_class($this)));
    }
    /** supports
     * Function to indicate the features that are supported
     * by the handler.
     * @param The feature constant.
     * @return true if the feature is supported.
     * @static
     * @abstract
     */
    public function supports($feature)
    {
        throw new BadMethodCallException(tr("Abstract method call. %0 not defined in %1", __FUNCTION__ . '()', get_class($this)));
    }

    /** version
     * Indicates the handler's version.
     * @return The version number as a string.
     */
    public function version()
    {
        throw new BadMethodCallException(tr("Abstract method call. %0 not defined in %1", __FUNCTION__ . '()', get_class($this)));
    }
}

/** TikiSheetCSVHandler
 * Class that stores the sheet representation in a
 * standard text file as a serialized PHP object.
 */
class TikiSheetCSVHandler extends TikiSheetDataHandler
{
    /**
     * @var string
     */
    public $type;
    public $data;
    public $name;
    public $id;
    public $encoding;
    /**
     * @var bool
     */
    public $truncated;
    public $file = 'php://stdout';
    public $lineLen;

    /** Constructor
     * Initializes the the serializer on a file.
     * @param $file The file path to save or load from.
     */
    public function __construct($fileInfo = "php://stdout", $inputEncoding = '', $outputEncoding = '', $lineLen = 1024)
    {
        $this->lineLen = $lineLen;
        $this->type = "file";

        if (is_array($fileInfo)) { // When loading from FileGalLib
            $this->data = strip_tags($fileInfo['data']);
            $this->name = $fileInfo['name'];
            $this->id = $fileInfo['fileId'];
        } else { // when FileInfo is in fact the name of a file
            $this->file = $fileInfo;
            if ($this->file === 'php://stdout' || $this->file === 'php://output') {
                $this->data = '';
            } else {
                $this->data = strip_tags(file_get_contents($this->file));
            }
        }

        $this->encoding = new Encoding($inputEncoding, $outputEncoding);
    }

    // _load
    public function _load(TikiSheet &$sheet)
    {
        $rows = explode("\n", $this->data);
        for ($i = 0; $i < count($rows) && $i < $this->maxrows; $i++) {
            $cols = str_getcsv($rows[$i], escape: "");

            for ($j = 0; $j < count($cols) && $j < $this->maxcols; $j++) {
                $sheet->initCell($i, $j);

                if (! empty($cols[$j])) {
                    if ($cols[$j][0] == '=') {
                        $sheet->setCalculation(substr($cols[$j], 1));
                    } else {
                        $sheet->setValue($cols[$j]);
                    }
                } else {
                    $sheet->setValue("");
                }

                $sheet->setRowSpan(1);
                $sheet->setColSpan(1);
            }
        }

        if ($i >= $this->maxrows || $j >= $this->maxcols) {
            $this->truncated = true;
        }

        return true;
    }

    // _save
    public function _save(&$sheet)
    {
        $total = [];

        ksort($sheet->dataGrid);
        foreach ($sheet->dataGrid as $row) {
            if (is_array($row)) {
                ksort($row);
                $values = (array)$row;
                array_walk($values, function (&$item) {
                    if (is_array($item) && array_key_exists('value', $item)) {
                        $item = $item['value'];
                    }
                });
                    $total[] = implode(",", $values);
            }
        }

        if (is_array($total)) {
            $total = implode("\n", $total);
        }

        $total = $this->encoding->convert_encoding($total);

        if ($this->file === 'php://stdout' || $this->file === 'php://output') {
            $this->output = $total;
            return true;
        } else {
            if ($file = @fopen($this->file, "w")) {
                if (! @fwrite($file, $total)) {
                    return false;
                }

                @fclose($file);
                return true;
            } else {
                return false;
            }
        }
    }

    // name
    public function name()
    {
        return tr("CSV File (commas)");
    }

    // supports
    public function supports($type)
    {
        return ( ( TIKISHEET_SAVE_DATA | TIKISHEET_LOAD_DATA ) & $type ) > 0;
    }

    // version
    public function version()
    {
        return "1.0";
    }
}


/** TikiSheetTrackerHandler
 * Class that stores the sheet representation in a
 * standard text file as a serialized PHP object.
 */
class TikiSheetTrackerHandler extends TikiSheetDataHandler
{
    public $id;
    public $def;
    public $info;
    /**
     * @var string
     */
    public $type;
    public $file;
    public $lineLen;

    /** Constructor
     * Initializes the the serializer on a file.
     * @param $file The file path to save or load from.
     */
    public function __construct($trackerId)
    {
        $tikilib = TikiLib::lib('tiki');
        $trklib = TikiLib::lib("trk");

        $this->id = $trackerId;
        $this->def = Tracker_Definition::get($trackerId);
        $this->info = $this->def->getInformation();
        $this->type = "tracker";
        $this->cssName = 'readonly';
    }

    // _load
    public function _load(&$sheet)
    {
        $tikilib = TikiLib::lib('tiki');

        $i = 0;
        $trackerName = $this->info['name'];
        $tracker = Tracker_Query::tracker($trackerName)
            ->byName()
            ->excludeDetails()
            ->render(false)
            ->query();

        foreach ($tracker as $item) {
            $j = 0;
            foreach ($item as $key => $field) {
                $sheet->initCell($i, $j);

                if (! empty($field[0]) && $field[0] == '=') {
                    $sheet->setCalculation(substr($field, 1));
                }

                $sheet->setValue($i == 0 ? $key : $field);

                $sheet->setColSpan(1, 1);
                $j++;
            }
            $i++;
        }

        return true;
    }

    // _save
    public function _save(&$sheet)
    {
        return false;
    }

    // name
    public function name()
    {
        return $this->info['name'];
    }

    // supports
    public function supports($type)
    {
        return ( ( TIKISHEET_LOAD_DATA ) & $type ) > 0;
    }

    // version
    public function version()
    {
        return "1.0";
    }
}


/** TikiSheetTrackerHandler
 * Class that stores the sheet representation in a
 * standard text file as a serialized PHP object.
 */
class TikiSheetSimpleArrayHandler extends TikiSheetDataHandler
{
    public $name;
    /**
     * @var string
     */
    public $type;
    public $values = [];

    public function __construct($simpleArray = [])
    {
        $this->values = $simpleArray['values'];
        $this->name = $simpleArray['name'];
        $this->type = "simpleArray";
        $this->cssName = 'readonly';
    }

    // _load
    public function _load(&$sheet)
    {
        $i = 0;

        foreach ($this->values as $row) {
            $j = 0;
            foreach ($row as $key => $col) {
                $sheet->initCell($i, $j);

                if (! empty($col[0]) && $col[0] == '=') {
                    $sheet->setCalculation(substr($col, 1));
                }

                if (is_array($col)) {
                    foreach ($col as $colKey => $val) {
                        if (empty($val)) {
                            array_splice($col, $colKey, 1);
                        }
                    }
                    $col = implode(",", $col);
                }

                $col = htmlspecialchars($col);

                $sheet->setValue($i == 0 ? $key : $col);

                $sheet->setColSpan(1, 1);
                $j++;
            }
            $i++;
        }

        return true;
    }

    // _save
    public function _save(&$sheet)
    {
        return false;
    }

    // name
    public function name()
    {
        return $this->name;
    }

    // supports
    public function supports($type)
    {
        return ( ( TIKISHEET_LOAD_DATA ) & $type ) > 0;
    }

    // version
    public function version()
    {
        return "1.0";
    }
}

 /** TikiSheetCSVExcelHandler
 * Class that stores the sheet representation in a
 * standard text file as a serialized PHP object. The difference
 * betwen this and standard CSV is that fields here are separarated by ';'
 */
class TikiSheetCSVExcelHandler extends TikiSheetDataHandler
{
    public $data;
    public $encoding;
    /**
     * @var bool
     */
    public $truncated;
    public $file;
    public $lineLen;

    /** Constructor
     * Initializes the the serializer on a file.
     * @param $file The file path to save or load from.
     */
    public function __construct($file = "php://stdout", $inputEncoding = '', $outputEncoding = '', $lineLen = 1024)
    {
        $this->file = $file;
        $this->lineLen = $lineLen;
        $this->data = strip_tags(file_get_contents($this->file));
        $this->encoding = new Encoding($inputEncoding, $outputEncoding);
    }

// _load
    public function _load(&$sheet)
    {
        $rows = explode("\n", $this->data);
        for ($i = 0; $i < count($rows) && $i < $this->maxrows; $i++) {
            $cols = preg_split("/[,;](?!(?:[^\\\",;]|[^\\\"],[^\\\"])+\\\")/", $rows[$i]);

            for ($j = 0; $j < count($cols) && $j < $this->maxcols; $j++) {
                $sheet->initCell($i, $j);
                $sheet->setValue($cols[$j]);

                if (isset($cols[$j])) {
                    if (strlen($cols[$j])) {
                        if ($cols[$j][0] == '=') {
                            $sheet->setCalculation(substr($cols[$j], 1));
                        }
                    }
                }

                $sheet->setColSpan(1, 1);
            }
        }

        if ($i >= $this->maxrows || $j >= $this->maxcols) {
            $this->truncated = true;
        }

        return true;
    }

    // _save
    public function _save(&$sheet)
    {
        $total = [];

        foreach ($sheet->dataGrid as $row) {
            $values = (array)$row;
            array_walk($values, function (&$item) {
                if (is_array($item) && array_key_exists('value', $item)) {
                    $item = $item['value'];
                }
            });

            $total[] = implode(';', $values);
        }

        if (is_array($total)) {
            $total = implode("\n", $total);
        }

        $total = $this->encoding->convert_encoding($total);

        if ($this->file == "php://stdout") {
            $this->output = $total;
            return true;
        } else {
            if ($file = @fopen($this->file, "w")) {
                if (! @fwrite($file, $total)) {
                    return false;
                }

                @fclose($file);
                return true;
            } else {
                return false;
            }
        }
    }

    // name
    public function name()
    {
        return tr("CSV-Excel File (semicolons)");
    }

    // supports
    public function supports($type)
    {
        return ( ( TIKISHEET_SAVE_DATA | TIKISHEET_LOAD_DATA ) & $type ) > 0;
    }

    // version
    public function version()
    {
        return "1.0";
    }

    private function fputcsvexcel($row, $fd = ';', $quot = '"', $limit = null)
    {
        $str = '';
        $i = 0;
        foreach ($row as $col) {
            if ($i && $i < $limit) {
                $cell = ($col->formula ? $col->formula : $col->value);
                str_replace(
                    [$quot,        "\n"],
                    [$quot . $quot,  ''],
                    $cell
                );

                if (strchr($cell, $fd)) {
                    $str .= $quot . $cell . $quot . $fd;
                } else {
                    $str .= $cell . $fd;
                }
            }
            $i++;
        }

        return  $str;
    }
}

/** TikiSheetDatabaseHandler
 * Class to handle transactions with the database.
 * The class and database structure allow data
 * rollbacks. The class does not allow to manipulate
 * the sheets themselves. The data will only be filled
 * and extracted based on the given sheet ID. As a default
 * value, the most recent entries will be read.
 *
 * The database loader will also select the appropriate
 * layout based on the timestamped database entries. Using
 * the database handler will not require to specify manually
 * using TikiSheet::configureLayout() as it is required by all
 * other known handler as this comment is being written.
 */
class TikiSheetDatabaseHandler extends TikiSheetDataHandler
{
    public $id;
    public $readDate;
    public $rowCount;
    public $columnCount;
    public $metadata;
    public $type;
    public $name;
    public $className;

    /** Constructor
     * Assigns a sheet ID to the handler.
     * @param $id Integer The ID of the sheet in the database.
     * @param $date Integer The database link to use.
     */
    public function __construct($id, $date = null, $metadata = null)
    {
        $sheetlib = TikiLib::lib('sheet');

        $this->id = $id;
        $this->readDate = ( $date ? $date : time() );

        $info = $sheetlib->get_sheet_info($this->id);

        $this->type = "sheet";
        $this->name = $info['title'];
        $this->metadata = $metadata;
    }

    // _load
    public function _load(TikiSheet &$sheet)
    {
        $sheetlib = TikiLib::lib('sheet');
        $tikilib = TikiLib::lib('tiki');

        $result = $tikilib->query("
            SELECT `rowIndex`, `columnIndex`, `value`, `calculation`, `width`, `height`, `format`, `style`, `class`, `user`
            FROM `tiki_sheet_values`
            WHERE
                `sheetId` = ? AND
                ? >= `begin` AND
                (
                    `end` IS NULL OR
                    `end` > ?
                )
        ", [ $this->id, (int)$this->readDate, (int)$this->readDate ]);

        while ($row = $result->fetchRow()) {
            $sheet->initCell($row['rowIndex'], $row['columnIndex']);
            $sheet->setValue($row['value']);
            $sheet->setCalculation($row['calculation']);
            $sheet->setColSpan($row['width']);
            $sheet->setRowSpan($row['height']);
            $sheet->setDeadCells();
            $sheet->setFormat($row['format']);
            $sheet->setStyle($row['style']);
            $sheet->setClass($row['class']);
        }

        // Fetching the layout informations.
        $result2 = $tikilib->query("
            SELECT `className`, `headerRow`, `footerRow`, `parseValues`, `metadata`
            FROM `tiki_sheet_layout`
            WHERE
                `sheetId` = ? AND
                ? >= `begin` AND
                ( `end` IS NULL OR `end` > ? )
        ", [ $this->id, (int)$this->readDate, (int)$this->readDate ]);

        if ($row = $result2->fetchRow()) {
            $sheet->configureLayout($row['className'], $row['headerRow'], $row['footerRow'], $row['parseValues'], $row['metadata']);
        }

        return true;
    }

    public function name()
    {
        return $this->name;
    }

    // _save
    public function _save(TikiSheet &$sheet)
    {
        global $user, $prefs;
        $tikilib = TikiLib::lib('tiki');

        // Load the current database state {{{3
        $current = new TikiSheet();
        $handler = new TikiSheetDatabaseHandler($this->id, null, $this->metadata);
        $current->import($handler);

        // Find differences {{{3
        $mods = [];
        for ($row = 0; $sheet->getRowCount() > $row; $row++) {
            for ($col = 0; $sheet->getColumnCount() > $col; $col++) {
                if (! $sheet->equals($current, $row, $col)) {
                    $mods[] = [ "row" => $row, "col" => $col ];
                }
            }
        }

        $stamp = time();

        $inserts = [];
        $updates = [];
        $updates[] = $stamp;
        $updates[] = $this->id;

        // Update the database {{{3
        if (is_array($mods)) {
            foreach ($mods as $coord) {
                extract($coord);
                $value = $sheet->dataGrid[$row][$col]['value'] ?? null;
                $calc = $sheet->calcGrid[$row][$col]['calculation'] ?? null;
                $width = $sheet->cellInfo[$row][$col]['width'] ?? 1;
                $height = $sheet->cellInfo[$row][$col]['height'] ?? 1;
                $format = $sheet->cellInfo[$row][$col]['format'] ?? null;
                $style = $sheet->cellInfo[$row][$col]['style'];
                $class = $sheet->cellInfo[$row][$col]['class'];

                $updates[] = $row;
                $updates[] = $col;

                //Now that sheets have styles, many things can change and the cell not have a value.
                //if ( !$sheet->isEmpty( $row, $col ) )
                $inserts[] = [ (int)$this->id, $stamp, $row, $col, $value, $calc, $width, $height, $format, $style, $class, $user ];
            }
        }

        $updates[] = $sheet->getRowCount();
        $updates[] = $sheet->getColumnCount();

        $conditions = str_repeat("( rowIndex = ? AND columnIndex = ? ) OR ", ( count($updates) - 4 ) / 2);
        if ($prefs['feature_actionlog'] == 'y') { // must keep the previous value to do the difference
            $query = "SELECT `rowIndex`, `columnIndex`, `value`, `style`, `class` FROM `tiki_sheet_values` WHERE `sheetId` = ? AND  `end` IS NULL";
            $result = $tikilib->query($query, [$this->id]);
            $old = [];
            while ($row = $result->fetchRow()) {
                $old[$row['rowIndex'] . '-' . $row['columnIndex']] = $row['value'];
                if (isset($old[$row['rowIndex'] . '-' . $row['columnIndex']]['style'])) {
                    $old[$row['rowIndex'] . '-' . $row['columnIndex']]['style'] = $row['style'];
                }

                if (isset($old[$row['rowIndex'] . '-' . $row['columnIndex']]['class'])) {
                    $old[$row['rowIndex'] . '-' . $row['columnIndex']]['class'] = $row['class'];
                }
            }

            $tikilib->query("UPDATE `tiki_sheet_layout` SET `metadata` = ?  WHERE `sheetId` = ?", [$handler->metadata, $handler->id]);
        }

        $tikilib->query("UPDATE `tiki_sheet_values` SET `end` = ?  WHERE `sheetId` = ? AND `end` IS NULL AND ( {$conditions}`rowIndex` >= ? OR `columnIndex` >= ? )", $updates);

        if (count($inserts) > 0) {
            foreach ($inserts as $values) {
                $tikilib->query("INSERT INTO `tiki_sheet_values` (`sheetId`, `begin`, `rowIndex`, `columnIndex`, `value`, `calculation`, `width`, `height`, `format`, `style`, `class`, `user` ) VALUES( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )", $values);
            }
        }

        if ($prefs['feature_actionlog'] == 'y') {
            $logslib = TikiLib::lib('logs');
            $add = 0;
            $del = 0;
            foreach ($inserts as $values) {
                $add += strlen($values[4]);
                if (! empty($old[$values[2] . '-' . $values[3]])) {
                    $del += strlen($old[$values[2] . '-' . $values[3]]);
                }
            }
            if ($prefs['feature_contribution'] == 'y' && isset($_REQUEST['contributions'])) {
                $contributionlib = TikiLib::lib('contribution');
                $contributionlib->assign_contributions($_REQUEST['contributions'], $this->id, 'sheet', '', '', '');
            }
            if (isset($_REQUEST['contributions'])) {
                $logslib->add_action('Updated', $this->id, 'sheet', "add=$add&amp;del=$del&amp;sheetId=" . $this->id, '', '', '', '', $_REQUEST['contributions']);
            } else {
                $logslib->add_action('Updated', $this->id, 'sheet', "add=$add&amp;del=$del&amp;sheetId=" . $this->id);
            }
        }

        // }}}3

        return true;
    }

    /** setReadDate
     * Modifies the instant at which the snapshot of the
     * database is taken.
     * @param $timestamp A unix timestamp.
     */
    public function setReadDate($timestamp)
    {
        $this->readDate = $timestamp;
    }

    // supports
    public function supports($type)
    {
        return ( ( TIKISHEET_SAVE_DATA | TIKISHEET_SAVE_CALC | TIKISHEET_SAVE_CELL | TIKISHEET_SAVE_FORMAT | TIKISHEET_LOAD_DATA | TIKISHEET_LOAD_CALC | TIKISHEET_LOAD_CELL | TIKISHEET_LOAD_FORMAT ) & $type ) > 0;
    }

    // version
    public function version()
    {
        return "1.0";
    }
}

/** TikiSheetOpenOfficeHandler
 * Class to generate OpenOffice sxc documents.
 */
class TikiSheetOpenOfficeHandler extends TikiSheetDataHandler
{
    /** Constructor
     * Does nothing special.
     */
    public function __construct($file = "php://stdout")
    {
    }

    // _save
    public function _save(&$sheet)
    {
        // Get rid of debug output
        ob_start();

        APIC::import("org.apicnet.io.OOo.objOOo.OOoTable");
        $OOoCalc = APIC::loadClass("org.apicnet.io.OOo.OOoDoc");

        $OOoCalc->newCalc();
        $OOoCalc->setName("export.sxc");
        $OOoCalc->meta->setCreator("TikiSheet");
        $OOoCalc->meta->setTitle(tr("TikiSheet Export"));

        $OOoCalc->content->addFeuille();

        foreach ($sheet->dataGrid as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $OOoCalc->content->addcellData($rowIndex + 1, $columnIndex + 1, ["DATA" => $value['value']]);
            }
        }

        $OOoCalc->save();
        $OOoCalc->close();
        ob_end_clean();

        $OOoCalc->download();

        return true;
    }

    // name
    public function name()
    {
        return tr("OpenOffice.org File");
    }


    // supports
    public function supports($type)
    {
        return ( ( TIKISHEET_SAVE_DATA ) & $type ) > 0;
    }

    // version
    public function version()
    {
        return "0.1-dev";
    }
}

/** TikiSheetWikiTableHandler
 * Class that stores the sheet representation in a
 * standard text file as a serialized PHP object.
 */
class TikiSheetWikiTableHandler extends TikiSheetDataHandler
{
    public $pageName;

    /** Constructor
     * Initializes the the serializer on a wiki page
     * @param $file The name of the wiki page to perform actions on.
     */
    public function __construct($pageName)
    {
        $this->pageName = $pageName;
    }

    // _load
    public function _load(TikiSheet &$sheet)
    {
        $tikilib = TikiLib::lib('tiki');

        $result = $tikilib->query("SELECT `data` FROM `tiki_pages` WHERE `pageName` = ?", [ $this->pageName ]);
        if ($row = $result->fetchRow()) {
            $tables = $this->getRawTables($row['data']);

            $row = 0;
            foreach ($tables as $table) {
                $table = explode("\n", $table);

                foreach ($table as $line) {
                    $line = explode('|', trim($line));

                    foreach ($line as $col => $cellValue) {
                        $value = $cellValue['value'];
                        $sheet->initCell($row, $col);
                        $sheet->setValue($value);
                        if (isset($value)) {
                            if (preg_match("/^::(.*)::$/", $value, $matches)) {
                                $sheet->setClass($sheet->getClass() . " styleCenter");
                                $value = $matches[1];
                                $sheet->setValue($value);
                            }
                            if (preg_match("/^__(.*)__$/", $value, $matches)) {
                                $sheet->setClass($sheet->getClass() . " styleBold");
                                $value = $matches[1];
                                $sheet->setValue($value);
                            }
                            if (preg_match("/^''(.*)''$/", $value, $matches)) {
                                $sheet->setClass($sheet->getClass() . " styleItalic");
                                $value = $matches[1];
                                $sheet->setValue($value);
                            }
                            if (strlen($value)) {
                                if ($value[0] == '=') {
                                    $sheet->setCalculation(substr($value, 1));
                                } else {
                                    $sheet->setCalculation($value);
                                }
                            }
                        }
                        $sheet->setRowSpan(1);
                        $sheet->setColSpan(1);
                        $sheet->setDeadCells();
                    }
                    ++$row;
                }
            }

            return true;
        } else {
            return false;
        }
    }

    /** getRawTables
     * Returns an array containing all table-like structures
     * in the wiki-content.
     */
    public function getRawTables($data)
    {
        $pos = 0;
        $tables = [];
        while (true) { // Keep looping
            if (( $begin = strpos($data, '||', $pos) ) === false) {
                break;
            };
            if (( $end = strpos($data, '||', $begin + 2) ) === false) {
                break;
            }

            $pos = $end + 2;

            $content = substr($data, $begin + 2, $end - $begin - 2);

            if (strpos($content, '|') !== false) {
                $tables[] = $content;
            }
        }


        return $tables;
    }

    // name
    public function name()
    {
        return tr("CSV File");
    }

    // supports
    public function supports($type)
    {
        return ( TIKISHEET_LOAD_DATA & $type ) > 0;
    }

    // version
    public function version()
    {
        return "0.1-dev";
    }
}

/** TikiSheetOutputHandler
 * Class to output the data sheet as a standard HTML table.
 * Importing is not supported.
 */
class TikiSheetOutputHandler extends TikiSheetDataHandler
{
    public $heading;
    public $parseOutput;

    /** Constructor
     * Identifies the caption of the table if it applies.
     * @param $heading          The heading
     * @param $parseOutput      Parse wiki markup in cells if parseValues=y in sheet layout
     */
    public function __construct($heading = null, $parseOutput = true)
    {
        $this->heading = $heading;
        $this->parseOutput = $parseOutput;
    }

    // _save
    public function _save(TikiSheet &$sheet)
    {
//      if ( $sheet->headerRow + $sheet->footerRow > $sheet->getRowCount() )
//          return false;
        $beginRow = $sheet->getRangeBeginRow();
        $endRow = $sheet->getRangeEndRow();

        $beginCol = $sheet->getRangeBeginCol();
        $endCol = $sheet->getRangeEndCol();

        if (
            $beginRow > -1 &&
            $beginRow == $endRow - 1 &&
            $beginCol == $endCol - 1
        ) {
            if (isset($sheet->dataGrid[$beginRow][$beginCol])) {
                $data = $sheet->dataGrid[$beginRow][$beginCol];
                if (is_array($data)) {
                    $data = implode('', $data);
                }

                if ($sheet->parseValues == 'y' && mb_ereg_match('[^A-Za-z0-9\s]', $data)) { // needs to be multibyte regex here
                    global $tikilib;
                    $data = TikiLib::lib('parser')->parse_data($data, ['suppress_icons' => true]);
                }
                $sheet->dataGrid[$beginRow][$beginCol] = $data;
            }
        }

        $class = empty($sheet->cssName) ? "" : " class='{$sheet->cssName}'";
        $id = empty($sheet->id) ? '' : " data-id='{$sheet->id}'";
        $title = " title='" . htmlspecialchars($sheet->name(), ENT_QUOTES) . "'";
        $type = (! empty($sheet->type) ? ' data-type="' . $sheet->type . '" ' : '');

        $this->output = "<table" . $class . $id . $title . $type . ">\n";

        if (! is_null($this->heading)) {
            $this->output .= "    <caption>{$this->heading}</caption>\n";
        }

        if ($sheet->headerRow > 0 && $beginRow < 0) {
            $this->output .= "    <thead>\n";
            $this->drawRows($sheet);
            $this->output .= "    </thead>\n";
        }

        $this->output .= "    <colgroup>\n";
        $this->drawCols($sheet);
        $this->output .= "    </colgroup>\n";

        $this->output .= "    <tbody>\n";
        $this->drawRows($sheet);
        $this->output .= "    </tbody>\n";

        if ($sheet->footerRow > 0 && $beginRow < 0) {
            $this->output .= "    <tfoot>\n";
            $this->drawRows($sheet);
            $this->output .= "    </tfoot>\n";
        }

        $this->output .= "</table>\n";
        return true;
    }

    /** drawRows
     * Draws out a defined set of rows from the sheet.
     * @param $sheet The data container.
     * @param $begin The index of the begining row. (included)
     * @param $end The index of the end row (excluded)
     */
    public function drawRows(TikiSheet &$sheet)
    {
        $sheetlib = TikiLib::lib('sheet');

        $beginRow = $sheet->getRangeBeginRow();
        $endRow = $sheet->getRangeEndRow();

        $beginCol = $sheet->getRangeBeginCol();
        $endCol = $sheet->getRangeEndCol();

        for ($i = $beginRow; $i < $endRow; $i++) {
            $td = "";
            $trStyleHeight = "";
            $trHeight = "20px";
            $trHeightIsSet = false;

            for ($j = $beginCol; $j < $endCol; $j++) {
                $width = $height = '';
                if (! empty($sheet->cellInfo[$i][$j])) {
                    extract($sheet->cellInfo[$i][$j]);
                }

                $append = '';

                if ($width > 1) {
                    $append .= " colspan='{$width}'";
                }

                if ($height > 1) {
                    $append .= " rowspan='{$height}'";
                }

                if (! empty($sheet->calcGrid[$i][$j]['calculation'])) {
                    $append .= ' data-formula="=' . str_replace('"', "'", $sheet->calcGrid[$i][$j]['calculation']) . '"';
                }

                if (isset($sheet->dataGrid[$i][$j]['value'])) {
                    $data = $sheet->dataGrid[$i][$j]['value'];
                } else {
                    $data = '';
                }

                $format = $sheet->cellInfo[$i][$j]['format'];
                if (! empty($format)) {
                    $data = TikiSheetDataFormat::$format($data);
                }

                $style = $sheet->cellInfo[$i][$j]['style'];
                if (! empty($style)) {
                    //we have to sanitize the css style here
                    $tdStyle = "";
                    $color = $sheetlib->get_attr_from_css_string($style, "color", "");
                    $bgColor = $sheetlib->get_attr_from_css_string($style, "background-color", "");
                    $tdHeight = '';

                    if ($trHeightIsSet == false) {
                        $trHeight = $sheetlib->get_attr_from_css_string($style, "height", "20px");
                        $trHeightIsSet = true;
                    }

                    if ($color) {
                        $tdStyle .= "color:$color;";
                    }
                    if ($bgColor) {
                        $tdStyle .= "background-color:$bgColor;";
                    }

                    $tdHeight = $trHeight;
                    if ($tdHeight) {
                        $tdStyle .= "height:$tdHeight;";
                        $append .= " height='" . str_replace("px", "", $tdHeight) . "'";
                    }

                    $append .= " style='$tdStyle'";
                }

                $class = $sheet->cellInfo[$i][$j]['class'];
                if (! empty($class)) {
                    $append .= ' class="' . $class . '"';
                }

                if ($this->parseOutput && $sheet->parseValues == 'y') {
                    global $tikilib;
                    // only parse if we have non-alphanumeric or space chars
                    if (mb_ereg_match('[^A-Za-z0-9\s]', $data)) {   // needs to be multibyte regex here
                        $data = TikiLib::lib('parser')->parse_data($data, ['suppress_icons' => true]);
                    }
                    if (strpos($data, '<p>') === 0) {   // remove containing <p> tag
                        $data = substr($data, 3);
                        if (strrpos($data, '</p>') === strlen($data) - 4) {
                            $data = substr($data, 0, -4);
                        }
                    }
                }
                $td .= "            <td" . $append . ">$data</td>\n";
            }

            if (! empty($td)) {
                $this->output .= "        <tr style='height: $trHeight;' height='" . str_replace("px", "", $trHeight) . "'>\n";
                $this->output .= $td;
                $this->output .= "        </tr>\n";
            }
        }
    }

    /** drawCols
     * Draws out a defined set of rows from the sheet.
     * @param $sheet The data container.
     * @param $begin The index of the begining row. (included)
     * @param $end The index of the end row (excluded)
     */
    public function drawCols(TikiSheet &$sheet)
    {
        $sheetlib = TikiLib::lib('sheet');

        if (isset($sheet->metadata) && isset($sheet->metadata->widths)) {
            foreach ($sheet->metadata->widths as $width) {
                $this->output .= "<col style='width:" . ($width * 1) . "px;' />";
            }
        } else {
            $beginCol = $sheet->getRangeBeginCol();
            $endCol = $sheet->getRangeEndCol();
            for ($i = $beginCol; $i < $endCol; $i++) {
                $style = $sheet->cellInfo[0][$i]['style'];
                $width = $sheetlib->get_attr_from_css_string($style, "width", "118px");
                $this->output .= "<col style='width: $width;' width='$width' />\n";
            }
        }
    }

    // supports
    public function supports($type)
    {
        return ( ( TIKISHEET_SAVE_DATA | TIKISHEET_SAVE_CELL | TIKISHEET_SAVE_FORMAT ) & $type ) > 0;
    }

    // version
    public function version()
    {
        return "1.0";
    }
}

/** TikiSheetLabeledOutputHandler
 * Class to output the data sheet as a standard HTML table.
 * Importing is not supported.
 */
class TikiSheetLabeledOutputHandler extends TikiSheetDataHandler
{
    /** Constructor
     */
    public function __construct()
    {
    }

    // _save
    public function _save(&$sheet)
    {
        $this->output = "<table class=\"default\">\n";

        $this->output .= "    <thead>\n";
        $this->output .= "        <tr><th></th>\n";

        $prev = 'A';
        for ($j = 0; $sheet->getColumnCount() > $j; $j++) {
            $this->output .= "            <th>$prev</th>\n";
            $prev = $sheet->increment($prev);
        }

        $this->output .= "        </tr>\n";
        $this->output .= "    </thead>\n";

        $this->output .= "    <tbody>\n";
        $this->drawRows($sheet);
        $this->output .= "    </tbody>\n";

        $this->output .= "</table>\n";

        return true;
    }

    /** drawRows
     * Draws out a defined set of rows from the sheet.
     * @param $sheet The data container.
     * @param $begin The index of the begining row. (included)
     * @param $end The index of the end row (excluded)
     */
    public function drawRows(&$sheet)
    {
        $sheetlib = TikiLib::lib('sheet');

        $beginRow = $sheet->getRangeBeginRow();
        $endRow = $sheet->getRangeEndRow();

        $beginCol = $sheet->getRangeBeginCol();
        $endCol = $sheet->getRangeEndCol();

        for ($i = $beginRow; $i < $endRow; $i++) {
            $trHeight = "20px";
            $td = '';
            for ($j = $beginCol; $j < $endCol; $j++) {
                $width = $height = "";
                extract($sheet->cellInfo[$i][$j]);
                $append = "";

                if (empty($width) || empty($height) || $width == 0 || $height == 0) {
                    continue;
                }

                if ($width > 1) {
                    $append .= " colspan='{$width}'";
                }

                if ($height > 1) {
                    $append .= " rowspan='{$height}'";
                }

                if (isset($sheet->dataGrid[$i][$j])) {
                    $data = $sheet->dataGrid[$i][$j];
                } else {
                    $data = '';
                }

                $format = $sheet->cellInfo[$i][$j]['format'];
                if (! empty($format)) {
                    $data = TikiSheetDataFormat::$format($data);
                }

                $style = $sheet->cellInfo[$i][$j]['style'];
                if (! empty($style)) {
                    $append .= " style='{$style}'";

                    $trHeight = $sheetlib->get_attr_from_css_string($style, "height", "20px");
                }

                $class = $sheet->cellInfo[$i][$j]['class'];
                if (! empty($class)) {
                    $append .= " class='{$class}'";
                }

                $td .= "            <td$append>$data</td>\n";
            }

            $tr = "        <tr  style='height: $trHeight;' height='$trHeight'><th>" . ($i + 1) . "</th>\n";
            $tr .= $td;
            $tr .= "    </tr>\n";

            $this->output .= $tr;
        }
    }

    // supports
    public function supports($type)
    {
        return ( ( TIKISHEET_SAVE_DATA | TIKISHEET_SAVE_CELL | TIKISHEET_SAVE_FORMAT ) & $type ) > 0;
    }

    // version
    public function version()
    {
        return "1.0";
    }
}

/** TikiSheetHTMLTableHandler
 * Class that imports a sheet from an HTML table
 * Designed to be used with jQuery.sheet.save_sheet
 */
class TikiSheetHTMLTableHandler extends TikiSheetDataHandler
{
    public $data;

    /** Constructor
     * Initializes the the serializer on a wiki page
     * @param $file The name of the wiki page to perform actions on.
     */
    public function __construct($json)
    {
        $this->data = $json;
    }

    // _load
    public function _load(TikiSheet &$sheet)
    {
        $d = $this->data;

        foreach ($d->metadata->widths as $c => $width) {
        }

        foreach ($d->rows as $r => $row) {
            foreach ($row->columns as $c => $column) {
                $sheet->initCell($r, $c);


                //if cell has formula, use it, otherwise, use value, if value if blank, use ''
                if (! empty($column->formula)) {
                    $sheet->setCalculation($column->formula);
                } else {
                    $sheet->setValue(isset($column->value) ? $column->value : '');
                }


                //Make cell able to span multi columns and rows
                $rowSpan = 1;
                $colSpan = 1;
                if (isset($column->rowspan)) {
                    $rowSpan = $column->rowspan;
                }
                if (isset($column->colspan)) {
                    $colSpan = $column->colspan;
                }
                $sheet->setRowSpan($rowSpan);
                $sheet->setColSpan($colSpan);
                $sheet->setDeadCells();


                //setup cell css style
                if (! empty($column->style)) {
                    $sheet->setStyle($column->style);
                }

                //setup cell html class
                if (isset($column->class)) {
                    $sheet->setClass($column->class);
                }
            }
        }

        return true;
    }

    // name
    public function name()
    {
        return tr("HTML Table");
    }

    // supports
    public function supports($type)
    {
        return ( TIKISHEET_LOAD_DATA & $type ) > 0;
    }

    // version
    public function version()
    {
        return "1.0";
    }
}
