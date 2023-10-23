<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TikiLib;

/**
 * ndex.php and .htaccess are designed to disallow directory listing in apache.
 *
 * @package Tiki\Command
 */

class DevbuildwsconfsCommand extends Command
{
    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('dev:buildwsconfs')
            ->addOption(
                'check-only',
                'c',
                InputOption::VALUE_NONE,
                'just to see the folder where index.php is not yet located'
            );
    }

    /**
     * Command Execution entry point
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (PHP_SAPI !== 'cli') {
            die('Only available through command-line.');
        }

        $checkOnly = $input->getOption('check-only');

        require realpath(__DIR__ . '/../../../../doc/devtools/svntools.php');
        require realpath(__DIR__ . '/../../../../path_constants.php');

        $tikiRootFolder = realpath(__DIR__ . '/../../../../');

        // get latest file "stat" info
        clearstatcache();

        $emptyDirectoriesMessage = '';
        $missingIndexMessage = '';
        $missingIndexMessageFixed = '';
        $missingHtaccessMessage = '';
        $failCreateMessage = '';


        $filter = function ($o_path) {
            $filePath = $o_path->getRealpath();
            $folders = explode(\DIRECTORY_SEPARATOR, $filePath);
            $folder = count($folders);
            $excludeDir = [
                '.', // Directories that are hidden (Ex: .composer)
                TEMP_PATH,
                CUSTOM_PATH,
                VENDOR_PATH,
                VENDOR_BUNDLED_PATH,
                NODE_MODULES_PATH
            ];

            if (in_array($folders[$folder - 1], $excludeDir)) {
                return false;
            }
            return true;
        };

        $it = new \RecursiveDirectoryIterator($tikiRootFolder, \FilesystemIterator::SKIP_DOTS);
        $folder = new \RecursiveCallbackFilterIterator($it, $filter);

        foreach (new \RecursiveIteratorIterator($folder) as $file) {
            $filePath = $file->getRealpath();
            $fileName = $file->getFilename();

            if ($fileName == '..' || ! $file->isDir()) {
                continue;
            }

            if ($this->isEmptyDir($filePath)) {
                $emptyDirectoriesMessage .= '<fg=blue>' . $filePath . '</>' . PHP_EOL;
                continue;
            }

            if (empty(glob($filePath . '/[iI][nN][dD][eE][xX].[pP][hH][pP]'))) { // index.php case-insensitive
                $missingIndexMessage .= '<fg=blue>' . $filePath . '</>' . PHP_EOL;

                if (! $checkOnly) {
                    $projectFolder = str_replace('doc', '', dirname(__DIR__));
                    $path = str_replace($projectFolder, '', $filePath);
                    $path = str_replace('-', '', $path);
                    $indexPath = preg_replace('/(\w+)/', '../', $path);
                    $indexPath = str_replace('//', '/', $indexPath);

                    $indexContent = '<?php' . PHP_EOL . PHP_EOL . '// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project' . PHP_EOL;
                    $indexContent .= '//' . PHP_EOL . '// All Rights Reserved. See copyright.txt for details and a complete list of authors.' . PHP_EOL;
                    $indexContent .= '// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.' . PHP_EOL;
                    $indexContent .= PHP_EOL . '// This redirects to the sites root to prevent directory browsing' . PHP_EOL;
                    $indexContent .= 'header("location: ' . $indexPath . 'index.php");' . PHP_EOL;
                    $indexContent .= 'die;' . PHP_EOL;

                    $newFile = file_put_contents($filePath . '/index.php', $indexContent);
                    $missingIndexMessageFixed .= '<fg=blue>' . $filePath . '</>' . PHP_EOL;

                    if ($newFile === false) {
                        $failCreateMessage .= '<fg=blue>' . $filePath . '</>' . PHP_EOL;
                    }
                }

                if (! $this->folderHasHtaccess($filePath)) {
                    $missingHtaccessMessage .= '<fg=blue>' . $filePath . '</>' . PHP_EOL;
                }
            }
        }

        if (! empty($emptyDirectoriesMessage) || ! empty($missingIndexMessage) || ! empty($missingHtaccessMessage)) {
            if (! empty($emptyDirectoriesMessage)) {
                $output->writeln('<comment>The following directories are empty:</comment>');
                $output->writeln($emptyDirectoriesMessage);
            }
            if (! empty($missingIndexMessage)) {
                $output->writeln('<comment>index.php file is missing in the following directories:</comment>');
                $output->writeln($missingIndexMessage);

                if (! empty($missingIndexMessageFixed)) {
                    $output->writeln('<info>index.php file was fixed in the following directories:</info>');
                    $output->writeln($missingIndexMessageFixed);
                }
            }
            if (! empty($missingHtaccessMessage)) {
                $output->writeln('<comment>.htaccess file is missing in the following directories:</comment>');
                $output->writeln($missingHtaccessMessage);
            }
            if (! empty($failCreateMessage)) {
                $output->writeln('<comment>The directory where he failed</comment>');
                $output->writeln($failCreateMessage);
            }
            exit(1);
        } else {
            important('All directories OK');
        }
        return Command::SUCCESS;
    }

    /**
    * Check if folder is empty
    *
    * @param $dir
    * @return boolean
    */
    protected function isEmptyDir($dir)
    {
        return (($files = scandir($dir)) && count($files) <= 2);
    }

    /**
     * Check if .htaccess file exist
     *
     * @param $dir
     * @return boolean
     */
    protected function folderHasHtaccess($dir)
    {
        global $tikiRootFolder;

        $hasHtaccess = file_exists($dir . '/.htaccess');

        // We want to ensure that first level folders have .htaccess
        if (! $hasHtaccess && dirname($dir) !== $tikiRootFolder && $dir != $tikiRootFolder) {
            return $this->folderHasHtaccess(dirname($dir));
        }

        return $hasHtaccess;
    }
}
