<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// ** This is the main script to release Tiki **
//
// To get the Tiki release HOWTO, try:
//    php src/devtools/release.php --howto
//
// You can also get a detailed help on this script with:
//    php src/devtools/release.php --help
//

define('TOOLS', __DIR__);
define('ROOT', realpath(TOOLS . '/../..'));

define('CHANGELOG_FILENAME', 'changelog.md'); //
define('COPYRIGHTS_FILENAME', 'copyright.txt');

// Display all errors and warnings, strict level already included in E_ALL in php 8.4
define('ERROR_REPORTING_LEVEL', E_ALL);
error_reporting(ERROR_REPORTING_LEVEL);

chdir(ROOT . '/');

require_once ROOT . '/lib/setup/third_party.php';
require_once ROOT . '/path_constants.php';
require_once ROOT . '/' . CI_TOOLS_PATH . '/vcscommons.php';
require_once ROOT . '/' . PRIMARY_AUTOLOAD_FILE_PATH;

$phpCommand = $_SERVER['_'] ?? 'php';
$phpCommandArguments = implode(' ', $_SERVER['argv']);

if (! ($options = get_options()) || $options['help']) {
    display_usage();
}

if (file_exists(ROOT . '/.git')) {
    $vcs = 'git';
} else {
    exit;
}

require_once TOOLS . '/' . $vcs . 'tools.php';


if ($options['devmode']) {
    $options['no-commit'] = true;
    $options['no-check-vcs'] = true;
    $options['no-first-update'] = true;
    $options['debug-packaging'] = true;
}
if ($options['howto']) {
    display_howto();
}


if (! check_bin_version()) {
    error("You need the VCS '" . getBinName() . "' program at least at version " . getMinVersion() . "\n");
}

if (! $options['no-check-vcs'] && has_uncommited_changes('.')) {
    error("Uncommitted changes exist in the working folder.\n");
}

include_once('lib/setup/twversion.class.php');
$TWV = new TWVersion();

$script = $_SERVER['argv'][0];
$version = $_SERVER['argv'][1] ?? '';
$subrelease = $_SERVER['argv'][2] ?? '';

if (! preg_match("/^\d+\.\d+$/", $version)) {
    error("Version number should be in X.X format.\n");
}

$isPre = str_starts_with($subrelease, 'pre');
if ($isPre) {
    $subrelease = substr($subrelease, 3);
    $pre = 'pre';
} else {
    $pre = '';
}
$splitedversion = explode('.', $version);
$mainversion = $splitedversion[0];

$check_version = $version . $subrelease;
if ($TWV->version !== $check_version && ! $options['devmode']) {
    error("The version in the code " . strtolower($TWV->version) . " differs from the version provided to the script $check_version.\nThe version should be modified in lib/setup/twversion.class.php to match the released version.");
}

echo color("\nTiki release process started for version '$version" . ($subrelease ? " $subrelease" : '') . "'\n", 'cyan');
if ($isPre) {
    echo color("The script is running in 'pre-release' mode, which means that no tag will be created.\n", 'yellow');
}

if (! $options['no-first-update'] && important_step('Update working copy to the last revision')) {
    echo "Update in progress...";
    update_working_copy('.');

    if (! $options['no-check-vcs'] && has_uncommited_changes('.')) {
        error("\rUncommitted changes exist in the working folder.\n");
    }
    $revision = get_revision('.');
    info("\r>> Checkout updated to revision $revision.");
}

if (empty($subrelease)) {
    $branch = "$mainversion.x";
    $tag = "tags/$version";
    $packageVersion = $version;
    if (! empty($pre)) {
        $packageVersion .= ".$pre";
    }
    $relVersion = $version;
} else {
    $branch = "$mainversion.x";
    $tag = "tags/$version$subrelease";
    $packageVersion = "$version.$pre$subrelease";
    $relVersion = "$version$subrelease";
}

if (! $options['no-lang-update'] && important_step("Update language files")) {
    passthru("$phpCommand console.php translation:getstrings");
    $removeFiles = glob('lang/*/language.php.old');
    foreach ($removeFiles as $rf) {
        unlink($rf);
    }
    unset($removeFiles);
    info('>> Language files updated and temporary files removed.');
    important_step('Commit updated language files', true, "[REL] Update language.php files for $relVersion");
}

