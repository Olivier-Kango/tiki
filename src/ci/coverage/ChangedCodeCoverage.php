<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiDevTools\Coverage;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;
use Tiki\Process\Process;

require_once __DIR__ . '/CoberturaReport.php';

/** Compare a committed diff with existing reports; diff-cover owns the calculations. */
final class ChangedCodeCoverage
{
    public function __construct(private string $root, private string $engine = 'diff-cover')
    {
        $root = realpath($root);
        if ($root === false) {
            throw new RuntimeException('Repository directory is missing');
        }
        $this->root = $root;
    }

    private function execute(array $command, string $phase): string
    {
        try {
            $process = new Process($command, $this->root, ['GIT_TERMINAL_PROMPT' => '0'], null, 120);
            $status = $process->run();
        } catch (Throwable $error) {
            // Process exceptions and stderr can contain authenticated remote URLs.
            throw new RuntimeException($phase . ': command could not run');
        }
        if ($status !== 0) {
            throw new RuntimeException($phase . ': command failed (exit ' . $status . ')');
        }
        return $process->getOutput();
    }

    public function generate(string $base, array $reports): array
    {
        if (! preg_match('/^(?:[a-f0-9]{40}|[a-f0-9]{64})$/D', $base) || trim($base, '0') === '') {
            throw new RuntimeException('A full diff-base commit SHA is required');
        }
        if ($reports === []) {
            throw new RuntimeException('Coverage artifacts are required');
        }
        if (trim($this->execute(['git', 'rev-parse', '--show-toplevel'], 'Repository validation')) !== $this->root) {
            throw new RuntimeException('Run from the repository root');
        }
        $this->execute(['git', 'cat-file', '-e', $base . '^{commit}'], 'Diff-base validation');
        $head = trim($this->execute(['git', 'rev-parse', '--verify', 'HEAD^{commit}'], 'Comparison head resolution'));
        $coverageFiles = array_filter(explode("\0", $this->execute(['git', 'ls-tree', '-r', '--name-only', '-z', $head], 'Coverage file discovery')), 'strlen');
        $changes = array_filter(explode("\0", $this->execute(['git', 'diff', '--name-only', '-z', '--diff-filter=ACMRT', $base, $head, '--'], 'Changed file discovery')), 'strlen');
        foreach ($changes as $path) {
            if (preg_match('/[[:cntrl:]]/', $path) || str_contains($path, '"') || str_contains($path, '\\') || str_contains($path, ' b/')) {
                throw new RuntimeException('Changed filename is unsupported by the coverage diff parser');
            }
        }
        $work = sys_get_temp_dir() . '/tiki-coverage-' . bin2hex(random_bytes(8));
        $filesystem = new Filesystem();
        $filesystem->mkdir($work, 0700);
        try {
            $normalized = [];
            foreach (array_values($reports) as $index => $source) {
                $path = $work . '/' . $index . '.xml';
                CoberturaReport::normalize($source, $path, $this->root, $coverageFiles);
                $normalized[] = $path;
            }
            $filesystem->dumpFile($work . '/diff.patch', $this->execute([
                'git', '-c', 'core.quotePath=false', 'diff', '--no-ext-diff', '--no-textconv',
                '--src-prefix=a/', '--dst-prefix=b/', '--find-renames', '--unified=0', $base, $head, '--',
            ], 'Committed diff generation'));
            $this->execute(array_merge([$this->engine], $normalized, [
                '--diff-file', $work . '/diff.patch', '--fail-under', '0', '--total-percent-float',
                '--format', 'json:' . $work . '/report.json',
            ]), 'Coverage calculation');
            $result = json_decode(file_get_contents($work . '/report.json'), true, 512, JSON_THROW_ON_ERROR);
            if (
                ! is_int($result['total_num_lines'] ?? null) || ! is_int($result['total_num_violations'] ?? null)
                || $result['total_num_violations'] < 0 || $result['total_num_lines'] < $result['total_num_violations']
                || ! is_array($result['src_stats'] ?? null)
                || (! is_int($result['total_percent_covered'] ?? null) && ! is_float($result['total_percent_covered'] ?? null))
                || ! is_finite($result['total_percent_covered']) || $result['total_percent_covered'] < 0 || $result['total_percent_covered'] > 100
            ) {
                throw new RuntimeException('Invalid diff-cover results');
            }
            // Zero measured lines are not evidence of full coverage.
            if ($result['total_num_lines'] === 0) {
                $result['total_percent_covered'] = null;
            }
            $result['diff_head'] = $head;
            $result['diff_base'] = $base;
            $result['unmeasured_files'] = array_values(array_diff($changes, array_keys($result['src_stats'])));
            sort($result['unmeasured_files']);
            return $result;
        } finally {
            $filesystem->remove($work);
        }
    }
}
