<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$composerBin = __DIR__ . '/../../temp/composer.phar';
$composerLock = __DIR__ . '/../../vendor_bundled/composer.lock';
$vendorBundledPath = __DIR__ . '/../../vendor_bundled/';

if (! is_dir($vendorBundledPath)) {
    echo "vendor_bundled folder not found";
    exit(1);
}

if (! file_exists($composerBin)) {
    $composerBin = trim(exec('which composer'));
}

if (empty($composerBin)) {
    echo "composer.phar not found" . PHP_EOL;
    exit(1);
}

if (! file_exists($composerLock)) {
    echo "composer.lock not found" . PHP_EOL;
    exit(1);
}

$initial_md5 = md5_file($composerLock);
printf('Getting md5 from %s ...' . PHP_EOL . 'result: %s' . PHP_EOL, $composerLock, $initial_md5);
printf('Running composer update...' . PHP_EOL);
$output = null;
$exitCode = 0;
exec(sprintf('%s %s update --prefer-dist --working-dir=%s --no-progress --no-interaction', PHP_BINARY, $composerBin, $vendorBundledPath), $output, $exitCode);

if ($exitCode !== 0) {
    echo PHP_EOL . "Error: Failed to upgrade composer dependencies. Aborting." . PHP_EOL . PHP_EOL;
    exit($exitCode);
}

$final_md5 = md5_file($composerLock);
printf('Getting md5 from %s after composer update...' . PHP_EOL . 'result: %s' . PHP_EOL, $composerLock, $final_md5);

if ($initial_md5 !== $final_md5) {
    $jsonContent = json_decode(file_get_contents($composerLock));

    if (! empty($jsonContent->packages)) {
        $errors = [];
        foreach ($jsonContent->packages as $package) {
            if (! empty($package->type) && $package->type === "metapackage") {
                continue; // metapackage is a empty package and does not have dist.url
            }
            if (strrpos($package->dist->url, 'https://composer.tiki.org') !== 0) {
                $errors[] = "Package: " . $package->name . ", dist.url: " . $package->dist->url;
            }
        }
        if (count($errors)) {
            echo PHP_EOL;
            foreach ($errors as $error) {
                echo $error . PHP_EOL;
            }
            echo PHP_EOL . "Error: composer.lock might contain packages from unverified sources. Aborting." . PHP_EOL . PHP_EOL;
            exit(1);
        }
    }

    printf("Vendor bundled dependencies updated");
} else {
    printf("Nothing to be done...");
}