if (! $options['no-changelog-update'] && important_step("Update '" . CHANGELOG_FILENAME . "' file (using final version number '$version')")) {
    $output = [];
    $returnVar = 0;
    exec("php src/ci/generate_changelog.php", $output, $returnVar);
    if ($returnVar === 0) {
        info(">> Changelog updated successfully using src/ci/generate_changelog.php script.");
        important_step("Commit new " . CHANGELOG_FILENAME, true, "[REL] Update " . CHANGELOG_FILENAME . " for $relVersion");
    } else {
        error("Changelog update failed. generate_changelog.php exited with code $returnVar.\nOutput:\n" . implode("\n", $output));
    }
}

if (! $options['no-copyright-update'] && important_step("Update '" . COPYRIGHTS_FILENAME . "' file (using final version number '$version')")) {
    passthru("$phpCommand src/devtools/generate_copyright.php", $exitCode);
    if ($exitCode === 0) {
        info("\n>> Copyright updated successfully using src/devtools/generate_copyright.php script.");
        important_step("Commit new " . COPYRIGHTS_FILENAME, true, "[REL] Update " . COPYRIGHTS_FILENAME . " for $relVersion");
    } else {
        error('Copyrights update failed.');
    }
}

if ($isPre) {
    if (! $options['no-packaging'] && important_step("Build packages files")) {
        build_packages($packageVersion);
        echo color("\nMake sure these tarballs are tested by at least 3 different people.\n\n", 'cyan');
    } else {
        echo color("This was the last step.\n", 'cyan');
    }
} else {
    if (! $options['no-tagging']) {
        $tagAlreadyExists = tag_exists($tag, true);
        if ($tagAlreadyExists && important_step("The Tag '$tag' already exists: Delete the existing tag in order to create a new one")) {
            $commit_msg = "[REL] Deleting tag '$tag' in order to create a new one";
            if ($options['no-commit']) {
                print "Skipping actual commit ('$commit_msg') because no-commit = true\n";
            } else {
                delete_tag($tag, $commit_msg);
                $tagAlreadyExists = false;
                info(">> Tag '$tag' deleted.");
            }
        }
        if (! $tagAlreadyExists) {
            update_working_copy('.');
            $revision = get_revision(ROOT);
            if (important_step("Tag release using branch '$branch' at revision $revision")) {
                $commit_msg = '[REL] Tagging release';
                if ($options['no-commit']) {
                    print "Skipping actual commit ('$commit_msg') because no-commit = true\n";
                } else {
                    create_tag($tag, $commit_msg, $branch, $revision);
                    info(">> Tag '$tag' created.");
                }
            }
        }
    }

    if (! $options['no-packaging'] && important_step("Build packages files")) {
        build_packages($packageVersion);
    } else {
        info("This was the last step.\n");
    }
}

// Helper functions


/**
 *
 * Deletes a directory and its contents.
 *
 * @param string $dir directory to delete.
 * @return string|bool returns the filename that an error occurred in, false otherwise
 */

function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                @chmod($dir . "/" . $object, 0777);
                if (filetype($dir . "/" . $object) === 'dir') {
                    $error = rrmdir($dir . "/" . $object);
                    if ($error) {
                        return $error;
                    }
                } elseif (! @unlink($dir . "/" . $object)) {
                    return 'Could not delete ' . $dir . "/" . $object . "\n";
                }
            }
        }
        reset($objects);
        @unlink($dir . '/.DS_store');
        if (! @rmdir($dir)) {
            return 'Could not delete ' . $dir . "\n";
        }
    }
    return false;
}

/**
 *
 * Prepares and generates the release packages.
 *
 * @param string $releaseVersion Version of tiki that is being released.
 */

