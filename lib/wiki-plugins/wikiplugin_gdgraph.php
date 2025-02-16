<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// plugin that uses lib/graph-engine/ to produce simple bar charts on screen
// Usage
// {GDGRAPH(various parameters)}
//  x,y data
// {GDGRAPH}


function wikiplugin_gdgraph_info()
{
    return [
        'name' => tra('GDGraph'),
        'documentation' => 'PluginGDGraph',
        'description' => tra('Create a simple bar chart from supplied data'),
        'tags' => ['basic'],
        'prefs' => ['wikiplugin_gdgraph'],
        'body' => tra('Comma-separated data (x,y) to be graphed. A useful option is to generate this data from a LIST
            or CUSTOMSEARCH using a .tpl template or trackerlist plugin placed in the body'),
        'iconname' => 'chart',
        'format' => 'html',
        'introduced' => tra('14, backported to 12.4'),
        'params' => [
            'type' => [
                'required' => true,
                'name' => tra('Graph Type'),
                'description' => tra('Defines what type of bar chart is to be generated'),
                'since' => '14.0',
                'filter' => 'word',
                'options' => [
                    ['text' => tra('Vertical bar chart'), 'value' => 'barvert'],
                    ['text' => tra('Horizontal bar chart'), 'value' => 'barhoriz'],
/*                    array('text' => tra('Multiline'), 'value' => 'multiline'),
                    array('text' => tra('Pie'), 'value' => 'pie'),*/
                ],
            ],
            'title' => [
                'required' => false,
                'name' => tra('Graph Title'),
                'description' => tra('Displayed above the graph'),
                'since' => '14.0',
                'filter' => 'text',
                'default' => '',
            ],
            'axestext' => [
                'required' => false,
                'name' => tra('Axes text size'),
                'description' => tra('Size options for the text used for the x and y axes values - the default selection is Large-Text'),
                'since' => '24.1',
                'filter' => 'text',
                'default' => 'Large-Text',
                'options' => [
                    ['text' => tra('Large-Text'), 'value' => 'Large-Text'],
                    ['text' => tra('Normal-Text'), 'value' => 'Normal-Text'],
                ],
            ],
            'alttag' => [
                'required' => false,
                'name' => tra('Alt Tag'),
                'description' => tra('Text for image alt tag - the default is "GDgraph image"'),
                'since' => '14.0',
                'filter' => 'text',
                'default' => 'GDgraph image',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Graph Image Width'),
                'description' => tr('Overall width in pixels. Default value is %0.', '<code>300</code>'),
                'since' => '14.0',
                'filter' => 'digits',
                'default' => 300,
            ],
            'height' => [
                'required' => false,
                'name' => tra('Graph Image Height'),
                'description' => tr('Sets the total height in pixels of the image generated to display the entire graph
                    - if not set and %0 is %1 then the image height will be calculated from the number of x,y pairs,
                    which is useful if the number of x,y pairs is not known eg they are generated using (say) a LIST,
                    CUSTOMSEARCH or trackerlist plugin. The auto height option only works properly if the title is not
                    shown.', '<code>type</code>', '<code>barhoriz</code>'),
                'since' => '14.0',
                'filter' => 'digits',
                'default' => 0,
            ],
            'class' => [
                'required' => false,
                'name' => tra('CSS Class'),
                'description' => tra('In addition to the standard wp-gdgraph class, apply a second custom class to the surrounding DIV'),
                'since' => '24.1',
                'filter' => 'text',
                'default' => '',
            ],
            'divid' => [
                'required' => false,
                'name' => tra('ID'),
                'description' => tra('Apply an id tag to the surrounding DIV'),
                'since' => '24.1',
                'filter' => 'text',
                'default' => '',
            ],
            'float' => [
                'required' => false,
                'name' => tra('Float Position'),
                'description' => tr(
                    'Set the alignment for the graph image. For elements with a width of less than 100%, other elements
					will wrap around it unless the %0 parameter is appropriately set.)',
                    '<code>clear</code>'
                ),
                'since' => '24.1',
                'filter' => 'text',
                'default' => 'none',
                'options' => [
                    ['text' => tra('None'), 'value' => 'none'],
                    ['text' => tra('Left'), 'value' => 'left'],
                    ['text' => tra('Right'), 'value' => 'right']
                ],
            ],
            'clear' => [
                'required' => false,
                'safe' => true,
                'name' => tra('Clear'),
                'description' => tr(
                    'Text, etc. is not allowed to wrap around the box if this parameter is set to %0 (Yes)',
                    '<code>1</code>'
                ),
                'since' => '1',
                'filter' => 'digits',
                'default' => '',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ],
            ],
        ],
    ];
}

