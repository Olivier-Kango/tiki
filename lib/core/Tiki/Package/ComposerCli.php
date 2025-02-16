<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Package;

use Symfony\Component\Process\Exception\ExceptionInterface as ProcessExceptionInterface;
use Tiki\Process\PhpExecutableFinder;
use Tiki\Process\Process;

/**
 * Wrapper to composer.phar to allow installation of packages from the admin interface
 */
class ComposerCli
{
    public const COMPOSER_URL = 'https://getcomposer.org/installer';
    public const COMPOSER_SETUP = 'temp/composer-setup.php';
    public const COMPOSER_PHAR = 'temp/composer.phar';
    public const COMPOSER_CONFIG = 'composer.json';
    public const COMPOSER_LOCK = 'composer.lock';
    public const COMPOSER_HOME = 'temp/composer';

    public const FALLBACK_COMPOSER_JSON_TEMPLATE = '{"minimum-stability": "stable","config": {"process-timeout": 5000,"bin-dir": "bin","component-dir": "vendor/components", "prepend-autoloader": false, "platform": {"php":">=%TIKI_MIN_PHP_VERSION%"}}, "repositories": [{"type": "composer","url": "https://composer.tiki.org"}]}';

    /**
     * @var string path to the base folder from tiki
     */
    protected $basePath = '';

    /**
     * @var string path to the folder that will be used
     */
    protected $workingPath = '';

    /**
     * @var PhpExecutableFinder Will hold the php executable finder
     */
    protected PhpExecutableFinder $phpExecutableFinder;

    /**
     * @var int timeout in seconds waiting for composer commands to execute, default 5 min (300s)
     */
    protected $timeout = 300;

    /**
     * @var null|array Result from last execution null if never executed, else an array with command, output, errors and code
     */
    protected $lastResult = null;

    /**
     * @var PackageInformationCache Cache for composer and package information
     */
    protected PackageInformationCache $cache;

    /**
     * ComposerCli constructor.
     *
     * @param string                   $basePath
     * @param string|null              $workingPath
     * @param PhpExecutableFinder|null $phpExecutableFinder
     * @param PackageInformationCache|null $cache
     */
    public function __construct(string $basePath, ?string $workingPath = null, ?PhpExecutableFinder $phpExecutableFinder = null, ?PackageInformationCache $cache = null)
    {
        $basePath = rtrim($basePath, '/');
        if ($basePath) {
            $this->basePath = $basePath . '/';
        }

        if (is_null($workingPath)) {
            $this->workingPath = $this->basePath;
        } else {
            $workingPath = rtrim($workingPath, '/');
            if ($workingPath) {
                $this->workingPath = $workingPath . '/';
            }
        }

        if (! $phpExecutableFinder) {
            $phpExecutableFinder = new PhpExecutableFinder();
        }
        $this->phpExecutableFinder = $phpExecutableFinder;

        if (is_null($cache)) {
            $cache = new PackageInformationCache();
        }
        $this->cache = $cache;
    }

    /**
     * Returns the current working path location
     * @return string
     */
    public function getWorkingPath()
    {
        return $this->workingPath;
    }

    /**
     * Sets the current working path location
     */
    public function setWorkingPath($path)
    {
        $this->workingPath = $path;
    }

    /**
     * Returns the location of the composer.json file
     * @return string
     */
    public function getComposerConfigFilePath()
    {
        return $this->workingPath . self::COMPOSER_CONFIG;
    }

    /**
     * Returns the location of the composer.lock file
     * @return string
     */
    public function getComposerLockFilePath()
    {
        return $this->workingPath . self::COMPOSER_LOCK;
    }

    /**
     * Return the composer.json parsed as array, false if the file can not be processed
     * @return bool|array
     */
    protected function getComposerConfig()
    {
        if (! $this->checkConfigExists()) {
            return false;
        }
        return json_decode(file_get_contents($this->getComposerConfigFilePath()), true);
    }

    /**
     * Return the composer.json.dist parsed or default minimal setup
     * @return array
     */
    protected function getComposerDistConfig()
    {
        $distFile = $this->workingPath . self::COMPOSER_CONFIG . '.dist';
        $distContent = [];
        if (file_exists($distFile)) {
            $distContent = json_decode(file_get_contents($distFile), true);
            if (! is_array($distContent)) {
                $distContent = [];
            }
        }

        if (empty($distContent)) {
            $distContent = json_decode(
                str_replace('%TIKI_MIN_PHP_VERSION%', $this->phpExecutableFinder->getMinimalVersionSupported(), ComposerCli::FALLBACK_COMPOSER_JSON_TEMPLATE),
                true
            );
        }

        return $distContent;
    }

