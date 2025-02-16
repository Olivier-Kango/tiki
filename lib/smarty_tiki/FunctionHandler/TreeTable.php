<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * Tree Table Smarty func - smarty_function_treetable()
 * Renders a tree table (for use with http://plugins.jquery.com/project/treeTable)
 *
 * Params
 *
 * _data    :   array of data rows   -  e.g . with perms for now
 *
 *  array(
 *      array('permName'=>'tiki_p_admin_newsletters', 'permDesc' => 'Can admin newsletters', 'level' => 'admin', 'type' => 'newsletters' etc...),
 *      array('permName'=>'tiki_p_blahblah', etc...),
 *  ...)
 *
 * _columns : array of columns and headers array('permName' => tra('Permission Name'), 'permDesc' => tra('Permission Description'), etc
 *              or a string like: '"permName"="Permission Name", "permDesc"="Description", etc'
 *              if undefined it tries to guess (?)
 *
 * _valueColumnIndex = 0    :   index (or name) of the col in the _data array above to use as the unique index
 *
 * _sortColumn = ''         :   column to organise tree by (actually row key = e.g. 'type')
 *
 * _sortColumnDelimiter = '':   if set (e.g. to ',') sorting will be nested accoding to this delimiter
 *                              e.g. if the _sortColumn value is 'gran-parent, parent, child' the 'child' section will be nested 3 levels deep
 *
 * _checkbox = ''           :   name of checkbox (auto-incrementing) - no checkboxes if not set
 *                              if comma delimited list (or array) then makes multiple checkboxes
 *
 * _checkboxColumnIndex = 0 :   index (or name) of the col in the _data array above to use as the checkbox value
 *                              comma delimeted list (or array - of ints) for multiple checkboxes as set above
 *                              if set needs to match number of checkboxes defines in _checkbox (or if not set uses 0, 1, 2 etc)
 *
 * _checkboxTitles = ''     :   Comma delimited list (or array) of header titles for checkboxes (optional, but needs to match number of checkboxes above)
 *
 * _checkboxTooltips = ''   :   Defaults to _checkboxTitles. Columns get joined together using ' : '
 *
 * _checkboxTooltipFormat = '' : tr() type formatting string to format the array defined for each checkbox by _checkboxTooltips above
 *
 * _listFilter = 'y'        :   include dynamic text filter
 *
 * _filterMinRows = 12      :   don't show filter box if less than this number of rows
 *
 * _collapseMaxSections = 4 :   collapse tree sections of more than this number of sections showing on page load
 *
 * class = 'treeTable'      :   class of the table - will add 'sortable' if feature_jquery_sortable = y
 * id = 'treetable1'        :   id of the table (auto-incrementing)
 *
 * _rowClasses = array('odd','even')    :   classes to cycle through for rows (tr's and td's)
 *                                          can be a string for same class on each row
 *                                          or empty string for not
 *
 * _columnsContainHtml = 'n':   Column data gets html encoded (by default)
 *
 * _emptyDataMessage = tra('No rows found') : message if there are no rows
 *
 * _openall                 : show folder button to open all areas (y/n default=n)
 *
 * _showSelected            : checkbox to show only selected (y/n default=n)
 *
 * _selectAllHiddenToo = 'n': select all checkbox incudes hidden rows
 */
/**
 * @param array $params
 * @param Smarty\Template $smarty
 * @return string html output
 */
class TreeTable extends Base
{
    public function handle($params, Template $template)
    {
        global $tree_table_id, $prefs;
        $headerlib = \TikiLib::lib('header');

        extract($params);

        $_emptyDataMessage = empty($_emptyDataMessage) ? tra('No rows found') : $_emptyDataMessage;
        if (empty($_data)) {
            return $_emptyDataMessage;
        }

        $_checkbox = empty($_checkbox) ? '' : $_checkbox;
        $_checkboxTitles = empty($_checkboxTitles) ? '' : $_checkboxTitles;
        $_openall = isset($_openall) ? $_openall : 'n';
        $_showSelected = isset($_showSelected) ? $_showSelected : 'n';
        $_selectAllHiddenToo = isset($_selectAllHiddenToo) ? $_selectAllHiddenToo : 'n';
        $_checkboxColumnIndex = empty($_checkboxColumnIndex) ? 0 : $_checkboxColumnIndex;
        $_valueColumnIndex = empty($_valueColumnIndex) ? 0 : $_valueColumnIndex;

        if (is_string($_checkbox) && strpos($_checkbox, ',') !== false) {
            $_checkbox = preg_split('/,/', trim($_checkbox));
        }

        if (! empty($_checkbox) && ! is_array($_checkbox)) {
            $_checkbox = [$_checkbox];
            $_checkboxColumnIndex = [$_checkboxColumnIndex];
        }

        if (! empty($_checkboxColumnIndex)) {
            if (is_string($_checkboxColumnIndex) && strpos($_checkboxColumnIndex, ',') !== false) {
                $_checkboxColumnIndex = preg_split('/,/', trim($_checkboxColumnIndex));
            }
            if (count($_checkbox) != count($_checkboxColumnIndex)) {
                return 'Number of items in _checkboxColumnIndex doesn not match items in _checkbox';
            }
        }
        if (! empty($_checkboxTitles)) {
            if (is_string($_checkboxTitles)) {
                if (strpos($_checkboxTitles, ',') !== false) {
                    $_checkboxTitles = preg_split('/,/', trim($_checkboxTitles));
                } else {
                    $_checkboxTitles = [trim($_checkboxTitles)];
                }
            }
            if (count($_checkbox) != count($_checkboxTitles)) {
                return 'Number of items in _checkboxTitles doesn not match items in _checkbox';
            }
        }

        $_columnsContainHtml = isset($_columnsContainHtml) ? $_columnsContainHtml : 'n';

        $html = '';
        $nl = "\n";

        // some defaults
        $_listFilter = empty($_listFilter) ? 'y' : $_listFilter;
        $_filterMinRows = empty($_filterMinRows) ? 12 : $_filterMinRows;
        $_collapseMaxSections = empty($_collapseMaxSections) ? 4 : $_collapseMaxSections;

        $_rowClasses = ! isset($_rowClasses) ? ['odd', 'even'] : (is_array($_rowClasses) ? $_rowClasses : [$_rowClasses]);

        if (! empty($_rowClasses)) {
            $oddEvenCounter = 0;
        } else {
            $oddEvenCounter = -1;
        }

        // auto-increment val for unique id's etc
        if (empty($id)) {
            if (! isset($tree_table_id)) {
                $tree_table_id = 1;
            } else {
                $tree_table_id++;
            }
            $id = 'treetable_' . $tree_table_id;
        }
        // TODO - check this? add key/val pairs?
        if (empty($_columns)) {
            $keys = array_keys($_data[0]);
            $_columns = [];
            foreach ($keys as $key) {
                if (! is_numeric($key)) {
                    $_columns[$key] = htmlspecialchars($key);
                }
            }
        } elseif (is_string($_columns)) {
            $ar = preg_split('/,/', $_columns);
            $_columns = [];
            foreach ($ar as $str) {
                $ar2 = preg_split('/=/', trim($str));
                $_columns[trim($ar2[0], ' "')] = trim($ar2[1], ' "');
            }
            unset($ar, $ar2);
        }

        $_sortColumn = empty($_sortColumn) ? '' : $_sortColumn;
        $_groupColumn = empty($_groupColumn) ? '' : $_groupColumn;

        if ($_sortColumn) {
            $this->sort2d($_data, $_sortColumn);
        } elseif ($_groupColumn) {
            $this->sort2d($_data, $_groupColumn, false);
            $_sortColumn = $_groupColumn;
        }

        $class = empty($class) ? 'table table-striped' : $class;    // treetable

        /*
    if ($prefs['feature_jquery_tablesorter'] == 'y' && strpos($class, 'sortable') === false) {
         //$class .= ' sortable';
    }
*/

        if ($_listFilter == 'y' && count($_data) > $_filterMinRows) {
            $html .= smarty_function_listfilter(
                [
                    'id' => $id . '_filter',
                    'selectors' => "#$id tbody tr",
                    'parentSelector' => "#$id .collapsed:not(\".leaf\"), #$id .expanded:not(\".leaf\")",
                    'exclude' => ""
                ],
                $template
            );
        }

        if ($_openall == 'y') {
            $html .= '&nbsp;<label id="' . $id . '_openall" style="cursor:pointer">'
                . smarty_function_icon(
                    [
                        'name' => 'file-archive',
                    ],
                    $template
                )
                . smarty_function_icon(
                    [
                        'name' => 'file-archive-open',
                        'istyle' => 'display:none'
                    ],
                    $template
                )
                . ' ' . tra('Toggle sections') . '</label>';

            $headerlib->add_jq_onready(
                '
$("#' . $id . '_openall").on("click", function () {
    $this = $(this).tikiModal(" ");
    var visible = $(this).find(".icon:visible")
    if ($(visible).hasClass("icon-file-archive-open")) {

        $(".expanded .indenter", "#' . $id . '").eachAsync({
            delay: 20,
            bulk: 0,
            loop: function () {
                $(this).trigger("click");
            },
            end: function ()  {
                $this.tikiModal();
            }
        });
        $(this).find(".icon-file-archive-open").hide();
        $(this).find(".icon-file-archive").show();
    } else {
        $(".collapsed .indenter", "#' . $id . '").eachAsync({
            delay: 20,
            bulk: 0,
            loop: function () {
                $(this).trigger("click");
            },
            end: function ()  {
                $this.tikiModal();
            }
        });
        $(this).find(".icon-file-archive").hide();
        $(this).find(".icon-file-archive-open").show();
    }
    return false;
});'
            );
        }

        if ($_showSelected == 'y') {
            $html .= ' <input type="checkbox" id="' . $id . '_showSelected" title="' . tra('Show only selected') . '" />';
            $html .= ' <label for="' . $id . '_showSelected">' . tra('Show only selected') . '</label>';

            $headerlib->add_jq_onready(
                '
$("#' . $id . '_showSelected").on("click", function () {
    if (!$(this).prop("checked")) {
        $("#treetable_1 tr td.checkBoxCell input:checkbox").parent().parent().show()
    } else {
        $("#treetable_1 tr td.checkBoxCell input:checkbox").parent().parent().hide()
        $("#treetable_1 tr td.checkBoxCell input:checked").parent().parent().show()
    }
});'
            );
        }

        // start writing the table
        $html .= $nl . '<table id="' . $id . '" class="' . $class . ' position-relative">' . $nl;

        // write the table header
        $html .= '<thead><tr>';
        if (! empty($_checkbox)) {
            for ($i = 0, $icount_checkbox = count($_checkbox); $i < $icount_checkbox; $i++) {
                $html .= '<th class="checkBoxHeader">';
                $html .= smarty_function_select_all(
                    [
                        'checkbox_names' => [$_checkbox[$i] . '[]'],
                        'label' => empty($_checkboxTitles) ? '' : htmlspecialchars(tra($_checkboxTitles[$i])),
                        'hidden_too' => $_selectAllHiddenToo,
                    ],
                    $template
                );
                $html .= '</th>';
            }
        }

        foreach ($_columns as $column => $columnName) {
            $html .= '<th>';
            $html .= htmlspecialchars($columnName);
            $html .= '</th>';
        }
        $html .= '</tr></thead>' . $nl;
        $html .= '<tbody>' . $nl;

        $treeSectionsAdded = [];
        $rowCounter = 1;

        // for each row
        foreach ($_data as &$row) {
            // set up tree hierarchy
            if ($_sortColumn) {
                $treeType = htmlspecialchars(trim($row[$_sortColumn]));
                $childRowClass = '';

                if (! empty($_sortColumnDelimiter)) {   // nested
                    $parts = array_reverse(explode($_sortColumnDelimiter, $treeType));

                    for ($i = 0, $icount_parts = count($parts); $i < $icount_parts; $i++) {
                        $part = preg_replace('/\s+/', '_', $parts[$i]);
                        if (in_array($part, $treeSectionsAdded) && $i > 0) {
                            $treeParentId = preg_replace('/\s+/', '_', $parts[$i]);
                            $tt_parent_id = $id . '_' . $treeParentId;
                            break;
                        } else {
                            $tt_parent_id = '';
                        }
                    }

                    $treeTypeId = preg_replace('/\s+/', '_', $parts[0]);
                    $tt_id = $id . '_' . $treeTypeId;

                    $treeSectionsAdded[] = $treeTypeId;
                } else {
                    $treeTypeId = preg_replace('/\s+/', '_', $treeType);
                    $tt_parent_id = $id . '_' . $treeTypeId;
                    $tt_id = 'child_of_' . $id . '_' . $treeTypeId . '_' . $oddEvenCounter;

                    if (! empty($treeType) && ! in_array($treeTypeId, $treeSectionsAdded)) {
                        if (! is_array($_columns) && ! is_object($_columns)) {
                            $_columns = [];
                        }
                        if (! is_array($_checkbox) && ! is_object($_checkbox)) {
                            $_checkbox = [];
                        }
                        $html .= '<tr data-tt-id="' . $tt_parent_id . '"><td colspan="' . (count($_columns) + count($_checkbox)) . '">';
                        $html .= $treeType . '</td></tr>' . $nl;

                        // Courtesy message to help category perms configurators
                        if ($treeType == 'category') {
                            $html .= '<tr class="' . $childRowClass . '" data-tt-parent-id="' . $tt_parent_id . '" data-tt-id="cat_subHeader_' . $rowCounter . '">' .
                                '<td colspan="' . (count($_columns) + count($_checkbox)) . '">';
                            $html .= '<em>' . tra('You might want to also set the tiki_p_modify_object_categories permission under the tiki section') . '</em></td></tr>' . $nl;
                        }
                        $treeSectionsAdded[] = $treeTypeId;

                        // write a sub-header
                        $html .= '<tr data-tt-id="subHeader_' . $rowCounter . '" data-tt-parent-id="' . $tt_parent_id . '" class="subHeader' . $childRowClass . '">';
                        if (! empty($_checkbox)) {
                            for ($i = 0, $icount_checkbox = count($_checkbox); $i < $icount_checkbox; $i++) {
                                $html .= '<td class="checkBoxHeader"><span class="checkBoxLabel tips perms-titles" title=":' . htmlspecialchars(tra($_checkboxTitles[$i])) . '">';
                                $html .= empty($_checkboxTitles) ? '' : htmlspecialchars(tra($_checkboxTitles[$i]));
                                $html .= '</span></td>';
                            }
                        }
                        foreach ($_columns as $column => $columnName) {
                            $html .= '<td>';
                            $html .= htmlspecialchars($columnName);
                            $html .= '</td>';
                        }
                        $html .= '</tr>' . $nl;
                    }
                }
            } else {
                $childRowClass = '';
                $tt_parent_id = '';
                $tt_id = '';
            }

            // work out row class (odd/even etc)
            if ($oddEvenCounter > -1) {
                $rowClass = $_rowClasses[$oddEvenCounter % 2] . $childRowClass;
                $oddEvenCounter++;
            } else {
                $rowClass = $childRowClass;
            }

            $html .= '<tr data-tt-id="' . $tt_id . '"' .
                (! empty($tt_parent_id) ? ' data-tt-parent-id="' . $tt_parent_id . '"' : '') .
                ' class="' . $rowClass . '">';
            // add the checkbox
            if (! empty($_checkbox)) {
                for ($i = 0, $icount_checkbox = count($_checkbox); $i < $icount_checkbox; $i++) {
                    // get checkbox's "value"
                    $cbxVal = htmlspecialchars($row[$_checkboxColumnIndex[$i]]);
                    $rowVal = htmlspecialchars($row[$_valueColumnIndex]);

                    if (empty($_checkboxTooltips)) {
                        $cbxTit = empty($_checkboxTitles) ? $cbxVal : htmlspecialchars($_checkboxTitles[$i]);
                    } else {
                        $cbxTit = [];
                        foreach ($_checkboxTooltips as $col) {
                            if (isset($row[$col])) {
                                $cbxTit[] = tra($row[$col]);
                            } elseif ($col = '_checkboxTitles') {
                                $cbxTit[] = tra($_checkboxTitles[$i]);
                            }
                        }
                        if (empty($_checkboxTooltipFormat)) {
                            $cbxTit = htmlspecialchars(implode(' ', $cbxTit));
                        } else {
                            $cbxTit = htmlspecialchars(tra($_checkboxTooltipFormat, '', false, $cbxTit));
                        }
                    }

                    $html .= '<td class="checkBoxCell" style="white-space: nowrap;">';
                    $html .= '<input type="checkbox" class="form-check-input" name="' . htmlspecialchars($_checkbox[$i]) . '[]" value="' . $rowVal . '"' .
                        ($cbxVal == 'y' ? ' checked="checked"' : '') . ' title="' . $cbxTit . '" />';
                    if ($cbxVal == 'y') {
                        $html .= '<input type="hidden" name="old_' . htmlspecialchars($_checkbox[$i]) . '[]" value="' . $rowVal . '" />';
                    }
                    $html .= '</td>';
                }
            }

            foreach ($_columns as $column => $columnName) {
                $html .= '<td>';
                if ($_columnsContainHtml != 'y') {
                    $html .= htmlspecialchars($row[$column]);
                } else {
                    $html .= $row[$column];
                }
                $html .= '</td>' . $nl;
            }
            $html .= '</tr>' . $nl;
            $rowCounter++;
        }
        $html .= '</tbody></table>' . $nl;

        // add jq code to initial treetable
        $expanable = empty($_sortColumnDelimiter) ? 'true' : 'false';   // when nested, clickableNodeNames is really annoying
        if (count($treeSectionsAdded) < $_collapseMaxSections) {
            $headerlib->add_jq_onready('$("#' . $id . '").treetable({clickableNodeNames:' . $expanable . ',initialState: "expanded", expandable:true});');
        } else {
            $headerlib->add_jq_onready('$("#' . $id . '").treetable({clickableNodeNames:' . $expanable . ',initialState: "collapsed", expandable:true});');
        }
        // TODO refilter when .parent is opened - seems to prevent the click propagating
        //      $headerlib->add_jq_onready('$("tr.parent").on("click", function(event) {
        //if ($("#'.$id.'_filter").val()) {
        //  $("#'.$id.'_filter").trigger("keyup");
        //  if (event.isPropagationStopped() || event.isImmediatePropagationStopped()) {
        //      $(this).trigger("click");
        //  }
        //}
        //      });');

        return $html;
    }

    // TODO - move this somewhere sensible
    // from http://uk.php.net/sort?

    // $sort used as variable function--can be natcasesort, for example
    // WARNING: $sort must be associative
    private function sort2d(&$arrIn, $index = null, $sort = 'asort')
    {
        // pseudo-secure--never allow user input into $sort
        $arrTemp = [];
        $arrOut = [];
        foreach ($arrIn as $key => $value) {
            $arrTemp[$key] = is_null($index) ? reset($value) : $value[$index];
        }

        if ($sort) {
            if (strpos($sort, 'sort') === false) {
                $sort = 'asort';
            }
            $sort($arrTemp);
        }

        foreach ($arrTemp as $key => $value) {
            $arrOut[$key] = $arrIn[$key];
        }
        $arrIn = $arrOut;
    }
}
