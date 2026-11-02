<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\CI;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Tiki\Process\Process;
use TikiDevTools\Coverage\ChangedCodeCoverage;
use TikiDevTools\Coverage\CoberturaReport;
use TikiDevTools\Coverage\CoverageReport;

require_once __DIR__ . '/../../../src/ci/coverage/ChangedCodeCoverage.php';
require_once __DIR__ . '/../../../src/ci/coverage/CoverageReport.php';

class ChangedCodeCoverageTest extends TestCase
{
    private string $root;
    private string $base;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir() . '/tiki-coverage-test-' . bin2hex(random_bytes(8));
        $this->filesystem->mkdir([$this->root . '/lib', $this->root . '/src/js', $this->root . '/templates', $this->root . '/hooks'], 0700);
        $commands = [
            ['init', '-q'],
            ['config', 'user.name', 'Coverage test'],
            ['config', 'user.email', 'coverage@example.invalid'],
            ['config', 'commit.gpgSign', 'false'],
            ['config', 'core.hooksPath', $this->root . '/hooks'],
        ];
        foreach ($commands as $command) {
            $this->git($command);
        }
        $this->filesystem->dumpFile($this->root . '/lib/example.php', "<?php\nreturn 1;\n");
        $this->filesystem->dumpFile($this->root . '/src/js/example.js', "export const value = 1;\n");
        $this->filesystem->dumpFile($this->root . '/templates/example.tpl', "Original\n");
        $this->commit();
        $this->base = trim($this->git(['rev-parse', 'HEAD']));
        $this->filesystem->dumpFile($this->root . '/lib/example.php', "<?php\n\$value = 2;\nreturn \$value;\n");
        $this->filesystem->dumpFile($this->root . '/src/js/example.js', "export const value = 2;\n");
        $this->filesystem->dumpFile($this->root . '/templates/example.tpl', "Changed\n");
        $this->commit();
        $this->phpCoverage($this->root . '/lib');
        $this->filesystem->dumpFile($this->root . '/js.xml', '<coverage><classes><class filename="src/js/example.js"><lines><line number="1" hits="1"/></lines></class></classes></coverage>');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    private function git(array $arguments): string
    {
        $process = new Process(array_merge(['git'], $arguments), $this->root);
        $process->mustRun();
        return $process->getOutput();
    }

    private function commit(): void
    {
        $this->git(['add', '-A', 'lib', 'src', 'templates']);
        $this->git(['commit', '-qm', 'Fixture']);
    }

    private function phpCoverage(string $source, int $lastLineHits = 0): void
    {
        $this->cobertura($this->root . '/php.xml', $source, 'example.php', [2 => 1, 3 => $lastLineHits]);
    }

    private function cobertura(string $path, string $source, string $filename, array $lines): void
    {
        $lineXml = '';
        foreach ($lines as $number => $hits) {
            $lineXml .= '<line number="' . $number . '" hits="' . $hits . '"/>';
        }
        $this->filesystem->dumpFile($path, '<?xml version="1.0"?><!DOCTYPE coverage SYSTEM "http://cobertura.sourceforge.net/xml/coverage-04.dtd">'
            . '<coverage><sources><source>' . htmlspecialchars($source, ENT_XML1) . '</source></sources><classes><class filename="'
            . htmlspecialchars($filename, ENT_XML1) . '"><lines>' . $lineXml . '</lines></class></classes></coverage>');
    }

    private function engineRejectingPath(string $forbiddenPath): string
    {
        $engine = $this->root . '/diff-cover';
        $this->filesystem->dumpFile($engine, '#!/usr/bin/env php' . "\n" . '<?php' . "\n"
            . '$diff = null; $report = null;' . "\n"
            . 'foreach ($argv as $index => $argument) {' . "\n"
            . "    if (\$argument === '--diff-file') { \$diff = \$argv[\$index + 1] ?? null; }" . "\n"
            . "    if (str_starts_with(\$argument, 'json:')) { \$report = substr(\$argument, 5); }" . "\n"
            . '}' . "\n"
            . 'if ($diff === null || $report === null || str_contains((string) file_get_contents($diff), ' . var_export('+++ b/' . $forbiddenPath, true) . ')) { exit(7); }' . "\n"
            . "file_put_contents(\$report, '{\"total_num_lines\":0,\"total_num_violations\":0,\"total_percent_covered\":0,\"src_stats\":{}}');" . "\n");
        chmod($engine, 0700);
        return $engine;
    }

