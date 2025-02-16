<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'                => [
        'type'                            => 'bool',              //get
        'width'                           => 'word',              //get
        'height'                          => 'word',              //get
        'title'                           => 'word',              //get
        'axestext'                        => 'word',              //get
        ],
    ],
];
require_once('tiki-setup.php'); // this seems to be needed ?
require_once('lib/graph-engine/gd.php');
require_once('lib/graph-engine/graph.bar.php');

$access->check_feature('wikiplugin_gdgraph');

if (! isset($_REQUEST['type'])) {
    $smarty->assign('msg', tra("No type indicated"));
    $smarty->display("error.tpl");
    die;
}

//Decode the passed array
$strencxy2 = $_GET['usexydata'];
$dataxy = json_decode(urldecode($strencxy2));

// only the barvert and barhoriz types are working at present
if ($_REQUEST["type"] == 'barvert' || $_REQUEST["type"] == 'barhoriz') {
    $renderer = new GD_GRenderer($_REQUEST["width"], $_REQUEST["height"]);
    $graph = new MultibarGraphic();
    $graph->setTitle(tra($_REQUEST["title"]));

    $graph->setData(['x' => $dataxy->xdata, 'y0' => $dataxy->ydata]);

    if ($_REQUEST["type"] == 'barvert') {
        $graph->setParam('grid-independant-location', 'horizontal');
    } else {
        $graph->setParam('grid-independant-location', 'vertical');
        $graph->setParam('grid-horizontal-position', 'top');
    }
    if (isset($_REQUEST["axestext"]) && ! empty($_REQUEST["axestext"]) && $_REQUEST["axestext"] == 'Large-Text') {
        $graph->setParam('grid-independant-major-font', 'Large-Text');
    } else {
        $graph->setParam('grid-independant-major-font', 'Normal-Text');
    }
    $graph->setParam('grid-independant-major-guide', false);
} elseif ($_REQUEST["type"] == 'multiline') {
    // multiline not working as yet so shouldn't get here
    $renderer = new GD_GRenderer($_REQUEST["width"], $_REQUEST["height"]);
    $graph = new MultilineGraphic();
    $graph->setTitle(tra($_REQUEST["title"]));

    $graph->setData(['x' => $dataxy['xdata'], 'y0' => $dataxy['ydata']]);

    $graph->setParam('grid-independant-location', 'vertical');
    $graph->setParam('grid-independant-major-font', 'Normal-Text');
    $graph->setParam('grid-independant-major-guide', false);
} elseif ($_REQUEST["type"] == 'pie') {
    // pie not working as yet so shouldn't get here
    $renderer = new GD_GRenderer($_REQUEST["width"], $_REQUEST["height"]);
    $graph = new PieChartGraphic();
    $graph->setTitle(tra($_REQUEST["title"]));

    $graph->setData(['x' => $dataxy['xdata'], 'y0' => $dataxy['ydata']]);

    $graph->setParam('grid-independant-location', 'vertical');
    $graph->setParam('grid-independant-major-font', 'Normal-Text');
    $graph->setParam('grid-independant-major-guide', false);
} else {
// should never end up here - but should add some sort of error return if you do
}
$graph->draw($renderer);
$renderer->httpOutput('graph.png');
