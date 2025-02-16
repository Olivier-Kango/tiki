<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Tiki\Process\Process;

function wikiplugin_chartjs_info()
{
    return [
        'name' => tr('Chart JS'),
        'documentation' => 'PluginChartJS',
        'description' => tra('Create a JS Chart'),
        'prefs' => ['wikiplugin_chartjs'],
        'body' => tr('JSON encoded array for data and options.'),
        'tags' => ['advanced'],
        'introduced' => 16,
        'params' => [
            'id' => [
                'name' => tra('Chart Id'),
                'description' => tr('A custom ID for the chart.'),
                'required' => false,
                'filter' => 'text',
                'default' => 'tikiChart1, tikiChart2 etc',
                'since' => '16.0',
            ],
            'type' => [
                'name' => tra('Chart Type'),
                'description' => tr('The type of chart. Currently works with pie, bar and doughnut'),
                'required' => false,
                'filter' => 'text',
                'default' => 'pie',
                'since' => '16.0',
            ],
            'height' => [
                'name' => tra('Chart Height'),
                'description' => tr('The height of the chart in px'),
                'required' => false,
                'filter' => 'text',
                'default' => '200',
                'since' => '16.0',
            ],
            'width' => [
                'name' => tra('Chart Width'),
                'description' => tr('The width of the chart in px'),
                'required' => false,
                'filter' => 'text',
                'default' => '200',
                'since' => '16.0',
            ],
            'values' => [
                'name' => tra('Chart data values'),
                'description' => tr('Colon-separated values for the chart (required if not using JSON encoded data in the plugin body)'),
                'required' => false,
                'filter' => 'text',
                'default' => '',
                'since' => '16.0',
            ],
            'data_labels' => [
                'name' => tra('Chart data labels'),
                'description' => tr('Colon-separated labels for the datasets in the chart. Max 10, if left empty'),
                'required' => false,
                'filter' => 'text',
                'default' => 'A:B:C:D:E:F:G:H:I:J',
                'since' => '16.0',
            ],
            'data_colors' => [
                'name' => tra('Chart colors'),
                'description' => tr('Colon-separated colors for the datasets in the chart. Max 10, if left empty'),
                'required' => false,
                'filter' => 'text',
                'default' => 'red:blue:green:purple:grey:orange:yellow:black:brown:cyan',
                'since' => '16.0',
            ],
            'data_highlights' => [
                'name' => tra('Chart highlight'),
                'description' => tr('Colon-separated color of chart section when highlighted'),
                'required' => false,
                'filter' => 'text',
                'default' => '',
                'since' => '16.0',
            ],
            'debug' => [
                'name' => tra('Debug Mode'),
                'description' => tr('Uses the non-minified version of the chart.js library for easier debugging.'),
                'required' => false,
                'filter' => 'digits',
                'default' => 0,
                'advanced' => true,
                'since' => '18.3',
            ],
        ],
        'iconname' => 'pie-chart',
    ];
}

