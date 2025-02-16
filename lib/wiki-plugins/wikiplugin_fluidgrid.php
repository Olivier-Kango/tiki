<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_fluidgrid_info()
{
    return [
        'name' => tra('Fluid Grid'),
        'documentation' => 'PluginFluidGrid',
        'description' => tra('Arrange content into rows and columns using a Bootstrap fluid grid.'),
        'prefs' => [ 'wikiplugin_fluidgrid' ],
        'body' => tra('Text to display in a grid. Use "---" to separate the columns and "@@@" to separate rows.'),
        'filter' => 'wikicontent',
        'format' => 'html',
        'iconname' => 'table',
        'introduced' => 17,
        'tags' => [ 'basic' ],
        'params' => [
            'joincols' => [
                'required' => false,
                'name' => tra('Join Columns'),
                'description' => tra('Merge empty cells into the cell to their left'),
                'since' => '17',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'devicesize' => [
                'required' => false,
                'name' => tra('Device size'),
                'description' => tra('Specify the device size below which the cells will be stacked vertically'),
                'since' => '17',
                'filter' => 'alpha',
                'default' => 'sm',
                'options' => [
                    ['text' => '',                 'value' => ''],
                    ['text' => tra('Small'),       'value' => 'sm'],
                    ['text' => tra('Medium'),      'value' => 'md'],
                    ['text' => tra('Large'),       'value' => 'lg'],
                    ['text' => tra('Extra Large'), 'value' => 'xl']
                ]
            ],
            'colsize' => [
                'required' => false,
                'name' => tra('Column Sizes'),
                'description' => tra('Specify all column widths in units which add up to 12 or percent, separating each width by a pipe (|)'),
                'since' => '17',
                'seprator' => '|',
                'filter' => 'text',
                'default' => '',
            ],
            'first' => [
                'required' => false,
                'name' => tra('First'),
                'description' => tra('Cells specified are ordered first left to right across rows (default) or top to bottom down columns'),
                'since' => '17',
                'filter' => 'alpha',
                'default' => 'line',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Column'), 'value' => 'col'],
                    ['text' => tra('Line'), 'value' => 'line']
                ]
            ],
            'customclass' => [
                'required' => false,
                'name' => tra('Custom Class'),
                'description' => tra('Add a class to customize the design'),
                'since' => '17',
                'filter' => 'text',
                'default' => '',
            ],
        ],
    ];
}

//
// The plugin function starts by removing a list of other plugins, nested
// inside this one. This function patches them back in.
//
function wikiplugin_fluidgrid_rollback($data, $hashes)
{
    foreach ($hashes as $hash => $match) {
        $data = str_replace($hash, $match, $data);
    }
    return $data;
}

/*
 * \note This plugin should carefuly change text it have to parse
 *       because some of wiki syntaxes are sensitive for
 *       start of new line ('\n' character - e.g. lists and headers)... such
 *       user lines must stay with the same layout when applying
 *       this plugin to render them properly after...
 *      $data = the preparsed data (plugin, code, np.... already parsed)
 *      $pos is the position in the object where the non-parsed data begins
 */
