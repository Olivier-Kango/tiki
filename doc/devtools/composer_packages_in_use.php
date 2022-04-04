<?php
/*
 * Find a list of all the vendor_bundled pagages in use in all (relevant) versions
 * in order to delete unused files from composer.tiki.org (which is full!)
 */

if (PHP_SAPI !== 'cli') {
    die("Please run from a shell");
}

class ComposerRepoPurge {

    private array $versions;

    private string $repoUri;

    private \GitLib $gitLib;

    public function __construct()
    {
        $this->tikiRoot = dirname(dirname(__DIR__));
        $this->versions = [];
        array_unshift($this->versions, 'master');
        $this->repoUri = 'https://gitlab.com/tikiwiki/tiki/-/raw/%branch%/vendor_bundled/composer.lock';
        $this->gitLib = \TikiLib::lib('git');
    }

    public function execute() {
        echo 'Getting branches...';
        $branches = $this->getBranches();
        echo ' done (' . count($branches) . " found)\n";
        ob_flush();

        $packagesInUse = [];

        echo 'Reading';
        foreach ($branches as $branch) {
            $json = $this->loadComposerLock($branch);
            echo '.';
            ob_flush();

            if ($json) {
                $vendorData = $this->parseComposerLock($json);
                foreach ($vendorData->packages as $package) {
                    if (! isset($packagesInUse[$package->name])) {
                        $packagesInUse[$package->name] = [];
                    }
                    if (! in_array($package->version, $packagesInUse[$package->name])) {
                        $packagesInUse[$package->name][] = $package->version;
                    }
                }
                if ($vendorData->{'packages - dev'} ) {     // maybe this doesn't really exist?
                    foreach ($vendorData->{'packages - dev'} as $package) {
                        if (! isset($packagesInUse[$package->name])) {
                            $packagesInUse[$package->name] = [];
                        }
                        if (! in_array($package->version, $packagesInUse[$package->name])) {
                            $packagesInUse[$package->name][] = $package->version;
                        }
                    }
                }
            }

        }
        echo "\nDone\n\n";
        ob_flush();

        foreach($packagesInUse as $packageName => $versions) {
            sort($versions);
            foreach ($versions as $version) {
                echo "$packageName/$version\n";
            }
        }
    }

    private function getBranches() {

        $output = $this->gitLib->run_git(['ls-remote']);
        $r = preg_match_all('/.*refs\/heads\/(.*)$/m', $output, $remotes);
        if ($r) {
            $this->versions = $remotes[1];
        }
        return $this->versions;
    }

    private function loadComposerLock(string $branch): string
    {
        $uri = str_replace('%branch%', $branch, $this->repoUri);
        $json = @file_get_contents($uri);
        return $json;
    }

    private function parseComposerLock(string $json): ?object
    {
        $data = json_decode($json);

        return $data;
    }

}


require_once('tiki-setup.php');

$purger = new ComposerRepoPurge();
$purger->execute();