    private function report(?string $base = null): array
    {
        $engine = getenv('DIFF_COVER_BIN');
        if (! $engine) {
            $this->markTestSkipped('Set DIFF_COVER_BIN to the hash-pinned engine to run reporting integration tests.');
        }
        return (new ChangedCodeCoverage($this->root, $engine))->generate($base ?? $this->base, [$this->root . '/php.xml', $this->root . '/js.xml']);
    }

    public function testCombinedTotalsAndMeasurementScope(): void
    {
        $result = $this->report();
        $this->assertSame(3, $result['total_num_lines']);
        $this->assertSame(1, $result['total_num_violations']);
        $this->assertEqualsWithDelta(66.67, $result['total_percent_covered'], 0.001);
        $this->assertSame(['templates/example.tpl'], $result['unmeasured_files']);
        $this->assertSame($this->base, $result['diff_base']);
        $this->assertSame(trim($this->git(['rev-parse', 'HEAD'])), $result['diff_head']);
        $this->assertStringContainsString('66.67%', CoverageReport::render($result));
    }

    public function testArtifactsFromAnotherRunnerRetainBothLanguages(): void
    {
        $this->phpCoverage('/builds/another-runner/tiki/lib');
        $result = $this->report();
        $this->assertSame(3, $result['total_num_lines']);
        $this->assertSame(1, $result['total_num_violations']);
        $this->assertArrayHasKey('lib/example.php', $result['src_stats']);
    }

    public function testReportCommandUsesMergeComparisonInAMergedResultPipeline(): void
    {
        $source = trim($this->git(['rev-parse', 'HEAD']));
        $this->git(['checkout', '-qb', 'target', $this->base]);
        $this->filesystem->dumpFile($this->root . '/lib/target-only.php', "<?php\nreturn 3;\n");
        $this->commit();
        $target = trim($this->git(['rev-parse', 'HEAD']));
        $this->git(['merge', '--no-ff', '--no-edit', $source]);
        $merge = trim($this->git(['rev-parse', 'HEAD']));
        $this->filesystem->dumpFile($this->root . '/php.xml', '<coverage><sources><source>' . htmlspecialchars($this->root . '/lib', ENT_XML1)
            . '</source></sources><classes><class filename="example.php"><lines><line number="2" hits="1"/><line number="3" hits="0"/></lines></class>'
            . '<class filename="target-only.php"><lines><line number="2" hits="1"/></lines></class></classes></coverage>');
        $this->filesystem->mkdir($this->root . '/temp/ci/js_coverage');
        $this->filesystem->copy($this->root . '/php.xml', $this->root . '/temp/ci/phpunit-coverage.xml');
        $this->filesystem->copy($this->root . '/js.xml', $this->root . '/temp/ci/js_coverage/cobertura-coverage.xml');
        $output = $this->root . '/report/index.html';
        $script = realpath(__DIR__ . '/../../../src/ci/coverage/report.php');
        $process = new Process([PHP_BINARY, $script, '--output', $output], $this->root, [
            'CI_MERGE_REQUEST_DIFF_BASE_SHA' => $this->base,
            'CI_MERGE_REQUEST_TARGET_BRANCH_SHA' => $target,
            'DIFF_COVER_BIN' => $this->engineRejectingPath('lib/target-only.php'),
        ]);
        $process->mustRun();

        $html = file_get_contents($output);
        $this->assertStringContainsString('Comparison head: <code>' . $merge . '</code>', $html);
        $this->assertStringContainsString('Comparison base: <code>' . $target . '</code>', $html);
        $this->assertStringNotContainsString('lib/target-only.php', $html);
    }