    /**
     * Return the composer.json parsed as array, or a default version for the composer.json if do not exists
     * First try to load the dist version, if not use a hardcoded version with the minimal setup
     * @return array|bool
     */
    public function getComposerConfigOrDefault()
    {
        $content = $this->getComposerConfig();
        if (! is_array($content)) {
            $content = [];
        }

        $distContent = $this->getComposerDistConfig();

        return array_merge($distContent, $content);
    }

    /**
     * Return the location of the composer.phar file (in the temp folder, as downloaded by setup.sh)
     * @return string
     */
    public function getComposerPharPath()
    {
        return $this->basePath . self::COMPOSER_PHAR;
    }

    /**
     * Check the version of the command line version of PHP
     *
     * @param string|null $php
     * @return string
     */
    public function getPhpVersion($php = null)
    {
        return $this->phpExecutableFinder->getPhpVersion($php);
    }

    /**
     * Evaluates if composer can be executed
     *
     * @return bool
     */
    public function canExecuteComposer()
    {
        static $canExecute = null;
        if (! is_null($canExecute)) {
            return $canExecute;
        }

        $canExecute = false;

        if ($this->composerPharExists()) {
            list($output) = $this->execComposer(['--no-ansi', '--version']);
            if (strncmp($output, 'Composer', 8) == 0) {
                $canExecute = true;
            }
        }

        return $canExecute;
    }

    /**
     * Check if composer.phar exists
     *
     * @return bool
     */
    public function composerPharExists()
    {
        return file_exists($this->getComposerPharPath());
    }

    /**
     * Execute Composer
     *
     * @param $args
     * @return array
     */
    protected function execComposer($args)
    {
        global $prefs;

        if (! is_array($args)) {
            $args = [$args];
        }

        $command = $output = $errors = '';

        try {
            $composerPath = $this->getComposerPharPath();
            array_unshift($args, $composerPath);

            $cmd = $this->phpExecutableFinder->find();
            if ($cmd) {
                array_unshift($args, $cmd);
            }

            if (! getenv('COMPOSER_HOME')) {
                $env['COMPOSER_HOME'] = $this->basePath . self::COMPOSER_HOME;
            }

            if (! getenv('COMPOSER_ALLOW_SUPERUSER')) {
                $env['COMPOSER_ALLOW_SUPERUSER'] = 1;
            }

            if ($prefs['use_proxy'] == 'y') {
                $env['HTTP_PROXY'] = $this->buildProxyUrl();
            }

            $process = new Process($args, null, $env);
            $command = $process->getCommandLine();
            $process->setTimeout($this->timeout);
            $process->run();

            $code = $process->getExitCode();

            $output = $process->getOutput();
            $errors = $process->getErrorOutput();
        } catch (ProcessExceptionInterface $e) {
            $errors .= $e->getMessage();
            $code = 1;
        }

        $this->lastResult = [
            'command' => $command,
            'output' => $output,
            'errors' => $errors,
            'code' => $code
        ];

        return [$output, $errors, $code];
    }

    /**
     * Execute show command
     *
     * @return array
     */
    protected function execShow()
    {
        if (! $this->canExecuteComposer()) {
            return [];
        }
        list($result) = $this->execComposer(['--format=json', 'show', '-d', $this->workingPath]);
        $json = json_decode($result, true);

        return $json;
    }

    /**
     * Execute Clear-Cache command
     *
     * @return array
     */
    public function execClearCache()
    {
        if (! $this->canExecuteComposer()) {
            return [];
        }
        list(, $errors, ) = $this->execComposer(['clear-cache']);

        return $errors;
    }


    /**
     * Check if the composer.json file exists
     *
     * @return bool
     */
    public function checkConfigExists()
    {
        return file_exists($this->getComposerConfigFilePath());
    }

