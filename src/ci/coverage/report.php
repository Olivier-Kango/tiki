<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Symfony\Component\Filesystem\Filesystem;
use TikiDevTools\Coverage\ChangedCodeCoverage;
use TikiDevTools\Coverage\CoverageReport;

if (PHP_SAPI !== 'cli') {
    die("This script must be run from the command line.\n");
}
require_once __DIR__ . '/../../../vendor_bundled/vendor/autoload.php';
require_once __DIR__ . '/ChangedCodeCoverage.php';
require_once __DIR__ . '/CoverageReport.php';

$options = getopt('', ['base:', 'output:', 'unavailable']);
$base = $options['base'] ?? (getenv('CI_MERGE_REQUEST_DIFF_BASE_SHA') ?: '');
$head = getenv('CI_MERGE_REQUEST_SOURCE_BRANCH_SHA') ?: null;
$output = $options['output'] ?? 'temp/ci/diff-coverage/index.html';
$filesystem = new Filesystem();
try {
    if (! is_string($base) || ($head !== null && ! is_string($head)) || ! is_string($output) || $output === '') {
        throw new RuntimeException('Invalid report options');
    }
    if (array_key_exists('unavailable', $options)) {
        $filesystem->dumpFile($output, CoverageReport::unavailable('Coverage reporting has not completed; check the reporting job.'));
        exit(0);
    }
    $result = (new ChangedCodeCoverage(getcwd(), getenv('DIFF_COVER_BIN') ?: 'diff-cover'))->generate($base, [
        'temp/ci/phpunit-coverage.xml',
        'temp/ci/js_coverage/cobertura-coverage.xml',
    ], $head);
    $filesystem->dumpFile($output, CoverageReport::render($result));
    printf(
        "MR diff coverage: %d covered / %d measured lines; %d uncovered; %d files without measured lines.\n",
        $result['total_num_lines'] - $result['total_num_violations'],
        $result['total_num_lines'],
        $result['total_num_violations'],
        count($result['unmeasured_files'])
    );
} catch (Throwable $error) {
    $reason = $error instanceof RuntimeException ? $error->getMessage() : 'Report processing failed';
    try {
        $filesystem->dumpFile($output, CoverageReport::unavailable($reason));
    } catch (Throwable $writeError) {
        fwrite(STDERR, "Could not write changed-code coverage report.\n");
        exit(1);
    }
    fwrite(STDERR, 'Coverage unavailable: ' . $reason . "\n");
    exit(1);
}
