<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use League\HTMLToMarkdown\HtmlConverter;
use League\HTMLToMarkdown\Converter\TableConverter;
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
                'Use this command to convert Tiki wiki syntax stored in one or more pages to Markdown or vice-verse.'
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
                'Convert to markdown syntax'
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
        global $prefs;

        $io = new SymfonyStyle($input, $output);

        if ($prefs['markdown_enabled'] !== 'y') {
            $io->error('Markdown is not enabled in Editing settings.');
            return 1;
        }

        if (! $input->getOption('markdown') && ! $input->getOption('tiki')) {
            $io->error('You should specify either --markdown or --tiki option.');
            return 1;
        }

        $tikilib = TikiLib::lib('tiki');

        if ($page = $input->getOption('page')) {
            $pageInfo = $tikilib->get_page_info($page) ?: null;
            $pages = [$pageInfo];
        } else {
            $allPages = $tikilib->list_pages();
            $pages = $allPages['data'] ?: [];
        }

        if (! $pages) {
            $io->writeln('There are no wiki pages to convert.');
            return 1;
        }

        $converter = new HtmlConverter([
            'strip_tags' => true,
            'hard_break' => true,
        ]);
        $converter->getEnvironment()->addConverter(new TableConverter());

        foreach ($pages as $page) {
            $io->note("Processing page " . $page['pageName']);

            $wikiParserParsable = new WikiParser_Parsable($page['data']);
            $syntax = $wikiParserParsable->guess_syntax($page['data']);
            $html = $wikiParserParsable->parse(['noparseplugins' => true]);

            if ($input->getOption('markdown')) {
                // convert to markdown
                if ($syntax == 'markdown') {
                    $io->warning("Page already in markdown syntax. Skipping...");
                    continue;
                }
                $converted = $converter->convert($html);
            } else {
                // convert to tiki syntax
                if ($syntax == 'tiki') {
                    $io->warning("Page already in Tiki syntax. Skipping...");
                    continue;
                }
                $converted = TikiLib::lib('edit')->parseToWiki($html);
            }

            if ($input->getOption('save')) {
                $syntax = $input->getOption('markdown') ? 'markdown' : 'tiki';
                $converted = '{syntax type="' . $syntax . '"} ' . $converted;
                $tikilib->update_page($page['pageName'], $converted, tra('automatic conversion'), 'admin', '127.0.0.1');
            } else {
                $io->note("Converted:");
                $io->writeln($converted);
            }
        }
    }
}
