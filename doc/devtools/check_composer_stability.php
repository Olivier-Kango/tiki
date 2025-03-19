<?php

require_once __DIR__ . '/../../vendor_bundled/vendor/autoload.php';

/**
 * Get the current running Tiki version
 */
function getCurrentTikiVersion()
{
    $tikiPath = __DIR__ . '/../..';
    $versionFile = $tikiPath . '/lib/setup/twversion.class.php';
    if (file_exists($versionFile)) {
        require_once $versionFile;
        $TWV = new TWVersion();
        return $TWV->version;
    }
    return null;
}

/**
 * Get composer.json content safely
 */
function getComposerJson($source)
{
    if (empty($source['path']) && empty($source['url'])) {
        echo "Error: No valid path or URL for composer.json\n";
        return null;
    }

    if ($source['type'] === 'local' && ! empty($source['path'])) {
        if (! file_exists($source['path'])) {
            echo "Error: Local composer.json not found at {$source['path']}\n";
            return null;
        }
        $content = file_get_contents($source['path']);
        if ($content === false) {
            echo "Error: Could not read local composer.json\n";
            return null;
        }
        $json = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error: Invalid JSON in composer.json: " . json_last_error_msg() . "\n";
            return null;
        }
        return $json;
    } elseif ($source['type'] === 'remote' && ! empty($source['url'])) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => ['User-Agent: PHP/Tiki-Stability-Check'],
                'timeout' => 15
            ]
        ]);
        $content = file_get_contents($source['url'], false, $context);
        if ($content === false) {
            echo "Error: Could not fetch remote composer.json from {$source['url']}\n";
            return null;
        }
        $json = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error: Invalid JSON in remote composer.json: " . json_last_error_msg() . "\n";
            return null;
        }
        return $json;
    }
    return null;
}

/**
 * Get available Tiki versions
 */
function getAvailableTikiVersions()
{
    return ['24.x', '26.x', '27.x', '28.x', 'master'];
}

$currentVersion = getCurrentTikiVersion();
if (! $currentVersion) {
    echo "Error: Could not determine current Tiki version.\n";
    exit(1);
}

preg_match('/(\d+)\.(\d+)/', $currentVersion, $matches);
$currentMajor = isset($matches[1]) ? (int)$matches[1] : 0;
$currentMinor = isset($matches[2]) ? (int)$matches[2] : 0;
$currentBranch = "{$currentMajor}.x";

$versionsToCheck = array_filter(getAvailableTikiVersions(), function ($version) use ($currentMajor) {
    preg_match('/(\d+)/', $version, $match);
    return isset($match[1]) && (int)$match[1] >= $currentMajor;
});

$currentComposer = getComposerJson(['type' => 'local', 'path' => __DIR__ . '/../../vendor_bundled/composer.json']);
if (! $currentComposer) {
    echo "Error: Could not read current composer.json\n";
    exit(1);
}

$packageHistory = [];

foreach ($versionsToCheck as $version) {
    $targetComposer = getComposerJson([
        'url' => "https://gitlab.com/tikiwiki/tiki/-/raw/$version/vendor_bundled/composer.json",
        'type' => 'remote'
    ]);

    foreach ($currentComposer['require'] ?? [] as $package => $currentVersion) {
        if ($package === 'php' || strpos($package, 'ext-') === 0) {
            continue;
        }

        if (! isset($packageHistory[$package])) {
            $packageHistory[$package] = [];
        }

        $pkgVersion = $targetComposer['require'][$package] ?? 'removed';

        if (isset($packageHistory[$package]) && in_array('removed', $packageHistory[$package], true)) {
            continue; // Stop checking further if already removed in a prior version
        }

        $packageHistory[$package][$version] = $pkgVersion;
    }
}

// Filter out packages that never changed versions or were removed early
$filteredHistory = [];
foreach ($packageHistory as $package => $history) {
    $uniqueVersions = array_unique(array_values($history));

    // Exclude if version never changed and was only removed
    if (count($uniqueVersions) === 1 && end($uniqueVersions) === 'removed') {
        continue;
    }

    // Remove packages where the version never changed before removal
    if (count(array_unique(array_diff($uniqueVersions, ['removed']))) === 1) {
        continue;
    }

    // Stop checking at first "removed"
    foreach ($history as $tikiVersion => $pkgVersion) {
        if ($pkgVersion === 'removed') {
            $filteredHistory[$package] = array_slice($history, 0, array_search($tikiVersion, array_keys($history)) + 1, true);
            break;
        }
    }
    if (! isset($filteredHistory[$package])) {
        $filteredHistory[$package] = $history;
    }
}

$currentVersion = getCurrentTikiVersion();
echo "\nCurrent Tiki Version: {$currentVersion}\n";
echo "\nStability Warnings:\n==================\n";

if (empty($filteredHistory)) {
    echo "\n✅ No stability issues detected!\n";
    exit(0);  // Success - no issues found
} else {
    foreach ($filteredHistory as $package => $history) {
        echo "\n⚠️ {$package}: Version history\n";
        foreach ($history as $tikiVersion => $pkgVersion) {
            echo "   - Tiki {$tikiVersion}: {$pkgVersion}\n";
        }
    }
    echo "\n❌ Stability issues detected!\n";
    exit(1);  // Failure - stability issues found
}

echo "\n✅ Stability check complete!\n";
