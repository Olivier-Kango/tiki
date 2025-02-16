<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputOption;
use Tiki\Package\ComposerManager;

#[AsCommand(
    name: 'security:vendorcheck',
    description: 'Check vendor files against known security issues.'
)]
class VendorSecurityCommand extends Command
{
    protected function configure()
    {
        $this
            ->addOption(
                'packages',
                'p',
                InputOption::VALUE_REQUIRED,
                'Install package file dependencies? Useful for automated scripts. Valid option = (y or n)'
            );
    }


    /**
     * Formats and displays security advisories, also looks up useful information (such as dependents)
     * @param OutputInterface $output       Symfony output for displaying text.
     * @param array           $vendors      Vendors and security advisories to render
     * @param string          $workingDir   If the composer working dir is outside the root dir, specify
     */
    protected function renderAdvisories(OutputInterface $output, array $vendors, string $workingDir = ''): void
    {
        if (empty($vendors)) {
            $output->writeln('No Advisories');
            return;
        }

        foreach ($vendors as $vendorName => $vendor) {
            if ($workingDir) {
                $workingDir = ' --working-dir="' . $workingDir . '"';
            }
            $command = 'php temp/composer.phar depends ' . $vendorName . ' ' . $vendor['version'] . $workingDir . '  2>&1';
            $command = trim(shell_exec($command));
            $command = preg_replace('/(?<=requires.\s).*(?=\s*\()/mU', '<comment>$0 ' . $vendor['version'] . '</comment>', $command, 1);
            $output->writeln($command);
            foreach ($vendor['advisories'] as $advisory) {
                $advisory['title'] = preg_replace('/' . $advisory['cve'] . '[:\s]*/m', '', $advisory['title'], 1);
                $output->writeln($advisory['cve'] . ' - ' . $advisory['title'] . ' - ' . $advisory['link']);
            }
            $output->writeln('');
        }
    }

    private function check($lockFile, $output)
    {
         $output->writeln("<info>Checking $lockFile</info>");
        $command = 'bin/local-php-security-checker';
        $commandOutputArray = [];
        exec($command, $commandOutputArray, $resultCode);
        if ($resultCode != 0) {
            foreach ($commandOutputArray as &$line) {
                $output->writeln("<info>$line</info>");
            }
            throw new Exception("$command security check failed", $resultCode);
        }
        foreach ($commandOutputArray as &$line) {
            $output->writeln("<info>$line</info>");
        }

        return $commandOutputArray;
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // die gracefully if shell_exec is not enabled;
        if (! is_callable('exec')) {
            $output->writeln('<error>exec must be enabled</error>');
            return Command::FAILURE;
        }
        // remove horrible red backgrounds from errors
        $outputStyle = new OutputFormatterStyle('red');
        $output->getFormatter()->setStyle('error', $outputStyle);

        $composerManager = new ComposerManager(TIKI_PATH);

        $lockFile = 'vendor_bundled/composer.lock';
        try {
            $checkOutput = $this->check($lockFile, $output);
        } catch (Exception $e) {
            $output->writeln('<error>Could not fetch security advisories</error>');
            $output->writeln('<comment>Error message:</comment> ' . $e->getMessage());
            return Command::FAILURE;
        }
        /*$alerts = json_decode((string)$alerts, true);
        $output->writeln('<info>Tiki Vendor Advisories</info>');
        $this->renderAdvisories($output, $alerts, 'vendor_bundled');
*/
        $lockFile = 'composer.lock';
        // check if packages lockfile exists
        if (is_readable($lockFile)) {
            /*$installedCount = count($composerManager->getInstalled());
            $availableComposerPackages = $composerManager->getAvailable();
            $totalCount = $installedCount + count($availableComposerPackages);
            $output->writeln("<info>Tiki Package Advisories ($installedCount of $totalCount checked)</info>");
            if ($availableComposerPackages) {
                $output->write('<comment>Packages not evaluated:</comment> ');
                foreach ($availableComposerPackages as $package) {
                    $output->write($package['key'] . ' ');
                }
                $output->writeln('& the dependencies thereof. They must be installed to check advisories.');
                $output->writeln('You may run "php composer.php packages:install --install-all" to install the missing dependencies.', OutputInterface::VERBOSITY_VERBOSE);
            }*/
            try {
                $alerts = $this->check($lockFile, $output);
            } catch (Exception $e) {
                $output->writeln('<error>Could not fetch security advisories</error>');
                $output->writeln('<comment>Error message:</comment> ' . $e->getMessage());
                return Command::FAILURE;
            }
            /*
            $alerts = json_decode((string)$alerts, true);
            $this->renderAdvisories($output, $alerts);*/
        }
        return Command::SUCCESS;
    }
}