function build_packages($releaseVersion)
{
    global $options;


    $workDir = $_SERVER['HOME'] . '/tikipack';
    $fileName = 'tiki-' . $releaseVersion;
    $relDir = $workDir . '/' . $releaseVersion;
    $sourceDir = $relDir . '/' . $fileName;

    $debugPackaging = ! empty($options['debug-packaging']);

    // Execute a build script and stream its output.
    $runBuildScript = static function (
        string $script,
        array $args = [],
        array $env = []
    ) use ($sourceDir): void {
        $scriptPath = __DIR__ . '/../ci/' . $script;


        $command = array_merge(['bash', $scriptPath], $args);

        $process = new \Symfony\Component\Process\Process(
            $command,
            $sourceDir,
            $env
        );

        // Packaging can take longer than Symfony Process's default timeout.
        $process->setTimeout(null);

        $process->run(static function ($type, $buffer): void {
            echo $buffer;
        });

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                sprintf(
                    "Build script failed: %s (exit code: %s)\n%s",
                    $script,
                    $process->getExitCode(),
                    $process->getErrorOutput()
                )
            );
        }
    };

    echo "Setting up $workDir directory\n";

    if (
        ! is_dir($workDir)
        && ! mkdir($workDir, 0775, true)
        && ! is_dir($workDir)
    ) {
        throw new RuntimeException("Cannot create working directory: $workDir");
    }

    // Remove previous release files if they exist.
    if (is_dir($relDir)) {
        echo "Removing previous files\n";

        $shellout = rrmdir($relDir);

        if ($shellout) {
            throw new RuntimeException($shellout);
        }
    }

    if (! mkdir($relDir, 0775, true) && ! is_dir($relDir)) {
        throw new RuntimeException("Cannot create release directory: $relDir");
    }

    // Export the requested Git revision.
    echo "Exporting working copy into $sourceDir\n";

    $shellout = export(ROOT, $sourceDir);

    if ($debugPackaging) {
        echo $shellout . "\n";
    }

    if (! is_file($sourceDir . '/' . PRIMARY_COMPOSERJSON_FILE_PATH)) {
        throw new RuntimeException('composer.json not found. Aborting.');
    }

    // Download and verify Composer.
    $composerPhar = $workDir . '/composer.phar';
    $composerInstaller = $workDir . '/composer-setup.php';

    $checksumContent = file_get_contents('https://composer.github.io/installer.sig');

    if ($checksumContent === false || trim($checksumContent) === '') {
        throw new RuntimeException('Unable to download Composer installer checksum.');
    }

    $checksum = trim($checksumContent);

    $installerContent = file_get_contents('https://getcomposer.org/installer');

    if ($installerContent === false) {
        throw new RuntimeException('Unable to download Composer installer.');
    }

    if (file_put_contents($composerInstaller, $installerContent) === false) {
        throw new RuntimeException("Cannot create Composer installer: $composerInstaller");
    }

    try {
        $installerHash = hash_file('sha384', $composerInstaller);

        if ($installerHash === false || ! hash_equals($checksum, $installerHash)) {
            throw new RuntimeException('Invalid Composer installer checksum.');
        }

        echo "Installing Composer\n";

        $process = new \Symfony\Component\Process\Process(
            [
                PHP_BINARY,
                $composerInstaller,
                '--quiet',
                '--2',
                '--install-dir=' . $workDir,
            ],
            $workDir
        );

        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            $message = "Composer installer failed.";

            if ($debugPackaging) {
                $message .= "\n" . $process->getOutput() . $process->getErrorOutput();
            }

            throw new RuntimeException($message);
        }

        if (! is_file($composerPhar)) {
            throw new RuntimeException('Composer PHAR was not created.');
        }
    } finally {
        if (is_file($composerInstaller) && ! unlink($composerInstaller)) {
            echo "Warning: Could not remove Composer installer: $composerInstaller\n";
        }
    }

    // Install Composer dependencies before building the frontend.
    $runBuildScript('build_package.sh', ['dependencies'], [
        'SOURCE_DIR' => $sourceDir,
        'COMPOSER_PHAR' => $composerPhar,
    ]);

    // Install frontend dependencies.
    $runBuildScript('build_frontend.sh', ['install'], [
        'SOURCE_DIR' => $sourceDir,
        'NPM_INSTALL_OPTIONS' => '--engine-strict',
    ]);

    // Build frontend assets.
    $runBuildScript('build_frontend.sh', ['build'], [
        'SOURCE_DIR' => $sourceDir,
    ]);

    // Clean up and create release archives.
    $runBuildScript('build_package.sh', ['package'], [
        'SOURCE_DIR' => $sourceDir,
        'OUTPUT_DIR' => $relDir,
        'PACKAGE_NAME' => $fileName,
        'PACKAGE_FORMATS' => 'tar.gz tar.bz2 tar.xz zip 7z',
    ]);

    // Display release-specific upload instructions.
    echo color(
        "\nTo upload the 'tarballs', copy-paste and execute the following line (and change '\$SF_LOGIN' by your SourceForge login):\n",
        'yellow'
    );

    echo color(
        "    cd $relDir; scp $fileName.* \$SF_LOGIN@frs.sourceforge.net:/home/pfs/project/t/ti/tikiwiki/\$RELEASEFOLDER\$\n",
        'yellow'
    );

    info(">> Packages files have been built in ~/tikipack/$releaseVersion\n");
}

/**
 * @return array|bool
 */
