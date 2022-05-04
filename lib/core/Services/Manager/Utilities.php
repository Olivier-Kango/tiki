<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

use TikiManager\Application\Instance;
use TikiManager\Libs\VersionControl\Git;
use TikiManager\Libs\VersionControl\Svn;
use TikiManager\Libs\VersionControl\Src;

class Services_Manager_Utilities
{
    use Services_Manager_Trait;

    public function loadEnv() {
        $this->loadManagerEnv();
        $this->setManagerOutput();
    }

    public function getManagerOutput() {
        return $this->manager_output;
    }

    public static function getAvailableTikiVersions()
    {
        $dir = getcwd();
        chdir('..');

        $instances = Instance::getInstances(false);
        $instance = reset($instances);

        $available = [];
        $vcs = null;

        $output = `git --version`;
        if (strstr($output, 'version')) {
            $vcs = new Git($instance);
        }

        if (! $vcs) {
            $output = `svn --version`;
            if (strstr($output, 'version')) {
                $vcs = new Svn($instance);
            }
        }

        if (! $vcs) {
            $vcs = new Src($instance);
        }

        $versions = $vcs->getAvailableBranches();
        foreach ($versions as $key => $version) {
            preg_match('/(\d+\.|trunk|master)/', $version->branch, $matches);
            if (!array_key_exists(0, $matches)) {
                continue;
            }
            $available[] = $version->branch;
        }

        chdir($dir);

        return $available;
    }
}
