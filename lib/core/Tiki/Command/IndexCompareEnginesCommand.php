<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use TikiLib;

class IndexCompareEnginesCommand extends Command
{
    /**
     * Add or remove plugins to this array to be considered when checking the results
     */
    const PLUGINS_TO_CHECK = ['list', 'listexecute', 'pivottable'];

    protected function configure()
    {
        $this
            ->setName('index:compare-engines')
            ->setDescription('Compare search engine results in wikiplugins')
            ->setHelp(
                'Check unified search plugin results inside wiki pages by comparing Elasticsearch results with its MySQL fallback counterpart.
Only plugins that use the unified search results are verified.'
            )
            ->addOption(
                'page',
                null,
                InputOption::VALUE_REQUIRED,
                'The page name to check',
            )
            ->addOption(
                'html',
                null,
                InputOption::VALUE_NONE,
                'Export the differences found in a well formatted HTML file'
            )
            ->addOption(
                'reindex',
                null,
                InputOption::VALUE_NONE,
                'Reindex search engines before running this script'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $prefs, $tikidomainslash;

        $io = new SymfonyStyle($input, $output);

        if ($prefs['unified_engine'] != 'elastic' || $prefs['unified_elastic_mysql_search_fallback'] != 'y') {
            $io->error(
                'To execute this script you need your unified search engine configured with Elasticsearch and MySQL fallback has to be enabled.'
            );
            return 1;
        }

        $tikiLib = TikiLib::lib('tiki');

        if ($page = $input->getOption('page')) {
            $pageInfo = $tikiLib->get_page_info($page) ?: null;
            $pages = [$pageInfo];
        } else {
            $allPages = $tikiLib->list_pages();
            $pages = $allPages['data'] ?: [];
        }

        if (! $pages) {
            $io->writeln('There are no wiki pages to check.');
            return 0;
        }

        $reindex = $input->getOption('reindex');
        $unifiedSearchLib = TikiLib::lib('unifiedsearch');
        $elasticStatus = $unifiedSearchLib->checkElasticsearch();

        if ($elasticStatus['error']) {
            $io->error('ElasticSearch Error' . PHP_EOL . $elasticStatus['feedback']);
            exit(1);
        }

        if (! $reindex && ! $unifiedSearchLib->getIndex()->exists()) {
            $io->error('ElasticSearch index not found. Use --reindex to rebuild the index.');
            exit(1);
        }

        $prefs['unified_engine'] = 'mysql'; // Check if mysql index table exists
        $mysqlStatus = $unifiedSearchLib->checkMySql();

        if ($mysqlStatus['error'] && ! $reindex) {
            $io->error($mysqlStatus['feedback']);
            exit(1);
        }

        $prefs['unified_engine'] = 'elastic'; // Restore original value

        if ($input->getOption('reindex')) {
            $io->writeln('Rebuilding index, please wait...');
            @TikiLib::lib('unifiedsearch')->rebuild();
            $io->writeln('Index rebuild finished.');
            $io->newLine(2);
        }

        $parserLib = TikiLib::lib('parser');
        $differentOutputs = [];

        // Disable fallback, in case of elastic failure does not use the mysql results
        $prefs['unified_elastic_mysql_search_fallback'] = 'n';

        foreach ($pages as $page) {
            $plugins = \WikiParser_PluginMatcher::match($page['data']);

            if (! $plugins->count()) {
                continue;
            }

            for ($i = 0; $i < $plugins->count(); $i++) {
                $plugin = $plugins->next();

                $rawPlugin = strval($plugin);

                if (! in_array($plugin->getName(), static::PLUGINS_TO_CHECK)) {
                    continue;
                }

                $pluginName = $plugin->getName();

                \Search_Formatter_Factory::$counter = 0; // Reset counter index
                $elasticOutput = @$parserLib->parse_data($rawPlugin);
                TikiLib::lib('unifiedsearch')->invalidateIndicesCache();

                $prefs['unified_engine'] = 'mysql';
                \Search_Formatter_Factory::$counter = 0; // Reset counter index (different engine)
                $fallbackOutput = @$parserLib->parse_data($rawPlugin);
                $prefs['unified_engine'] = 'elastic';

                // Remove static $id usage to avoid differences used by pivottable plugin
                if ($pluginName == 'pivottable') {
                    $regex = '/pivottable.?\d+/';
                    $elasticOutput = preg_replace($regex, 'pivottable', $elasticOutput);
                    $fallbackOutput = preg_replace($regex, 'pivottable', $fallbackOutput);
                }

                // Remove static $id usage to avoid differences used by listexecute plugin
                if ($pluginName == 'listexecute') {
                    $regex = '/listexecute.?\d+/';
                    $elasticOutput = preg_replace($regex, 'listexecute', $elasticOutput);
                    $fallbackOutput = preg_replace($regex, 'listexecute', $fallbackOutput);

                    $regex = '/objects\d+\[\]/';
                    $elasticOutput = preg_replace($regex, 'objects[]', $elasticOutput);
                    $fallbackOutput = preg_replace($regex, 'objects[]', $fallbackOutput);
                }

                // Remove static $id usage to avoid differences used by list plugin
                if ($pluginName == 'list') {
                    $regex = '/list.?(\d+)/';

                    $elasticOutput = preg_replace($regex, 'list', $elasticOutput);
                    $fallbackOutput = preg_replace($regex, 'list', $fallbackOutput);
                }

                if ($elasticOutput !== $fallbackOutput) {
                    $differentOutputs[] = [
                        'page'     => $page['pageName'],
                        'plugin'   => $rawPlugin,
                        'elastic'  => $elasticOutput,
                        'fallback' => $fallbackOutput,
                    ];
                }
            }
        }

        if (empty($differentOutputs)) {
            $io->writeln('Plugin outputs using Elasticsearch and the MySQL fallback are identical.');
            return 0;
        }

        if ($input->getOption('html')) {
            include_once 'lib/diff/difflib.php';
            include_once 'lib/wiki-plugins/wikiplugin_code.php';

            $htmlOutput = "";

            foreach ($differentOutputs as $output) {
                $pageName = $output['page'];
                $pluginCode = wikiplugin_code($output['plugin'], ['colors' => 'tiki'], null, []);
                $diff = diff2($output['elastic'], $output['fallback']);
                $htmlOutput .= <<<HTML
<table class='table table-striped' style='margin-top: 40px'>
    <tbody>
        <tr>
            <td>Wiki page</td>
            <td>{$pageName}</td>
        </tr>
        <tr>
            <td>Plugin Declaration</td>
            <td>{$pluginCode}</td>
        </tr>
        <tr>
            <td>Output diff (Elastic/MySQL)</td>
            <td><table style='width:100%'>{$diff}</table></td>
        </tr>
    </tbody>
</table>
HTML;
            }

            // Inject the CSS, so the output file can be used as standalone HTML file
            $tikiBaseCSS = file_get_contents('themes/base_files/css/tiki_base.css');
            $defaultCSS = file_get_contents('themes/default/css/default.css');
            $htmlOutput = <<<HTML
<!DOCTYPE html>
    <html>
        <head>
            <style>{$tikiBaseCSS}</style>
            <style>{$defaultCSS}</style>
        </head>
        <body style='margin-left: 20%; margin-right: 20%; margin-top: 20px'>
            <h4>Check unified search script results</h4>
            {$htmlOutput}
        </body>
    </html>
HTML;

            $filename = sprintf('index-compare-engines_results_%s.html', date('YmdHi'));

            $finalPath = 'temp/' . $tikidomainslash . $filename;

            if (file_exists($finalPath)) {
                unlink($finalPath);
            }

            file_put_contents($finalPath, $htmlOutput);

            $io->writeln("Plugin differences found. Please check the file '$finalPath' for more details.");
            return 1;
        }

        $builder = new UnifiedDiffOutputBuilder("--- Elastic\n+++ MySQL\n");
        $differ = new Differ($builder);

        foreach ($differentOutputs as $output) {
            $io->section('Tiki Page - ' . $output['page']);
            $io->writeln('Plugin Declaration:');
            $io->writeln($output['plugin']);
            $io->newLine(2);

            $diff = $differ->diff($output['elastic'], $output['fallback']);
            $io->writeln($diff);
        }

        return 1;
    }
}