function get_options()
{
    if ($_SERVER['argc'] <= 1) {
        return false;
    }

    $argv = [];
    $options = [
        'howto' => false,
        'help' => false,
        'no-commit' => false,
        'no-check-vcs' => false,
        'no-first-update' => false,
        'no-lang-update' => false,
        'no-changelog-update' => false,
        'no-copyright-update' => false,
        'no-packaging' => false,
        'no-tagging' => false,
        'force-yes' => false,
        'debug-packaging' => false,
        'devmode' => false,
        'skip' => 0,
    ];

    // Environment variables provide default values for parameter options. e.g. export TIKI_NO_COMMIT=true
    $prefix = "TIKI-";
    foreach ($options as $option => $optValue) {
        $envOption = $prefix . $option;
        $envOption = str_replace("-", "_", $envOption);
        if (isset($_ENV[$envOption])) {
            $envValue = $_ENV[$envOption];
            $options[$option] = $envValue;
        }
    }

    foreach ($_SERVER['argv'] as $arg) {
        if (str_starts_with($arg, '--')) {
            if (($opt = substr($arg, 2)) != '' && isset($options[$opt])) {
                $options[$opt] = true;
            } elseif (str_contains($arg, '=')) {
                $parts = explode('=', substr($arg, 2));
                if (isset($options[$parts[0]])) {
                    $options[$parts[0]] = $parts[1];
                }
            } else {
                error("Unknown option $arg. Try using --help option.\n");
            }
        } else {
            $argv[] = $arg;
        }
    }
    $_SERVER['argv'] = $argv;
    unset($argv);

    if ($_SERVER['argc'] == 2) {
        $_SERVER['argv'][] = '';
    }

    return $options;
}

/**
 * @param $msg
 * @param bool $increment_step
 * @param bool $commit_msg
 * @return bool
 */
function important_step($msg, $increment_step = true, $commit_msg = false)
{
    global $options;
    static $step = 0;

    // Auto-Skip the step if this is a commit step and if there is nothing to commit
    if ($commit_msg && ! has_uncommited_changes('.')) {
        return false;
    }

    // Increment step number if needed
    if ($increment_step) {
        $step++;
    }

    if ($step <= $options['skip']) {
        print "Skipping step $step\n";
        return false;
    }

    if ($commit_msg && $options['no-commit']) {
        print "Skipping actual commit ('$commit_msg') because no-commit = true\n";
        return false;
    }

    if ($options['force-yes']) {
        important("\n$step) $msg...");
        $do_step = true;
    } else {
        important("\n$step) $msg?");

        $prompt = '[Y/n/q/?] ';
        if (function_exists('readline')) {
            // readline function requires php readline extension...
            $c = readline($prompt);
        } else {
            echo $prompt;
            $c = rtrim(fgets(STDIN), "\n");
        }

        switch (strtolower($c)) {
            case 'y':
            case '':
                $do_step = true;
                break;
            case 'n':
                info(">> Skipping step $step.");
                $do_step = false;
                break;
            case 'q':
                die;
                break;
            default:
                if ($c != '?') {
                    info(color(">> Unknown answer '$c'.", 'red'));
                }
                info(">> You have to type 'y' (Yes), 'n' (No) or 'q' (Quit) and press Enter.");
                return important_step($msg, false);
        }
    }

    if ($commit_msg && $do_step && ($revision = commit($commit_msg))) {
        info(">> Commited revision $revision.");
    }

    return $do_step;
}


function display_usage()
{
    echo "Usage: php src/devtools/release.php [ Options ] <version-number> [ <subrelease> ]
Examples:
    php src/devtools/release.php 2.0 preRC3
    php src/devtools/release.php 2.0 RC3
    php src/devtools/release.php 2.0

Options:
    --howto                   : display the Tiki release HOWTO
    --help                    : display this help
    --no-commit               : do not commit any changes back to GIT
    --no-check-vcs            : do not check if there are uncommitted changes on the checkout used for the release
    --no-first-update         : do not vcs update the checkout used for the release as the first step
    --no-lang-update          : do not update lang/*/language.php files
    --no-changelog-update     : do not update the '" . CHANGELOG_FILENAME . "' file
    --no-copyright-update     : do not update the '" . COPYRIGHTS_FILENAME . "' file
    --no-packaging            : do not build packages files
    --no-tagging              : do not tag the release on the remote vcs repository
    --force-yes               : disable the interactive mode (same as replying 'y' to all steps)
    --debug-packaging         : display debug output while in packaging step
    --devmode                 : equivalent to no-commit + no-check-vcs + no-first-update
    --skip=0                  : number of steps to skip when debugging (for use with -- devmode)
Notes:
    Subreleases begining with 'pre' will not be tagged.
";
    die;
}

function display_howto()
{
    echo <<<EOS
--------------------------
   HOWTO release Tiki
--------------------------

Please see: https://dev.tiki.org/How+to+release

EOS;
    shell_exec('open ' . escapeshellarg('https://dev.tiki.org/How+to+release'));
    exit;
}