    public function testFullyCoveredChanges(): void
    {
        $this->phpCoverage($this->root . '/lib', 1);
        $result = $this->report();
        $this->assertSame(0, $result['total_num_violations']);
        $this->assertEquals(100, $result['total_percent_covered']);
    }

    public function testZeroMeasuredLinesDoNotClaimFullCoverage(): void
    {
        $base = trim($this->git(['rev-parse', 'HEAD']));
        $this->filesystem->dumpFile($this->root . '/templates/example.tpl', "Template only\n");
        $this->commit();
        $result = $this->report($base);
        $this->assertSame(0, $result['total_num_lines']);
        $this->assertNull($result['total_percent_covered']);
        $this->assertSame(['templates/example.tpl'], $result['unmeasured_files']);
    }

    public function testDeletedLinesAreExcluded(): void
    {
        $base = trim($this->git(['rev-parse', 'HEAD']));
        $this->filesystem->remove($this->root . '/templates/example.tpl');
        $this->commit();
        $result = $this->report($base);
        $this->assertSame(0, $result['total_num_lines']);
        $this->assertSame([], $result['unmeasured_files']);
    }

    public function testNumericUnmeasuredFilenameIsNotDiscarded(): void
    {
        $base = trim($this->git(['rev-parse', 'HEAD']));
        $this->filesystem->dumpFile($this->root . '/0', "Data only\n");
        $this->git(['add', '0']);
        $this->commit();
        $result = $this->report($base);
        $this->assertSame(['0'], $result['unmeasured_files']);
        $this->assertNull($result['total_percent_covered']);
    }

    public function testStagedAndUnstagedChangesAreExcluded(): void
    {
        $this->filesystem->dumpFile($this->root . '/lib/uncommitted.php', "<?php\nreturn 3;\n");
        $this->git(['add', 'lib/uncommitted.php']);
        $this->filesystem->dumpFile($this->root . '/src/js/example.js', "export const value = 999;\n");
        $result = $this->report();
        $this->assertSame(3, $result['total_num_lines']);
        $this->assertArrayNotHasKey('lib/uncommitted.php', $result['src_stats']);
        $this->assertNotContains('lib/uncommitted.php', $result['unmeasured_files']);
    }

    public function testCommentOnlyChangesHaveNoMeasuredLines(): void
    {
        $base = trim($this->git(['rev-parse', 'HEAD']));
        $this->filesystem->dumpFile($this->root . '/lib/example.php', "<?php\n// Comment only.\n\$value = 2;\nreturn \$value;\n");
        $this->commit();
        $this->cobertura($this->root . '/php.xml', $this->root . '/lib', 'example.php', [3 => 1, 4 => 0]);
        $result = $this->report($base);
        $this->assertSame(0, $result['total_num_lines']);
        $this->assertNull($result['total_percent_covered']);
    }

    public function testRenameOnlyChangesHaveNoMeasuredLines(): void
    {
        $base = trim($this->git(['rev-parse', 'HEAD']));
        $this->filesystem->rename($this->root . '/lib/example.php', $this->root . '/lib/renamed.php');
        $this->commit();
        $this->cobertura($this->root . '/php.xml', $this->root . '/lib', 'renamed.php', [2 => 1, 3 => 0]);
        $result = $this->report($base);
        $this->assertSame(0, $result['total_num_lines']);
        $this->assertNull($result['total_percent_covered']);
    }

    public function testRenameWithExecutableChangesUsesRenamedPath(): void
    {
        $base = trim($this->git(['rev-parse', 'HEAD']));
        $this->filesystem->rename($this->root . '/lib/example.php', $this->root . '/lib/renamed.php');
        $this->filesystem->dumpFile($this->root . '/lib/renamed.php', "<?php\n\$value = 3;\nreturn \$value + 1;\n");
        $this->commit();
        $this->cobertura($this->root . '/php.xml', $this->root . '/lib', 'renamed.php', [2 => 1, 3 => 0]);
        $result = $this->report($base);
        $this->assertSame(2, $result['total_num_lines']);
        $this->assertSame(1, $result['total_num_violations']);
        $this->assertArrayHasKey('lib/renamed.php', $result['src_stats']);
    }

