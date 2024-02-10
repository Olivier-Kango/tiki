<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TikiLib;
use WikiParser_PluginArgumentParser;
use WikiParser_PluginMatcher;

#[AsCommand(
    name: 'list:execute',
    description: 'Performs Plugin ListExecute command on a particular page'
)]
class ListExecuteCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'page',
                InputArgument::REQUIRED,
                'Page name where Plugin ListExecute is setup'
            )
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'Name of the action to be executed as defined on the target page'
            )
            ->addArgument(
                'input',
                InputArgument::OPTIONAL,
                'If action takes a variable input parameter, specify it here'
            )
            ->addOption(
                'request',
                null,
                InputOption::VALUE_OPTIONAL,
                'Specify query string defining the request variables to be used on the wiki page. E.g. "days=30&alert=2"'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $page = $input->getArgument('page');
        $action = $input->getArgument('action');

        $tikilib = TikiLib::lib('tiki');
        if (! $pageInfo = $tikilib->get_page_info($page)) {
            $output->writeln("Page $page not found.");
            return (int) false;
        }

        if ($request = $input->getOption('request')) {
            parse_str($request, $_POST);
        }

        $_POST['list_action'] = $action;
        for ($i = 1; $i <= 10; $i++) {
            $_POST['objects' . $i] = ['ALL'];
        }
        $_POST['list_input'] = $input->getArgument('input');

        $_GET = $_REQUEST = $_POST; // wiki_argvariable needs this

        $matches = WikiParser_PluginMatcher::match($pageInfo['data']);

        // Let's check if the plugin is approved
        if (! empty($matches)) {
            $argumentParser = new WikiParser_PluginArgumentParser();
            $parserLib = TikiLib::lib('parser');

            foreach ($matches as $match) {
                if ($match->getName() !== 'listexecute') {
                    continue;
                }

                $listExecuteMatches = WikiParser_PluginMatcher::match($match->getBody());

                foreach ($listExecuteMatches as $listExecuteMatch) {
                    $arguments = $argumentParser->parse($listExecuteMatch->getArguments());

                    // If the action of the list execute is not the requested one, move on
                    if ($listExecuteMatch->getName() !== 'action' || ! isset($arguments['name']) || $arguments['name'] != $action) {
                        continue;
                    }

                    $status = $parserLib->plugin_can_execute($match->getName(), $match->getBody(), $argumentParser->parse($match->getArguments()));

                    if ($status !== true) {
                        $outputMessage = "Action $action failed on page $page. ";
                        $outputMessage .= $status == 'rejected' ?
                            'ListExecute plugin was rejected.' :
                            'ListExecute plugin is pending for approval.';

                        $output->write("<error>$outputMessage</error>");
                        return Command::FAILURE;
                    }
                }
            }
        }

        TikiLib::lib('parser')->parse_data($pageInfo['data']);

        $output->writeln("Action $action executed on page $page.");
        return Command::SUCCESS;
    }
}