    /**
     * Retrieve list of packages in composer.json
     *
     * @return array|bool
     */
    public function getListOfPackagesFromConfig()
    {
        $cacheKey = [__CLASS__, $this->getWorkingPath(), 'getListOfPackagesFromConfig'];
        $cachedResult = $this->cache->get($cacheKey, null);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        if (! $this->checkConfigExists()) {
            $this->cache->set($cacheKey, false);
            return false;
        }

        $content = json_decode(file_get_contents($this->getComposerConfigFilePath()), true);
        $composerShow = $this->execShow();

        $installedPackages = [];
        if (isset($composerShow['installed']) && is_array($composerShow['installed'])) {
            foreach ($composerShow['installed'] as $package) {
                $installedPackages[$this->normalizePackageName($package['name'])] = $package;
            }
        }

        $result = [];
        if (isset($content['require']) && is_array($content['require'])) {
            foreach ($content['require'] as $name => $version) {
                if (isset($installedPackages[$this->normalizePackageName($name)])) {
                    $result[] = [
                        'name' => $name,
                        'status' => ComposerManager::STATUS_INSTALLED,
                        'required' => $version,
                        'installed' => $installedPackages[$name]['version'],
                    ];
                } else {
                    $result[] = [
                        'name' => $name,
                        'status' => ComposerManager::STATUS_MISSING,
                        'required' => $version,
                        'installed' => '',
                    ];
                }
            }
        }

        $this->cache->set($cacheKey, $result);

        return $result;
    }

    /**
     * Get list of packages from the composer.lock file
     * @return array|bool
     */
    public function getListOfPackagesFromLock()
    {
        $cacheKey = [__CLASS__, $this->getWorkingPath(), 'getListOfPackagesFromLock'];
        $cachedResult = $this->cache->get($cacheKey, null);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        if (! $this->checkConfigExists()) {
            $this->cache->set($cacheKey, false);
            return false;
        }

        $content = json_decode(file_get_contents($this->getComposerLockFilePath()), true);
        $packagesFromConfig = json_decode(file_get_contents($this->getComposerConfigFilePath()), true);

        if (empty($content['packages']) || empty($packagesFromConfig)) {
            $this->cache->set($cacheKey, []);
            return [];
        }

        // We will create a map with the required values to prevent extra logic afterwards
        $configRequiredMap = [];
        foreach ($packagesFromConfig['require'] as $packageName => $packageVersion) {
            $configRequiredMap[$packageName] = $packageVersion;
        }

        $result = [];
        foreach ($content['packages'] as $package) {
            if (! isset($configRequiredMap[$package['name']])) {
                continue;
            }

            $result[$package['name']] = [
                'name'      => $package['name'],
                'status'    => ComposerManager::STATUS_INSTALLED,
                'required'  => $configRequiredMap[$package['name']],
                'installed' => $package['version'],
            ];
        }

        $this->cache->set($cacheKey, $result);

        return $result;
    }

    /**
     * Ensure packages configured in composer.json are installed
     *
     * @return bool
     */
    public function installMissingPackages()
    {
        global $tikipath;
        if (! $this->checkConfigExists() || ! $this->canExecuteComposer()) {
            return false;
        }

        $exe = ['--no-ansi', '--no-dev', '--prefer-dist', 'update', '-d', $this->workingPath];
        if (is_dir($tikipath . 'vendor_bundled/vendor/phpunit')) {
            $exe = ['--no-ansi', '--prefer-dist', 'update', '-d', $this->workingPath];
        }

        list($output, $errors) = $this->execComposer($exe);

        $this->cache->clear();

        return $this->glueOutputAndErrors($output, $errors);
    }

    /**
     * Execute the diagnostic command
     *
     * @return array|bool
     */
    public function execDiagnose()
    {
        if (! $this->canExecuteComposer()) {
            return false;
        }

        list($output, $errors) = $this->execComposer(['--no-ansi', 'diagnose', '-d', $this->workingPath]);

        return $this->glueOutputAndErrors($output, $errors);
    }

    /**
     * Install a package (from the package definition)
     *
     * @param ComposerPackage $package
     * @return bool|string
     */
    public function installPackage(ComposerPackage $package)
    {
        if (! $this->canExecuteComposer()) {
            return false;
        }

        $this->verifyMinPhpVersion();

        $composerJson = $this->getComposerConfigOrDefault();
        $composerJson = $this->addComposerPackageToJson(
            $composerJson,
            $package->getName(),
            $package->getRequiredVersion(),
            $package->getScripts()
        );

        $composerJson = $this->sanitizeEmptyArrays($composerJson);

        $fileContent = json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($this->getComposerConfigFilePath(), $fileContent);

        $commandOutput = $this->installMissingPackages();

        return tr('= New composer.json file content') . ":\n\n"
        . $fileContent . "\n\n"
        . tr('= Composer execution output') . ":\n\n"
        . $commandOutput;
    }