    public function testUtf8AndSpacesInMeasuredPathsAreRetained(): void
    {
        $base = trim($this->git(['rev-parse', 'HEAD']));
        $filename = 'café with spaces.php';
        $this->filesystem->dumpFile($this->root . '/lib/' . $filename, "<?php\nreturn 2;\n");
        $this->commit();
        $this->cobertura($this->root . '/php.xml', $this->root . '/lib', $filename, [2 => 1]);
        $result = $this->report($base);
        $this->assertSame(1, $result['total_num_lines']);
        $this->assertArrayHasKey('lib/' . $filename, $result['src_stats']);
    }

    #[DataProvider('invalidReports')]
    public function testInvalidReportsAreRejected(string $xml): void
    {
        $this->filesystem->dumpFile($this->root . '/invalid.xml', $xml);
        $this->expectException(RuntimeException::class);
        CoberturaReport::normalize($this->root . '/invalid.xml', $this->root . '/normalized.xml', $this->root, ['lib/example.php']);
    }

    public static function invalidReports(): array
    {
        return [
            'malformed' => ['<coverage>'],
            'empty' => ['<coverage/>'],
            'unmapped' => ['<coverage><class filename="missing.php"/></coverage>'],
            'traversal' => ['<coverage><class filename="../lib/example.php"/></coverage>'],
            'internal entity' => ['<!DOCTYPE coverage [<!ENTITY secret SYSTEM "file:///etc/passwd">]><coverage/>'],
            'unknown external DTD' => ['<!DOCTYPE coverage SYSTEM "https://example.invalid/coverage.dtd"><coverage/>'],
        ];
    }

    public function testAmbiguousReportPathsAreRejected(): void
    {
        $this->phpCoverage($this->root . '/lib');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ambiguous');
        $xml = '<coverage><sources><source>/other/lib/a</source></sources><class filename="example.php"/></coverage>';
        $this->filesystem->dumpFile($this->root . '/ambiguous.xml', $xml);
        CoberturaReport::normalize($this->root . '/ambiguous.xml', $this->root . '/normalized.xml', $this->root, ['lib/a/example.php', 'a/example.php']);
    }

