<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

$composerLockPath = __DIR__ . '/../../vendor_bundled/composer.lock';

if (! file_exists($composerLockPath)) {
    echo "composer.lock not found" . PHP_EOL;
    exit(1);
}

$composerLock = json_decode(file_get_contents($composerLockPath));
$packages = array_merge($composerLock->packages ?? [], $composerLock->{'packages-dev'} ?? []);
$invalidPackages = [];

foreach ($packages as $package) {
    $isValid = empty($package->dist->url) || strtolower($package->type) === 'metapackage' || strncmp('https://composer.tiki.org', $package->dist->url, 25) === 0;

    if (! $isValid) {
        $invalidPackages[] = [
            'name' => $package->name,
            'url' => $package->dist->url ?? 'N/A'
        ];
    }
}

if (empty($invalidPackages)) {
    echo "Success: All packages dist.url in vendor_bundled/composer.lock points to https://composer.tiki.org." . PHP_EOL;
    exit(0);
} else {
    echo "Fail: Some packages in vendor_bundled/composer.lock are using urls that do not point to https://composer.tiki.org" . PHP_EOL;
    foreach ($invalidPackages as $package) {
        echo "Package: {$package['name']}, dist.url: {$package['url']}" . PHP_EOL;
    }
    echo "You will need to first add the packages to doc/devtools/satis.json so they are available in https://composer.tiki.org" . PHP_EOL;
    exit(1);
}