function wikiplugin_chartjs($data, $params)
{
    global $base_url, $jitRequest;

    static $instance = 0;
    $instance++;

    if (! empty($params['id'])) {
        $params['id'] = preg_replace('/[^A-Za-z0-9_]/', '', $params['id']);
    }

    if (empty($params['id'])) {
        $params['id'] = "tikiChart$instance";
    }

    //set defaults
    $plugininfo = wikiplugin_chartjs_info();
    $defaults = [];
    foreach ($plugininfo['params'] as $key => $param) {
        $defaults[$key] = $param['default'];
    }
    $params = array_merge($defaults, $params);

    if (empty($params['data_highlights'])) {
        $params['data_highlights'] = $params['data_colors'];
    }

    if (empty(trim($data))) {
        $values = array_filter(explode(':', $params['values']));
        $data_labels = array_filter(explode(':', $params['data_labels']));
        $data_colors = array_filter(explode(':', $params['data_colors']));
        $data_highlights = array_filter(explode(':', $params['data_highlights']));

        if (empty($values)) {
            return tr('Values must be set for chart');
        }

        $data = [
            'labels'   => array_slice($data_labels, 0, count($values)),
            'datasets' => [
                [
                    'data'                 => $values,
                    'backgroundColor'      => array_slice($data_colors, 0, count($values)),
                    'hoverBackgroundColor' => array_slice($data_highlights, 0, count($values)),
                ],
            ],
        ];
        $options = [];
    } else {
        $data = json_decode($data, true);
        if (isset($data['options']) && isset($data['data'])) {
            $options = $data['options'];
            $data = $data['data'];
        }
    }

    $to_PDF = $jitRequest->display->string() == 'pdf';

    // Disable animation
    if ($to_PDF) {
        $options['animation'] = [       // 'animation: { duration: 0 }'
            'duration' => 0,
        ];
    }

    $script = '
    var chartjs_' . $params['id'] . ' = new Chart("' . $params['id'] . '", {
        type: "' . $params['type'] . '",
        data: ' . json_encode($data) . ',
        options: ' . (empty($options) ? '{}' : json_encode($options)) . '
    });
';

    $canvas = '<canvas id="' . $params['id'] . '" width="' . $params['width'] . '" height="' . $params['height'] . '"></canvas>';

    if (! $to_PDF) {
        if ($instance === 1) {
            TikiLib::lib('header')->add_js_module(
                '
                import { Chart, registerables } from "chartjs";
                Chart.register(...registerables);
                setTimeout(function () {' . $script . '}, 500);
             '
            );
        } else {
            TikiLib::lib('header')->add_js_module('
                setTimeout(function () {' . $script . '}, 500);
             ');
        }

        return '<div class="tiki-chartjs">' . $canvas . '</div>';
    }

    // PDF export related logic
    $html_content = generateJsImportmapScripts(true); // generate imports with full URL since we load the html file as file://
    $html_content .= <<<HTML
<div>
    $canvas
</div>

<script type="module">
    import { Chart, registerables } from "chartjs";
    Chart.register(...registerables);
    $script
</script>
HTML;

    $scriptHash = md5($script);
    $cacheKey = 'chart_';
    $cacheLib = TikiLib::lib('cache');

    if (! $cacheLib->isCached($scriptHash, $cacheKey)) {
        $htmlFile = writeTempFile($html_content, '', true, 'wikiplugin_chart_', '.html');
        $casperBin = implode(DIRECTORY_SEPARATOR, [TIKI_PATH, 'bin', 'casperjs']);
        if (! file_exists($casperBin)) {
            return tr('Tiki needs the jerome-breton/casperjs-installer to convert charts to PNG. If you do not have permission to install this package, ask the site administrator.');
        }

        $casperjsScript = <<<JS
var casper = require('casper').create();

casper.start('{$htmlFile}', function() {
    this.echo(this.captureBase64('png', 'div'));
});

casper.run();
JS;

        $casperFile = writeTempFile($casperjsScript, '', true, 'wikiplugin_chart_', '.js');

        $process = new Process([$casperBin, $casperFile, '--ignore-ssl-errors=true']);
        if (! empty($params['timeout'])) {
            $process->setTimeout($params['timeout']);
            $process->setIdleTimeout($params['timeout']);
        }
        try {
            $process->run(null, ['OPENSSL_CONF' => '/etc/ssl']);
        } catch (ProcessTimedOutException $e) {
            $logsLib = TikiLib::lib('logs');
            $logsLib->add_log('Casperjs', $e->getMessage());

            \Feedback::error(tr('Failed to generate chart image using Casperjs. Please check Tiki Action Log for more information.'));
        } finally {
            unlink($htmlFile);
            unlink($casperFile);
        }

        if ($process->isSuccessful()) {
            $base64 = $process->getOutput();
        } else {
            return false;
        }

        $cacheLib->cacheItem($scriptHash, $base64, $cacheKey);
    } else {
        $base64 = $cacheLib->getCached($scriptHash, $cacheKey);
    }

    $canvas = <<<HTML
<img src="data:image/jpeg;charset=utf-8;base64, {$base64}"/>
HTML;

    return '<div class="tiki-chartjs">' . $canvas . '</div>';
}