    public function testMissingArtifactIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing');
        CoberturaReport::normalize($this->root . '/missing.xml', $this->root . '/normalized.xml', $this->root, []);
    }

    public function testInvalidBaseIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('full diff-base');
        (new ChangedCodeCoverage($this->root))->generate(str_repeat('a', 41), [$this->root . '/php.xml']);
    }

    public function testEngineFailureDoesNotExposeSubprocessOutput(): void
    {
        $engine = $this->root . '/failing-engine';
        $this->filesystem->dumpFile($engine, "#!/usr/bin/env php\n<?php fwrite(STDERR, 'secret-token'); exit(7);\n");
        chmod($engine, 0700);
        try {
            (new ChangedCodeCoverage($this->root, $engine))->generate($this->base, [$this->root . '/php.xml', $this->root . '/js.xml']);
            $this->fail('Expected engine failure');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('exit 7', $error->getMessage());
            $this->assertStringNotContainsString('secret-token', $error->getMessage());
        }
    }

    public function testVisualReportShowsMeasuredAndUnmeasuredFiles(): void
    {
        $html = CoverageReport::render([
            'total_num_lines' => 3,
            'total_num_violations' => 1,
            'total_percent_covered' => 66.67,
            'src_stats' => [
                'lib/example.php' => ['percent_covered' => 50.0, 'violation_lines' => [3]],
                'src/js/example.js' => ['percent_covered' => 100.0, 'violation_lines' => []],
            ],
            'unmeasured_files' => ['templates/<example>.tpl'],
            'diff_base' => $this->base,
            'diff_head' => trim($this->git(['rev-parse', 'HEAD'])),
        ]);
        $this->assertStringContainsString('66.67%', $html);
        $this->assertStringContainsString('Coverage gaps', $html);
        $this->assertStringContainsString('Fully covered', $html);
        $this->assertStringContainsString('Unmeasured', $html);
        $this->assertStringContainsString('templates/&lt;example&gt;.tpl', $html);
        $this->assertStringNotContainsString('templates/<example>.tpl', $html);
    }

    public function testVisualReportDoesNotClaimCoverageWithoutMeasuredLines(): void
    {
        $html = CoverageReport::render([
            'total_num_lines' => 0,
            'total_num_violations' => 0,
            'total_percent_covered' => null,
            'src_stats' => [],
            'unmeasured_files' => ['templates/example.tpl'],
            'diff_base' => $this->base,
            'diff_head' => trim($this->git(['rev-parse', 'HEAD'])),
        ]);
        $this->assertStringContainsString('No measured executable lines', $html);
        $this->assertStringNotContainsString('100.00%', $html);
    }

    public function testVisualReportShowsGapsDespiteRoundedFileCoverage(): void
    {
        $html = CoverageReport::render([
            'total_num_lines' => 1,
            'total_num_violations' => 1,
            'total_percent_covered' => 0.0,
            'src_stats' => [
                'lib/example.php' => ['percent_covered' => 100.0, 'violation_lines' => [3]],
            ],
            'unmeasured_files' => [],
            'diff_base' => $this->base,
            'diff_head' => trim($this->git(['rev-parse', 'HEAD'])),
        ]);
        $this->assertStringContainsString('Coverage gaps', $html);
    }

    public function testReportCommandWritesTheVisualArtifact(): void
    {
        $engine = getenv('DIFF_COVER_BIN');
        if (! $engine) {
            $this->markTestSkipped('Set DIFF_COVER_BIN to run reporting integration tests.');
        }
        $output = $this->root . '/report/index.html';
        $this->filesystem->mkdir($this->root . '/temp/ci/js_coverage');
        $this->filesystem->copy($this->root . '/php.xml', $this->root . '/temp/ci/phpunit-coverage.xml');
        $this->filesystem->copy($this->root . '/js.xml', $this->root . '/temp/ci/js_coverage/cobertura-coverage.xml');
        $script = realpath(__DIR__ . '/../../../src/ci/coverage/report.php');
        $process = new Process([PHP_BINARY, $script, '--base', $this->base, '--output', $output], $this->root, ['DIFF_COVER_BIN' => $engine]);
        $process->mustRun();
        $this->assertFileExists($output);
        $this->assertStringContainsString('66.67%', file_get_contents($output));
    }

    public function testReportCommandWritesAnUnavailableArtifactBeforeSetup(): void
    {
        $output = $this->root . '/report/index.html';
        $script = realpath(__DIR__ . '/../../../src/ci/coverage/report.php');
        $process = new Process([PHP_BINARY, $script, '--unavailable', '--output', $output], $this->root);
        $process->mustRun();
        $this->assertFileExists($output);
        $this->assertStringContainsString('Coverage unavailable', file_get_contents($output));
    }

    public function testMissingArtifactProducesAnUnavailableReport(): void
    {
        $output = $this->root . '/report/index.html';
        $script = realpath(__DIR__ . '/../../../src/ci/coverage/report.php');
        $process = new Process([PHP_BINARY, $script, '--base', $this->base, '--output', $output], $this->root);
        $this->assertNotSame(0, $process->run());
        $this->assertFileExists($output);
        $this->assertStringContainsString('Coverage unavailable', file_get_contents($output));
    }

    public function testLargeEngineErrorOutputDoesNotDeadlock(): void
    {
        $engine = $this->root . '/noisy-engine';
        $this->filesystem->dumpFile($engine, <<<'PHP'
#!/usr/bin/env php
<?php
fwrite(STDERR, str_repeat('x', 300000));
foreach ($argv as $argument) {
    if (str_starts_with($argument, 'json:')) {
        file_put_contents(substr($argument, 5), '{"total_num_lines":0,"total_num_violations":0,"total_percent_covered":0,"src_stats":{}}');
    }
}
PHP);
        chmod($engine, 0700);
        $result = (new ChangedCodeCoverage($this->root, $engine))->generate($this->base, [$this->root . '/php.xml', $this->root . '/js.xml']);
        $this->assertSame(0, $result['total_num_lines']);
    }
}
