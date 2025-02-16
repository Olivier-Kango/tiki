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

#[AsCommand(
    name: 'multitiki:move',
    description: 'Moves a MultiTiki site from one tiki instance to another',
)]
class MultiTikiMoveCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'site',
                InputArgument::REQUIRED,
                'Name of multitiki site to move'
            )
            ->addArgument(
                'from',
                InputArgument::REQUIRED,
                'path to the Tiki instance to move from (use "." for this one)'
            )
            ->addArgument(
                'to',
                InputArgument::OPTIONAL,
                'path to the Tiki instance to move to (defaults to this one if absent or if a site name is given instead of a valid file path then the destination site will be renamed)'
            )
            ->addOption(
                'confirm',
                null,
                InputOption::VALUE_NONE,
                'Perform the move'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $confirm = $input->getOption('confirm');

        $site = $input->getOption('site');
        if ($site) {
            $output->writeln("<error>Opion --site should not used in this command</error>");
            $output->writeln('<info>' . $this->getSynopsis() . '</info>');
            return -1;
        }
        $site = $input->getArgument('site');

        $from = $input->getArgument('from');
        if (! $from || ! is_readable($from)) {
            $output->writeln("<error>From path $from not found</error>");
            $output->writeln('<info>' . $this->getSynopsis() . '</info>');
            return -1;
        }
        $to = $input->getArgument('to');
        if (! $to) {
            $to = getcwd();
        }
        $from = realpath($from);
        if (realpath($to)) {
            $to = realpath($to);
            $destSite = $site;
        } else {
            $destSite = $to;
            $to = getcwd() ;
        }

        $from = rtrim($from, '/');
        $to = rtrim($to, '/');

        $list = $this->getVirtuals($from);

        if ($list) {
            if (in_array($site, $list)) {
                $list = $this->getVirtuals($to);

                if (in_array($destSite, $list)) {
                    $output->writeln("<error>Site $site already exists in destination</error>");
                    return -1;
                }

                $dirs = [   // from setup.sh currently
                    TIKI_CONFIG_PATH,
                    DEPRECATED_IMG_WIKI_PATH,
                    DEPRECATED_IMG_WIKI_UP_PATH,
                    TRACKER_FIELD_IMAGE_STORAGE_PATH,
                    TEMP_PATH,
                    TEMP_CACHE_PATH,
                    TEMP_HTTP_PUBLIC_PATH,
                    SMARTY_COMPILED_TEMPLATES_PATH,
                    SMARTY_TEMPLATES_PATH,
                    BASE_THEMES_SRC_PATH,
                    WHELP_PATH,
                    DEPRECATED_MODS_PATH,
                    DEPRECATED_FILES_PATH,
                    TIKI_TESTS_PATH,
                    //'temp/unified-index'
                ];

                $moves = [];
                foreach ($dirs as $dir) {
                    $src = $from . '/' . $dir . '/' . $site;
                    if (! is_dir($src) && $dir === 'themes' && is_dir($from . '/styles/' . $site)) {    // pre tiki 13
                        $src = $from . '/styles/' . $site;
                    }
                    if (is_dir($src)) {
                        $dest = $to . '/' . $dir . '/' . $destSite;
                        if (is_dir($dest)) {
                            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                                $output->writeln("<error>Destination directory already exists: $dest</error>");
                            }
                        } else {
                            $moves[] = [$src, $dest];
                        }
                    } else {
                        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                            $output->writeln("<error>Source directory not found: $src</error>");
                        }
                    }
                }
                // loop through the dirs to move
                foreach ($moves as $move) {
                    if ($confirm) {
                        // do the actual move
                        rename($move[0], $move[1]);
                        $mode = "Moved:";
                    } else {
                        $mode = "Will move:";
                    }
                    $output->writeln("{$mode} {$move[0]} to {$move[1]}");
                }
                if ($confirm) {
                    // update the virtuals.inc files
                    // remove from the from tiki
                    $list = $this->getVirtuals($from);
                    unset($list[array_search($site, $list)]);
                    file_put_contents($from . '/db/virtuals.inc', implode("\n", $list) . "\n");

                    // add to the destination
                    $list = $this->getVirtuals($to);
                    $list[] = $destSite;
                    file_put_contents($to . '/db/virtuals.inc', implode("\n", $list) . "\n");

                    if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                        $output->writeln("<info>Updated both db/virtuals.inc files</info>");
                    }
                } else {
                    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                        $output->writeln("<info>Use --confirm to perform moves</info>");
                    }
                }
            } else {
                $output->writeln("<error>Site $site not found in $from</error>");
                return Command::FAILURE;
            }
        } else {
            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln("<info>No multitikis found in $from</info>");
            }
        }
        return Command::SUCCESS;
    }

    /**
     * @param string $path to instance to list
     * @return array list of multitiki sites
     * @internal param $line
     */
    private function getVirtuals($path)
    {
        $virtuals = $path . '/db/virtuals.inc';
        $list = [];

        if (is_file($virtuals)) {
            $list = file($virtuals);
            foreach ($list as & $line) {
                $line = trim($line);
            }
        }
        return $list;
    }
}
