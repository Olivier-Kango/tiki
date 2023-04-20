<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TikiLib;
use WikiParser_Parsable;

class MarkdownConvertCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('markdown:convert')
            ->setDescription('Convert wiki pages between Tiki syntax and Markdown')
            ->setHelp(
                'Use this command to convert Tiki wiki syntax stored in one or more pages to Markdown or vice-versa.'
            )
            ->addOption(
                'page',
                null,
                InputOption::VALUE_OPTIONAL,
                'The page name to check. Leave empty to attempt conversion of all available pages',
            )
            ->addOption(
                'markdown',
                null,
                InputOption::VALUE_NONE,
                'Convert to Markdown syntax'
            )
            ->addOption(
                'tiki',
                null,
                InputOption::VALUE_NONE,
                'Convert to Tiki wiki syntax'
            )
            ->addOption(
                'save',
                null,
                InputOption::VALUE_NONE,
                'Save converted content back to the database. Important: this will overwrite your existing content, proceed with caution!'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $prefs, $page;

        $io = new SymfonyStyle($input, $output);

        if ($prefs['markdown_enabled'] !== 'y') {
            $io->error(tr('Markdown is not enabled in Editing settings.'));
            return 1;
        }

        if (! $input->getOption('markdown') && ! $input->getOption('tiki')) {
            $io->error(tr('You should specify either --markdown or --tiki option.'));
            return 1;
        }

        $tikilib = TikiLib::lib('tiki');

        if ($pageInfo = $input->getOption('page')) {
            $pageInfo = $tikilib->get_page_info($pageInfo) ?: null;
            if (empty($pageInfo)) {
                $io->error(tr('Page not found!'));
                return 1;
            }
            $pages = [$pageInfo];
        } else {
            $allPages = $tikilib->list_pages();
            $pages = $allPages['data'] ?: [];
        }

        if (! $pages) {
            $io->writeln(tr('There are no wiki pages to convert.'));
            return 1;
        }

        $syntax = $input->getOption('markdown') ? 'markdown' : 'tiki';

        foreach ($pages as $pageInfo) {
            $io->note(tr("Processing page %0", $pageInfo['pageName']));

            try {
                $page = $pageInfo['pageName'];
                $converted = TikiLib::lib('edit')->convertWikiSyntax($pageInfo['data'], $syntax);
            } catch (Exception $e) {
                $io->warning($e->getMessage() . ' ' . tr('Skipping...'));
                continue;
            }

            if ($input->getOption('save')) {
                $converted = '{syntax type="' . $syntax . '"} ' . $converted;
                $tikilib->update_page($pageInfo['pageName'], $converted, tra('automatic conversion'), 'admin', '127.0.0.1', null, 0, '', null, null, null, '', true);
            } else {
                $io->note("Converted:");
                $io->writeln($converted);
            }
        }
    }
}