    /**
     * Update a package required version (from the package definition)
     *
     * @param ComposerPackage $package
     * @return bool
     */
    public function updatePackage(ComposerPackage $package)
    {

        if (! $this->canExecuteComposer() || ! $this->checkConfigExists()) {
            return false;
        }

        $this->verifyMinPhpVersion();

        list($commandOutput, $errors) = $this->execComposer(
            ['require', $package->getName() . ':' . $package->getRequiredVersion(), '--update-no-dev', '-d', $this->workingPath, '--no-ansi', '--no-interaction']
        );

        $this->cache->clear();

        $fileContent = file_get_contents($this->getComposerConfigFilePath());

        return tr('= New composer.json file content') . ":\n\n"
            . $fileContent . "\n\n"
            . tr('= Composer execution output') . ":\n\n"
            . $this->glueOutputAndErrors($commandOutput, $errors);
    }

    /**
     * Remove a package (from the package definition)
     *
     * @param ComposerPackage $package
     * @return bool|string
     */
    public function removePackage(ComposerPackage $package)
    {
        if (! $this->canExecuteComposer() || ! $this->checkConfigExists()) {
            return false;
        }

        $this->verifyMinPhpVersion();

        list($commandOutput, $errors) = $this->execComposer(
            ['remove', $package->getName(), '--update-no-dev', '-d', $this->workingPath, '--no-ansi', '--no-interaction']
        );

        $this->cache->clear();

        $fileContent = file_get_contents($this->getComposerConfigFilePath());

        return tr('= New composer.json file content') . ":\n\n"
        . $fileContent . "\n\n"
        . tr('= Composer execution output') . ":\n\n"
        . $this->glueOutputAndErrors($commandOutput, $errors);
    }


    /**
     * Append a package to composer.json
     *
     * @param $composerJson
     * @param $package
     * @param $version
     * @param array $scripts
     * @return array
     */
    public function addComposerPackageToJson($composerJson, $package, $version, $scripts = [])
    {

        $scriptsKeys = [
            'pre-install-cmd',
            'post-install-cmd',
            'pre-update-cmd',
            'post-update-cmd',
        ];

        if (! is_array($composerJson)) {
            $composerJson = [];
        }
        // require
        if (! isset($composerJson['require'])) {
            $composerJson['require'] = [];
        }
        if (! isset($composerJson['require'][$package])) {
            $composerJson['require'][$package] = $version;
        }

        // scripts
        if (is_array($scripts) && count($scripts)) {
            if (! isset($composerJson['scripts'])) {
                $composerJson['scripts'] = [];
            }
            foreach ($scriptsKeys as $type) {
                if (! isset($scripts[$type])) {
                    continue;
                }
                $scriptList = $scripts[$type];
                if (is_string($scriptList)) {
                    $scriptList = [$scriptList];
                }
                if (! count($scriptList)) {
                    continue;
                }
                if (! isset($composerJson['scripts'][$type])) {
                    $composerJson['scripts'][$type] = [];
                }
                foreach ($scriptList as $scriptString) {
                    $composerJson['scripts'][$type][] = $scriptString;
                }
                $composerJson['scripts'][$type] = array_unique($composerJson['scripts'][$type]);
            }
        }

        return $composerJson;
    }

    /**
     * Normalize the package name
     *
     * @param string $packageName
     * @return string
     */
    public function normalizePackageName($packageName)
    {
        return strtolower($packageName);
    }

    /**
     * Sets the execution timeout for composer
     *
     * @param int $timeout max amount of seconds waiting for a composer command to finish
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int)$timeout;
    }

    /**
     * Retrieves the execution timeout for composer
     *
     * @return int return the value of timeout in seconds
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Returns the result of the last composer command executed
     *
     * @return array|null last result, null for never executed, array(command, output, error, code) if executed
     */
    public function getLastResult()
    {
        return $this->lastResult;
    }

    /**
     * Clear the information about the last execution result
     */
    public function clearLastResult()
    {
        $this->lastResult = null;
    }

    /**
     * Glue both output ans errors, Checking if the different parts are not empty
     * @param $output
     * @param $errors
     * @return string
     */
    protected function glueOutputAndErrors($output, $errors)
    {
        $string = $output;

        if (! empty($errors)) {
            if (! empty($string)) {
                $string .= "\n";
            }
            $string .= tr('Errors:') . "\n" . $errors;
        }
        return $string;
    }