function wikiplugin_gdgraph($data, $params)
{
    // check required param
    if (! isset($params['type']) || ($params['type'] !== 'barvert' && $params['type'] !== 'barhoriz')) {
        return ("<span class='error'>missing or wrong graph type parameter - only barvert and barhoriz available at present</span>");
    }

    // set default params
    $plugininfo = wikiplugin_gdgraph_info();
    $default = [];
    foreach ($plugininfo['params'] as $key => $param) {
        $default["$key"] = $param['default'] ?? '';
    }
    $params = array_merge($default, $params);

    // check axestext values
    if (($params['axestext'] !== 'Normal-Text' && $params['axestext'] !== 'Large-Text')) {
        return ("<span class='error'>wrong axestext parameter - only Normal-Text or Large-Text are allowed</span>");
    }

    // parse the body content to allow data to be generated from other plugins and strip tags
    $data = TikiLib::lib('parser')->parse_data($data, ['noparseplugins' => false, 'suppress_icons' => true]);
    // strip tags
    $data = strip_tags($data);

    // split into xy array using a comma as the split parameter with x-data as even number indices and y-data odd indices
    $data = explode("\n", $data);
    // remove empties
    $data = array_filter($data);

    $xy = [];
    foreach ($data as $line) {
        $pair = explode(',', $line);
        if (count($pair) !== 2) {
            return "<span class='error'>gdgraph plugin: ERROR: xy data count mismatch - odd number of values</span>";
        }
        $xy[] = $pair;
    }

    if (empty($xy)) {
        return "<span class='error'>gdgraph plugin: ERROR: there must be at least one XY data pair</span>";
    }

    // Set height dynamically for barhoriz if not set as a parameter or default to 300
    if (empty($params['height'])) {
        if ($params['type'] === 'barhoriz') {
            $params['height'] = count($xy) * 25 + 18; // tested over a range of 3 to 50 x,y pairs but only works OK if title is not displayed
        } else {
            $params['height'] = 300;        // better than nothing?
        }
    }

// -------------------------------------------------------
// Construct separate XY data strings from the array data to suit the graph-engine libraries - and check that at least one y value is non-zero.
// The XY data strings should each contain the same number
// of data elements.
    $ynonzero = false;
    $xydata = ['xdata' => [], 'ydata' => []];
    for ($i = 0; $i < count($xy); $i++) {
        $xydata['xdata'][] = $xy[$i][0];
        $xydata['ydata'][] = (float)$xy[$i][1];
        if ((float)$xy[$i][1] !== 0.0) {
            $ynonzero = true;
        }
    }
// if all y-values are zero don't bother doing the graph
    if (! $ynonzero) {
        return "<span class='error'>All " . count($xy) . " y-values are zero: so no graph drawn</span>";
    }

    $imgparams = [
        'type' => $params['type'],
        'title' => $params['title'],
        'height' => $params['height'],
        'width' => $params['width'],
        'axestext' => $params['axestext'],
        'usexydata' => json_encode($xydata),
    ];

    if (isset($params['float']) && $params['float'] != "none") {
        $f = ' float: ' . $params['float'] . '; ';
    } else {
        $f = '';
    }

    if (isset($params['clear']) && $params['clear'] != 0) {
        $c = " clear: both;";
    } else {
        $c = "";
    }

    $ret = '<div class="wp-gdgraph ' . $params['class'] . '" id="' . $params['divid'] .
           '" style="width: ' . $params['width'] . 'px; margin-left: 10px; margin-right: 10px; ' . $f . $c . ' " >' .
           '<img src="tiki-gdgraph.php?' . html_entity_decode(http_build_query($imgparams, '', '&amp;')) . '" alt="' . $params['alttag'] . '">' .
           '</div>';

    return $ret;
}
