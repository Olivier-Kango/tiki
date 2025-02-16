<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_fancytable_info()
{
    $tsOn = Table_Check::isEnabled();
    if ($tsOn === true) {
        $ts = new Table_Plugin();
        $ts->createParams();
        $tsparams = $ts->params;
        unset($tsparams['server']);
    } else {
        $tsparams = [];
    }
    $params = array_merge(
        [
             'head' => [
                 'required' => false,
                 'name' => tra('Heading Row'),
                 'description' => tr('Header rows of the table. Use %0 to separate multiple rows.', '<code>>></code>'),
                 'default' => '',
                 'since' => '1'
             ],
             'headclass' => [
                 'required' => false,
                 'name' => tra('Heading CSS Class'),
                 'description' => tra('CSS class to apply to the heading row.'),
                 'default' => '',
                 'since' => '1'
             ],
             'headaligns' => [
                 'required' => false,
                 'name' => tra('Header Horizontal Alignment'),
                 'description' => tr(
                     'Horizontal alignments for header cells, separated by %0. Choices: %1',
                     '<code>|</code>',
                     '<code>left</code>, <code>right</code>, <code>center</code>, <code>justify</code>'
                 ),
                 'default' => '',
                 'since' => '4.1',
                 'filter' => 'text',
             ],
             'headvaligns' => [
                 'required' => false,
                 'name' => tra('Header Vertical Alignment'),
                 'description' => tr(
                     'Vertical alignments for header cells, separated by %0". Choices: %1',
                     '<code>|</code>',
                     '<code>top</code>, <code>middle</code>, <code>bottom</code>, <code>baseline</code>'
                 ),
                 'default' => '',
                 'since' => '4.1',
                 'filter' => 'text',
             ],
             'colwidths' => [
                 'required' => false,
                 'name' => tra('Column Widths'),
                 'description' => tr('Column widths followed by "px" for pixels or "%" for percentages. Each column
                    separated by %0.', '<code>|</code>'),
                 'default' => '',
                 'since' => '4.1'
             ],
             'colaligns' => [
                 'required' => false,
                 'name' => tra('Cell Horizontal Align'),
                 'description' => tr(
                     'Table body column horizontal alignments, separated by %0. Choices: %1',
                     '<code>|</code>',
                     '<code>left</code>, <code>right</code>, <code>center</code>, <code>justify</code>'
                 ),
                 'default' => '',
                 'since' => '4.1',
                 'filter' => 'text',
             ],
             'colvaligns' => [
                 'required' => false,
                 'name' => tra('Cell Vertical Align'),
                 'description' => tr(
                     'Table body column vertical alignments, separated by %0. Choices: %1',
                     '<code>|</code>',
                     '<code>top</code>, <code>middle</code>, <code>bottom</code>, <code>baseline</code>'
                 ),
                 'default' => '',
                 'since' => '4.1',
                 'filter' => 'text',
             ],
             'allowStickyHeaders' => [
                 'name' => tr('Allow Sticky Headers'),
                 'description' => tr('Sticky Headers for the table when scrolling top') . ' ' . tr('Default value: No'),
                 'since' => '26',
                 'required' => false,
                 'filter' => 'alpha',
                 'default' => 'n',
                 'options' => [
                     ['text' => '', 'value' => ''],
                     ['text' => tra('No'), 'value' => 'n'],
                     ['text' => tra('Yes'), 'value' => 'y']
                 ]
             ]
        ],
        $tsparams
    );
    return [
        'searchable_by_default' => true,
        'name' => tra('Fancy Table'),
        'documentation' => 'PluginFancyTable',
        'description' => tra('Create a formatted table that can be filtered and sorted'),
        'prefs' => ['wikiplugin_fancytable'],
        'body' => tr(
            'Rows separated by %0 in the header; for the table body, one row per line. Cells separated by %1 (since Tiki4) or %2 in both cases.',
            '<code>>></code>',
            '<code>|</code>',
            '<code>~|~</code>'
        ),
        'iconname' => 'table',
        'introduced' => 1,
        'tags' => [ 'basic' ],
        'params' => $params,
    ];
}

function wikiplugin_fancytable($data, $params)
{
    global $prefs;

    // Initialize variables
    $tagremove = [];
    $pluginremove = [];
    static $iFancytable = 0;
    ++$iFancytable;
    extract($params, EXTR_SKIP);
    $msg = '';

    // Check for sortable
    $sort = isset($sortable) && $sortable != 'n';

    if ($sort && Table_Check::isEnabled()) {
        // If sortable and table plugin is enabled
        $ts = new Table_Plugin();
        $ts->setSettings(
            'wpfancytable' . $iFancytable,
            'n',
            $sortable,
            $sortList ?? null,
            $tsortcolumns ?? null,
            $tsfilters ?? null,
            $tsfilteroptions ?? null,
            $tspaginate ?? null,
            $tscolselect ?? null,
            null,
            null,
            $tstotals ?? null,
            $tstotalformat ?? null,
            $tstotaloptions ?? null
        );

        // Build table plugin
        if (is_array($ts->settings)) {
            $ts->settings['resizable'] = true;
            Table_Factory::build('plugin', $ts->settings);
        } else {
            $sort = false;
        }
    } else {
        $sort = false;
    }
    // Check if sort is disabled or failed to build
    if (! $sort) {
        if ($prefs['feature_jquery_tablesorter'] === 'n') {
            $msg = '<em>' . tra('The jQuery Sortable Tables feature must be activated for the sort feature to work.')
                . '</em>';
        } else {
            $msg = '<em>' . tra('Unable to load the jQuery Sortable Tables feature.') . '</em>';
        }
    }

    // Prepare table attributes
    $sticky = isset($allowStickyHeaders) && $allowStickyHeaders == 'y';
    $style = $sort ? ' style="visibility:hidden"' : '';
    $wret = '<div id="wpfancytable' . $iFancytable . '-div"' . $style
        . ' class="table-responsive ts-wrapperdiv ' . ($sticky ? 'table-sticky' : '') . '">' . "\r\t";
    $tableClass = 'table table-striped table-hover normal';
    if (! empty($colwidths)) {
        $tableClass .= ' fixed-layout';
    }
    $wret .= '<table class="' . $tableClass . '" id="wpfancytable' . $iFancytable . '">' . "\r\t";

    // Process header
    if (isset($head)) {
        $tdhdr = ! empty($headclass) ? "\r\t\t\t" . '<th class="' . $headclass . '"' : "\r\t\t\t<th";
        //replace tiki tags, plugins and other enclosing characters with hash strings before creating table so that any
        //pipes (| or ~|~) inside aren't mistaken for cell dividers
        if (TikiLib::lib('parser')->option['is_markdown']) {
            $head = TikiLib::lib('parser')->parse_data($head);
            $head = str_replace(['<p>', '</p>'], '', $head);
        }
        preprocess_section($head, $tagremove, $pluginremove);

        $type = $sort ? 'hs' : 'h';
        //now create header table rows
        $headrows = process_section(
            $head,
            $type,
            '>>',
            $tdhdr,
            '</th>',
            $colwidths ?? '',
            $headaligns ?? '',
            $headvaligns ?? ''
        );

        //restore original tags and plugin syntax
        $headhtml = $headrows['html'];
        postprocess_section($headhtml, $tagremove, $pluginremove);
        $wret .= '<thead class="' . ($sticky ? 'bg-light' : '') . '">' . $headhtml . "\r\t" . '</thead>' . "\r\t";
    }

    // Process body
    //replace tiki tags, plugins and other enclosing characters with hash strings before creating table so that any
    //pipes (| or ~|~) inside aren't mistaken for cell dividers
    preprocess_section($data, $tagremove, $pluginremove);
    //bs : sortable body rows - do not assign odd/even class to these since jquery will do it
    //b : plain body rows
    $type = $sort ? 'bs' : 'b';
    //now create table body rows
    $bodyrows = process_section(
        $data,
        $type,
        "\n",
        "\r\t\t\t" . '<td',
        '</td>',
        $colwidths ?? '',
        $colaligns ?? '',
        $colvaligns ?? ''
    );
    //restore original tags and plugin syntax
    $bodyhtml = $bodyrows['html'];
    postprocess_section($bodyhtml, $tagremove, $pluginremove);
    //end the tbody
    $wret .= '<tbody>' . $bodyhtml . "\r\t" . '</tbody>';
    // Add footer if available
    if (isset($ts->settings)) {
        $footer = Table_Totals::getTotalsHtml($ts->settings, $bodyrows['cols']);
        if ($footer) {
            $wret .= $footer;
        }
    }
    // Finalize table output
    $wret .= "\r" . '</table></div>' . "\r" . $msg;
    return $wret;
}

/**
 * To keep pipes (| or ~|~) inside other Tiki tags or plugins, or other "enclosing" characters from being mistaken as
 * cell dividers, replace them with hash strings until after the table is created
 *
 * @param       string          $data           Header or body text with tags and plugins removed
 * @param       array           $tagremove      Key => value pairs of Hash => data for tags or enclosing characters
 * @param       array           $pluginremove   Key => value pairs of Hash => data for plugins
 */
function preprocess_section(&$data, &$tagremove, &$pluginremove)
{
    $parserlib = TikiLib::lib('parser');
    // First replace plugins with hash strings since they may contain pipe characters
    $parserlib->plugins_remove($data, $pluginremove);
    // Then replace tags or other enclosing characters that could enclose a pipe (| or ~|~) character with a hash string
    $tikilib = TikiLib::lib('tiki');
    $tags = [
        [
            'start' => '\(\(',      // (( ))
            'end'   => '\)\)',
        ],
        [
            'start' => '\[',        // [ ]
            'end'   => '\]',
        ],
        [
            'start' => '~np~',      // ~np~ ~/np~
            'end'   => '~\/np~',
        ],
        [
            'start' => '~tc~',      // ~tc~ ~/tc~
            'end'   => '~\/tc~',
        ],
        [
            'start' => '~hc~',      // ~hc~ ~/hc~
            'end'   => '~\/hc~',
        ],
        [
            'start' => '\^',        // ^ ^
            'end'   => '\^',
        ],
        [
            'start' => '__',        // __ __
            'end'   => '__',
        ],
        [
            'start' => '\:\:',      // :: ::
            'end'   => '\:\:',
        ],
        [
            'start' => '\:\:\:',    // ::: :::
            'end'   => '\:\:\:',
        ],
        [
            'start' => '\'\'',      // '' ''
            'end'   => '\'\'',
        ],
        [
            'start' => '-\+',       // -+ +-
            'end'   => '\+-',
        ],
        [
            'start' => '-=',        // -= =-
            'end'   => '=-',
        ],
        [
            'start' => '===',       // === ===
            'end'   => '===',
        ],
        [
            'start' => '--',        // -- --
            'end'   => '--',
        ],
        [
            'start' => '\(',        // ( )
            'end'   => '\)',
        ],
        [
            'start' => '\"',        // " "
            'end'   => '\"',
        ],
    ];
    $count = count($tags) - 1;
    $pattern = '/(';
    foreach ($tags as $key => $tag) {
        $pattern .= $tag['start'] . '(?:(?!' . $tag['end'] . ').)*' . $tag['end'];
        if ($key < $count) {
            $pattern .= '|';
        }
    }
    $pattern .= ')/';
    preg_match_all($pattern, $data, $matches);
    foreach ($matches[0] as $match) {
        $tagremove['key'][] = '§' . md5($tikilib->genPass()) . '§';
        $tagremove['data'][] = $match;
        $data = isset($tagremove['data']) ? str_replace($tagremove['data'], $tagremove['key'], $data) : $data;
    }
}

/**
 * Process header or body string to convert to table rows
 *
 * @param       string          $data       Header or body string to be broken down into table rows
 * @param       string          $type       Indicates whether rows are sortable or not, which impacts class assigned
 * @param       string          $line_sep   Row separator (>> for header and \n for body)
 * @param       string          $cellbeg    HTML <th or <td tag and appropriate class attribute
 * @param       string          $cellend    HTML </th> or </td> ending tags
 * @param       numeric         $widths     User input for column widths
 * @param       string          $aligns     User input for horizontal text alignment within cells
 * @param       string          $valigns    User input for vertical text alignment within cells
 *
 * @return      string          $wret       HTML string for the header or body rows processed
 */
function process_section($data, $type, $line_sep, $cellbeg, $cellend, $widths, $aligns, $valigns)
{
    $separator = strpos($data, '~|~') === false ? '|' : '~|~';
    $lines = explode($line_sep, $data);
    $widths = ! empty($widths) ? explode('|', $widths) : '';
    $aligns = ! empty($aligns) ? explode('|', $aligns) : '';
    $valigns = ! empty($valigns) ? explode('|', $valigns) : '';
    $trbeg = "\r\t\t<tr>";
    $trend = "\r\t\t</tr>";
    $l = 0;
    $rnum1 = 0;
    $rnum2 = 0;
    $wret = '';
    $row_is_odd = true;
    $parts = [];

    //Each row
    foreach ($lines as $line) {
        $line = trim($line);
        if (strlen($line) > 0) {
            if ($type == 'b') {
            //  if ($row_is_odd) {
            //      $cellbeg = "\r\t\t\t" . '<td class="odd"';
            //      $row_is_odd = false;
            //  } else {
            //      $cellbeg = "\r\t\t\t" . '<td class="even"';
            //      $row_is_odd = true;
            //  }
                //don't set odd/even class if tablesorter is on because jquery will add it
                //and the classes won't alternate correctly if added here too
            } elseif ($type == 'bs') {
                $cellbeg = "\r\t\t\t" . '<td';
            }
            $c = 0;
            $row = '';
            $parts = explode($separator, $line);
            //Each column within a row
            foreach ($parts as $column) {
                $colnum = 'col' . $c;
                $colspan = '';
                $rowspan = '';
                /*
                 * Match / (colspan) or \ (rowspan) characters in whichever order at the beginning of the cell
                 * $matches[0][0] shows entire match. There are 3 strings being matched in the preg_match_all,
                 * so $matches[1][0] shows the character matched for the first string (\), $matches[2][0] the
                 * second character (/), and $matches[3][0] the third character (\)
                */
                if (preg_match_all("/^(\\\\)*(\/)*(\\\\)*/", $column, $matches)) {
                    $column = substr($column, strlen($matches[0][0]));
                    //create colspan if there are / characters at beginning of cell
                    if ($matches[2][0]) {
                        $colspan = ' colspan="' . substr_count($matches[0][0], $matches[2][0]) . '"';
                    }
                    //create rowspan if there are \ characters at beginning of cell
                    if ($matches[1][0] || $matches[3][0]) {
                        if ($matches[1][0]) {
                            $rnum1 = substr_count($matches[0][0], $matches[1][0]);
                        }
                        if ($matches[3][0]) {
                            $rnum2 = substr_count($matches[0][0], $matches[3][0]);
                        }
                        $rnum = $rnum1 + $rnum2;
                        $rowspan = ' rowspan="' . $rnum . '"';
                        //If there's another rowspan still in force, bump up the column number
                        if (isset(${$colnum}['col']) && ${$colnum}['col'] == $c) {
                            if ((${$colnum}['span'] - ($l - ${$colnum}['line'])) > 0) {
                                $c++;
                            }
                        }
                        //Note the info for this new rowspan
                        ${$colnum}['col'] = $c;
                        ${$colnum}['line'] = $l;
                        ${$colnum}['span'] = $rnum;
                    }
                }

                //set column style
                $colstyle = '';
                if (! empty($widths) || ! empty($aligns) || ! empty($valigns)) {
                    //If there's another rowspan still in force, bump up the column number
                    if (isset(${$colnum}['col']) && ${$colnum}['col'] == $c && ($l > ${$colnum}['line'])) {
                        if ((${$colnum}['span'] - ($l - ${$colnum}['line'])) > 0) {
                            $c++;
                        }
                    }
                    $colstyle = ' style="';
                    $colstyle .= ! empty($widths[$c]) ? ' width: ' . $widths[$c] . ';' : '';
                    $colstyle .= ! empty($aligns[$c]) ? ' text-align: ' . $aligns[$c] . ';' : '';
                    $colstyle .= ! empty($valigns[$c]) ? ' vertical-align: ' . $valigns[$c] : '';
                    $colstyle .= '"';
                }
                $row .= $cellbeg . $colspan . $rowspan . $colstyle . '>' . $column . $cellend;
                $c++;//increment column number
            }
            $wret .= $trbeg . $row . $trend;
        }
        $l++;//increment row number
    }
    $ret['html'] = $wret;
    $ret['cols'] = count($parts ?? []);
    return $ret;
}

/**
 * Replace hash strings with original tag and plugin content
 *
 * @param       string      $data           Header or body text processed into table rows with hash strings replacing
 *                                              tags and plugins
 * @param       array       $tagremove      Tag hash => data pairs for tags that were replaced with hash strings
 * @param       array       $pluginremove   Plugin Hash => data pairs for plugins that were replaced with hash strings
 */
function postprocess_section(&$data, &$tagremove, &$pluginremove)
{
    //first restore tag strings
    $parserlib = TikiLib::lib('parser');
    if (
        isset($tagremove['key']) and count($tagremove['key'])
        and count($tagremove['key']) == count($tagremove['data'])
    ) {
        $data = str_replace($tagremove['key'], $tagremove['data'], $data);
    }
    //then restore plugin strings
    $parserlib->plugins_replace($data, $pluginremove);
    //reset variables since this function is run for both the header and body
    $tagremove = [];
    $pluginremove = [];
}