    /**
     * Add composer.phar to temp/ folder
     */
    public function installComposer()
    {
        $expectedSig = trim(file_get_contents('https://composer.github.io/installer.sig'));

        if (! copy(self::COMPOSER_URL, self::COMPOSER_SETUP)) {
            return [false, tr('Unable to download composer installer from %0', self::COMPOSER_URL)];
        }

        $actualSig = hash_file('SHA384', self::COMPOSER_SETUP);

        if ($expectedSig !== $actualSig) {
            unlink(self::COMPOSER_SETUP);
            return [false, tr('Invalid composer installer signature.')];
        }

        $env = null;
        if (! getenv('COMPOSER_HOME')) {
            $env['COMPOSER_HOME'] = $this->basePath . self::COMPOSER_HOME;
        }

        $command = [$this->phpExecutableFinder->find(), self::COMPOSER_SETUP, '--quiet', '--install-dir=temp'];
        $process = new Process($command, null, $env);
        $process->run();

        $output = $process->getOutput();
        $result = $process->isSuccessful();

        if ($result) {
            $message = tr('composer.phar installed in temp folder.');
        } else {
            $message = tr('There was a problem when installing Composer.');
        }

        if (! empty($output)) {
            $message .= '<br>' . str_replace("\n", '<br>', $output);
        }

        unlink(self::COMPOSER_SETUP);

        return [$result, $message];
    }

    /**
     * Add composer.phar to temp/ folder
     */
    public function updateComposer()
    {
        $env = null;
        if (! getenv('COMPOSER_HOME')) {
            $env['COMPOSER_HOME'] = $this->basePath . self::COMPOSER_HOME;
        }

        $command = [$this->getComposerPharPath(), 'self-update', '--no-progress'];
        $process = new Process($command, null, $env);
        $process->start();
        $output = '';
        foreach ($process as $type => $data) {
            $output .= $data;
        }

        $result = $process->isSuccessful();
        $message = str_replace("\n", '<br>', trim($output));

        return [$result, $message];
    }

    /**
     * Compare existing config with dist config for necessary updates that haven't been completed.
     */
    public function compareDistRequiredOptions()
    {
        $config = $this->getComposerConfig();
        if (! $config) {
            return;
        }

        $distConfig = $this->getComposerDistConfig();

        $diff = [];

        if (@$config['config']['platform']['php'] !== @$distConfig['config']['platform']['php']) {
            $diff['platform.php'] = [
                'existing' => isset($config['config']['platform']['php']) ? json_encode($config['config']['platform']['php']) : 'missing',
                'dist' => isset($distConfig['config']['platform']['php']) ? json_encode($distConfig['config']['platform']['php']) : 'missing',
            ];
        }
        if (@$config['config']['prepend-autoloader'] !== @$distConfig['config']['prepend-autoloader']) {
            $diff['prepend-autoloader'] = [
                'existing' => isset($config['config']['prepend-autoloader']) ? json_encode($config['config']['prepend-autoloader']) : 'missing',
                'dist' => isset($distConfig['config']['prepend-autoloader']) ? json_encode($distConfig['config']['prepend-autoloader']) : 'missing',
            ];
        }

        return $diff;
    }

    /**
     * Build and get proxy URL based on Tiki preferences.
     * @return string
     */
    private function buildProxyUrl()
    {
        global $prefs;

        $proxy = '';

        if (! empty($prefs['proxy_user']) && ! empty($prefs['proxy_pass'])) {
            $proxy .= $prefs['proxy_user'] . ':' . $prefs['proxy_pass'] . '@';
        }

        $proxy .= $prefs['proxy_host'];

        if (isset($prefs['proxy_port'])) {
            $proxy .= ':' . $prefs['proxy_port'];
        }

        return $proxy;
    }

    /**
     * Compare composer.json platform php version against Tiki supported min PHP version
     * and update composer.json if necessary.
     */
    private function verifyMinPhpVersion()
    {
        $config = $this->getComposerConfig();
        if (! $config) {
            return;
        }

        $distConfig = $this->getComposerDistConfig();

        if (! isset($config['config']['platform']['php'])) {
            return;
        }

        if (! isset($distConfig['config']['platform']['php'])) {
            return;
        }

        if (floatval($config['config']['platform']['php']) < floatval($distConfig['config']['platform']['php'])) {
            $config['config']['platform']['php'] = $distConfig['config']['platform']['php'];
            $fileContent = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($this->getComposerConfigFilePath(), $fileContent);
        }
    }

    /**
     * Convert empty array attributes to object to be used in json encode
     *
     * @param $composerAttributes
     * @return array
     */
    private function sanitizeEmptyArrays($composerAttributes)
    {
        $objectRequiredIdentifiers = ['require', 'require-dev', 'autoload', 'autoload-dev'];

        foreach ($objectRequiredIdentifiers as $identifier) {
            if (
                isset($composerAttributes[$identifier])
                && is_array($composerAttributes[$identifier])
                && empty($composerAttributes[$identifier])
            ) {
                $composerAttributes[$identifier] = (object) [];
            }
        }

        return $composerAttributes;
    }
}