function wikiplugin_fluidgrid($data, $params, $pos)
{
    global $tikilib;

    //
    // The following function uses a regular expression in the form
    // "/pattern/ismU"
    // where / is used as a delimiter and ismU are pattern modifiers
    // i = case insensitive
    // s = dot matches anything (including new line)
    // m = multiline
    // U = Ungreedy
    //
    // The regular expression matches a list of specific plugins. The following
    // loop replaces the plugin with a hash, so that it is excluded from the
    // processing.
    //
    // Question:
    // Ungreedy matching prevents us spanning multiple instances of a plugin,
    // e.g. {SPIIT()}...{SPLIT}...{SPIIT()}...{SPLIT}
    // Will it handle a second level of nested plugins of the same type,
    // e.g. {SPIIT()}...{SPLIT()}...{SPIIT}...{SPLIT}
    //
    preg_match_all('/{(FLUIDGRID|SPLIT|CODE|HTML|FADE|JQ|JS|MOUSEOVER|VERSIONS).+{\1}/ismU', $data, $matches);
    $hashes = [];
    foreach ($matches[0] as $match) {
        if (empty($match)) {
            continue;
        }
        $hash = md5($match);
        $hashes[$hash] = $match;
        $data = str_replace($match, $hash, $data);
    }

    // Remove first <ENTER> if exists...
    // it may be here if present after {FLUIDGRID()} in original text
    if (substr($data, 0, 2) == "\r\n") {
        $data2 = substr($data, 2);
    } else {
        $data2 = $data;
    }

        extract($params, EXTR_SKIP);
    $joincols  = (! isset($joincols)  || $joincols == 'y' || $joincols == 1 ? true : false);

        // Check the device size parameter which must be one of 'sm', 'md', 'lg' or 'xl'
    if (! isset($devicesize) || ! ( ( $devicesize == 'sm' ) || ( $devicesize == 'md' ) || ( $devicesize == 'lg' ) || ( $devicesize == 'xl' ) )) {
        $devicesize = 'sm' ;
    }

        // Split data by rows and cells
    $sections = preg_split("/@@@+/", $data2);
    $rows = [];
    $maxcols = 0;
    foreach ($sections as $i) {
        // split by --- but not by ----
        //  $rows[] = preg_split("/([^\-]---[^\-]|^---[^\-]|[^\-]---$|^---$)+/", $i);
        //  not to eat the character close to - and to split on --- and not ----
        $rows[] = preg_split("/(?<!-)---(?!-)/", $i);
        $maxcols = max($maxcols, count(end($rows)));
    }

    // Are there any sections present?
    // Do not touch anything if not... don't even generate <table>
    if (count($rows) <= 1 && count($rows[0]) <= 1) {
        return wikiplugin_fluidgrid_rollback($data, $hashes);
    }

           //
    // The "first" parameter indicates whether the content is listed in columns
    // or in rows (aka. lines).
    //
    // I doubt whether columm mode is very useful, but I intend to support it.
    //
    // The original SPLIT plugin generates a normal table with rows and cells
    // for line mode, but handles column mode with a single row. The separate
    // rows in each column are defined with divs. This is imho Not good enough.
    //
    // Because I think it is an exotic case, I will handle column mode by
    // flipping the matrices before generating the table. This is probably
    // not very efficient, but keeps the code fairly readable.
    //
    if (isset($first) && $first == 'col') {
        $cols    = [] ;
        $maxrows = count($rows) ;

        for ($i = 0; $i < $maxcols; $i++) {
            $cols[] = [] ;
        }
        foreach ($rows as $r) {
            for ($i = 0; $i < $maxcols; $i++) {
                if ($i < count($r)) {
                    $cols[$i][] = $r[$i] ;
                } else {
                    $cols[$i][] = '' ;
                }
            }
        }

              $rows = $cols ;
        $maxcols = $maxrows ;
    }

    // The bootstrap fluid grid can handle a maximum of 12 colums.
    // Check this AFTER flipping the axis for column mode.
    if ($maxcols > 12) {
        return ( "<b>Fluid Grid can have a maximum of 12 columns</b><br/>") ;
    }

          // Handle the column widths.
    //
    // There are several cases:
    // (not in the order in which they must be handled)
    //
    // (1) Colsize is not present
    //     - Share out the space as evenly as possible
    //
    // (2) Colsize is present
    //     It specifies the size of all columns
    //     The total size is <= 12
    //     - Assign exactly the specified sizes
    //
    // (3) Colsize is present
    //     It does not specifiy the size of all columns
    //     The total size plus the number of unsized columns is <= 12
    //     - Assign the specified sizes and share out the remaining
    //       units among the unsized columns
    //
    // The remaining cases are for some degree of compatibility with the
    // SPLIT plugin.
    //
    // (4) Colsize is present
    //     It specifies the size of all columns in PIXELS
    //     The total size is > 12
    //     - Use the size as an approximate weighting
    //
    // (5) Colsize is present
    //     It specifies the size of some but not all columns in PIXELS
    //     The total size is > 12
    //     - Use the size as an approximate weighting, with a minimum size of 1.
    //
    // (6) Colsize is present
    //     All columns are specified in PERCENT.
    //     - Use the size as an approximate weighting, relative to 100, with a
    //       minimum size of 1.
    //     - The total can be less than 12, e.g. two columns with 25%|25%
    //       should translate to 3|3 and not 6|6
    //
    // (7) Colsize is present
    //     Some columns are specified in PERCENT, the rest are not specified.
    //     - Use the size as an approximate weighting, relative to 100, with a
    //       minimum size of 1.
    //     - Columns with an unspecified width should fill up remaining space,
    //       e.g. 3 columns with 25%|25% should translate to 3|3|6
    //
    // (8) Colsize is present
    //     Some columns are specified in PERCENT, some are specified in PIXELS.
    //     - Ingore the pixel values. Handle as above.
    //       (I don't have a good idea how to handle this case!)
    //

    // We will store the final widths in this array
    $w_array = [] ;

    // colsize is specified
    if (isset($colsize)) {
        // Check for a percent symbol on any column
        $percent = ( strpos($colsize, '%') !== false );

        // Count the total size and the number of unsized columns
        $tdsize   = explode("|", $colsize);
        $tdtotal  = 0 ;
        $tdtotalPercent  = 0 ;
        $tdnosize = 0 ;
        $s_array  = [] ;

        //PERCENT MODE
        if ($percent) {


            /*******************
             * (6) Colsize is present
             *   All columns are specified in PERCENT.
             *      - Use the size as an approximate weighting, relative to 100, with a minimum size of 1.
             *        In percentage mode, the total wighting is always 100
            **/

            // There are two parts to this algorithm:
            // [1] Gathering information
            // [2] Setting the final column sizes

            // [1] Gathering information
            // In this stage we read the colsize values and initialize
            // $s_array   = colsize values
            // $tdtotal   = total weighting (Normal mode)
            // $tdtotalPercent   = total weighting in percent
            // $tdnosize  = count of columns without a specified size
            for ($i = 0; $i < $maxcols; $i++) {
                if (isset($tdsize[$i]) && ( trim($tdsize[$i]) != '' )) {
                    $isPercentCol = ( strpos($tdsize[$i], '%') !== false );
                    $w = abs((int) trim($tdsize[$i])); //The size must always be positive

                    if ($isPercentCol) {
                        // Percentage mode. Save the width and increment the total percent size ($tdtotalPercent).
                        $s_array[$i] = $tdsize[$i] ;
                        $tdtotalPercent += $w ;
                    } else {
                        // Normal case. Save the width and increment the total normal size ($tdtotal).
                        $s_array[$i] = $w ;
                        $tdtotal += $w ;
                    }
                } else {
                    // Size not specified for this column.
                    $s_array[$i] = 0 ;
                    $tdnosize++ ;
                }
            }

            // [2] Setting the final column sizes
            if ($tdtotal == 0) {
                // [2.1] We first handle the case where all the cols are in percent mode
                if (( $tdtotalPercent + $tdnosize ) <= 100) {
                    // Remaining value to distribute to columns without specified size
                    $remaining = 100 - $tdtotalPercent;
                    for ($i = 0; $i < $maxcols; $i++) {
                        if ($s_array[$i] == 0) {
                            $w_array[$i] = ceil(round(($remaining / $tdnosize * 12) / 100));
                            $remaining   -= ceil($remaining / $tdnosize) ;
                            $tdnosize-- ;
                        } else {
                            $w_array[$i] = round(( abs((int) trim($s_array[$i])) * 12) / 100);
                        }
                    }
                } else {
                    return "<b class='text-danger'>" . tra("Fluidgrid plugin: ERROR: The values set in the colsize parameter are greater than 100% of the width as expected when you set the values in percentage") . "</b>";
                }
            } else {
                // [2.2] Handle the case where some cols are in percent mode and others in normal mode
                $totalWidthInPercentage = $tdtotalPercent + ($tdtotal * (100 / 12)) + $tdnosize;
                if ($totalWidthInPercentage <= 100) {
                    // Remaining value to distribute to columns without specified size
                    $remaining = 100 - $tdtotalPercent - ($tdtotal * (100 / 12));
                    for ($i = 0; $i < $maxcols; $i++) {
                        $isValuePercent = ( strpos($s_array[$i], '%') !== false );
                        if ($s_array[$i] == 0) {
                            // No size specified
                            $w_array[$i] = ceil(round(($remaining / $tdnosize * 12) / 100)) ;
                            $remaining -= ceil($remaining / $tdnosize) ;
                            $tdnosize-- ;
                        } elseif ($isValuePercent) {
                            // Size specified in percent mode
                            $w_array[$i] = round(( abs((int) trim($s_array[$i])) * 12) / 100) ;
                        } else {
                            // Size specified in normal mode
                            $w_array[$i] = $s_array[$i] ;
                        }
                    }
                } else {
                    return "<b class='text-danger'>" . tra("Fluidgrid plugin: ERROR: The values set in the colsize parameter are greater than 100% of the width as expected when you set the values in percentage") . "</b>";
                }
            }
        } else {
            // There are two parts to this algorithm:
            // [1] Gathering information
            // [2] Setting the final column sizes

            // [1] Gathering information
            // In this stage we read the colsize values and initialize
            // $s_array   = colsize values
            // $tdtotal   = total weighting
            // $tdnosize  = count of columns without a specified size
            for ($i = 0; $i < $maxcols; $i++) {
                if (isset($tdsize[$i]) && ( trim($tdsize[$i]) != '' )) {
                    $w = (int) trim($tdsize[$i]);
                    if ($w < 1) {
                    // treat 0 as unsized
                        $s_array[$i] = 0 ;
                        $tdnosize++ ;
                    } else {
                    // Normal case. Save the width and increment the total.
                        $s_array[$i] = $w ;
                        $tdtotal     += $w ;
                    }
                } else {
                // Size not specified for this column.
                    $s_array[$i] = 0 ;
                    $tdnosize++ ;
                }
            }

            // [2] Setting the final column sizes
            // In this stage we store the final column sizes in $w_array
            if (( $tdtotal + $tdnosize ) <= 12) {
                // Use the values as specified.
                // Share the remaining space out among the unsized columns
                $remaining = 12 - $tdtotal ;

                for ($i = 0; $i < $maxcols; $i++) {
                    if ($s_array[$i] == 0) {
                        $w_array[$i] = ceil($remaining / $tdnosize) ;
                        $remaining   -= $w_array[$i] ;
                        $tdnosize-- ;
                    } else {
                        $w_array[$i] = $s_array[$i] ;
                    }
                }
            } else {
                // Use the values as approximate weightings
                // Start by assigning every column a width of 1
                for ($i = 0; $i < $maxcols; $i++) {
                    $w_array[$i] = 1 ;
                }

                // Now share out the rest
                $i = 0 ;
                $j = $maxcols ;
                $h = 0 ;
                $pcfill = true ;

                while ($j < 12) {
                // Increment the width if it is underweight
                    if (( $w_array[$i] / 12 ) < ( $s_array[$i] / $tdtotal )) {
                        $w_array[$i]++ ;
                        $j++ ;
                    }

                    // Increment column number and wraparound
                    $i++ ;
                    if ($i >= $maxcols) {
                        // Wraparound
                        $i = 0 ;

                                // $j must increase in each pass through the columns.
                        if ($h < $j) {
                            // Store the current position
                            $h = $j ;
                        } elseif ($pcfill) {
                            // In percentage mode, change 0% weighted columns to 100%
                            // so that they take up the remaining space.
                            $pcfill = false ;
                            for ($k = 0; $k < $maxcols; $k++) {
                                if ($s_array[$k] == 0) {
                                    $s_array[$k] = 100 ;
                                }
                            }
                        } else {
                            // We get here in percentage mode, if the size is specified for
                            // all columns, but the total is less than 100%, e.g. two columns
                            // with 25%|25%, will result in 3|3.
                            break ;
                        }
                    }
                }
            }
        }
    } else {
      // colsize is not specified
      // Share out the 12 units
        $remaining = 12  ;
        for ($i = 0; $i < $maxcols; $i++) {
          // Share among the remaining columns.
          // Round up as long as there is a remainder.
          // Eventually it will be an integer.
            $w_array[$i] = ceil($remaining / ($maxcols - $i)) ;
            $remaining   -= $w_array[$i] ;
        }
    }

    //$result = "<div class='container-fluid" . ( !empty($customclass) ? " $customclass" : "") . "'>" ;
    $result = "<div" . ( ! empty($customclass) ? " class='$customclass'" : "") . ">" ;

    foreach ($rows as $r) {
      // Start of the row
        $result .= "<div class='row'>" ;

        $j = 0 ;
        while ($j < $maxcols) {
          // Get the column width
            $w = $w_array[$j] ;

                  // Get the content
            $c = ( isset($r[$j]) ) ? $r[$j] : "" ;

            if ($joincols) {
            // Check for empty columns to the right
                for ($k = $j + 1; $k < $maxcols; $k++) {
                    if (isset($r[$k]) && ( trim($r[$k]) != '' )) {
                        break ;
                    } else {
                      // Grab the space from the next column and skip it
                        $j = $k ;
                        $w += $w_array[$j] ;
                    }
                }
            }

                  // My current understanding is as follows.
          // If you specify 'format' => 'html', then you must call
          //    TikiLib::lib('parser')->parse_data()
          //    to process the wiki syntax in the body text.
          // If you specify 'format' => 'wiki', then the returned text will
          //    be parsed as wiki text, but this is potentially dangerous,
          //    because it is a mixture of html and wiki syntax.
            $c = trim($c);
            $c = wikiplugin_fluidgrid_rollback($c, $hashes);
            $c = TikiLib::lib('parser')->parse_data($c);
            $result .= "<div class='col-" . $devicesize . "-" . $w . "'>" . $c . "</div>" ;

                  // Increment the column number (because we are using while, not for)
            $j++ ;
        }

            // End of the row
        $result .= "</div>";
    }

    // Close HTML table (no \n at end!)
    $result .= "</div>";

    return $result;
}
