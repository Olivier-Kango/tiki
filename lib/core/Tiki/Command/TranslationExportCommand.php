<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @package Tiki\Command
 *
 * Export translations from the database to lang/xx/language.php files
 *
 */
class TranslationExportCommand extends Command
{
    protected static $defaultDescription = 'Update language.php translations from the database';
    protected function configure(): void
    {
        $this
            ->setName('translation:export')
            ->setHelp('Scans database translations and update language file.')
            ->addOption(
                'lang',
                'l',
                InputOption::VALUE_REQUIRED,
                'Language code to process eg. --lang=pt-br'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $langCode = $input->getOption('lang') ?: null;

        if (! $langCode) {
            $io->error('No language code specified. Please use --lang=<LANG_CODE>');
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        require_once('lang/langmapping.php');
        require_once('lib/language/Language.php');
        require_once('lib/language/LanguageTranslations.php');

        if (! array_key_exists($langCode, $langmapping)) {
            $io->error('Invalid language code.');
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $language = new \LanguageTranslations($langCode);

        try {
            $stats = $language->writeLanguageFile();
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $io->success(sprintf('Wrote %d new strings and updated %d to lang/%s/language.php', $stats['new'], $stats['modif'], $language->lang));
        return Command::SUCCESS;
    }
}
