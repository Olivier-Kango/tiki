<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace TikiDevTools\Coverage;

use RuntimeException;

/** Render the validated changed-code coverage result as a standalone artifact. */
final class CoverageReport
{
    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function lines(array $lines): string
    {
        foreach ($lines as $line) {
            if (! is_int($line) || $line < 1) {
                throw new RuntimeException('Invalid changed-code coverage line');
            }
        }
        return $lines ? implode(', ', $lines) : '—';
    }

    public static function unavailable(string $reason): string
    {
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'; base-uri \'none\'">'
            . '<title>Test coverage of this diff</title></head><body><h1>Coverage unavailable</h1><p>'
            . self::escape($reason) . '</p><p>Do not interpret missing coverage as 100%. Check the reporting and test jobs.</p></body></html>';
    }

    public static function render(array $result): string
    {
        $measured = $result['total_num_lines'] ?? null;
        $uncovered = $result['total_num_violations'] ?? null;
        $coverage = $result['total_percent_covered'] ?? null;
        $stats = $result['src_stats'] ?? null;
        $unmeasured = $result['unmeasured_files'] ?? null;
        $base = $result['diff_base'] ?? null;
        $head = $result['diff_head'] ?? null;
        if (
            ! is_int($measured) || ! is_int($uncovered) || $measured < $uncovered || $uncovered < 0
            || ! is_array($stats) || ! is_array($unmeasured) || ! is_string($base) || ! is_string($head)
            || ($coverage !== null && (! is_int($coverage) && ! is_float($coverage)
                || ! is_finite((float) $coverage) || $coverage < 0 || $coverage > 100))
        ) {
            throw new RuntimeException('Invalid changed-code coverage result');
        }
        $covered = $measured - $uncovered;
        $score = $coverage === null ? 'No measured executable lines' : number_format($coverage, 2) . '%';
        $scoreClass = $coverage === null ? 'unmeasured' : ($uncovered === 0 ? 'covered' : 'gaps');
        ksort($stats);
        sort($unmeasured);
        $rows = '';
        foreach ($stats as $path => $stat) {
            $missing = $stat['violation_lines'] ?? null;
            $fileCoverage = $stat['percent_covered'] ?? null;
            if (
                ! is_string($path) || ! is_array($missing)
                || (! is_int($fileCoverage) && ! is_float($fileCoverage))
                || ! is_finite((float) $fileCoverage) || $fileCoverage < 0 || $fileCoverage > 100
            ) {
                throw new RuntimeException('Invalid changed-code coverage file result');
            }
            $status = $missing ? 'gaps' : 'covered';
            $label = $missing ? 'Coverage gaps' : 'Fully covered';
            $rows .= '<tr><th scope="row"><code>' . self::escape($path) . '</code></th><td><span class="status ' . $status . '">'
                . $label . '</span></td><td>' . self::escape(number_format($fileCoverage, 2)) . '%</td><td>'
                . self::escape(self::lines($missing)) . '</td></tr>';
        }
        foreach ($unmeasured as $path) {
            if (! is_string($path) || isset($stats[$path])) {
                throw new RuntimeException('Invalid unmeasured changed file');
            }
            $rows .= '<tr><th scope="row"><code>' . self::escape($path) . '</code></th><td><span class="status unmeasured">Unmeasured</span></td>'
                . '<td>—</td><td>—</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="4">No added or modified files in this comparison.</td></tr>';
        }
        $style = <<<'CSS'
body { font-family: system-ui, sans-serif; max-width: 1100px; margin: 32px auto; padding: 0 20px; color: #172338; }
h1 { margin-bottom: .2rem; }
.score { font-size: 2.4rem; font-weight: 700; margin: .5rem 0; }
.summary { display: flex; flex-wrap: wrap; gap: 12px; margin: 20px 0; }
.metric { border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px; min-width: 120px; }
table { border-collapse: collapse; width: 100%; margin: 24px 0; }
th, td { border-bottom: 1px solid #cbd5e1; padding: 10px; text-align: left; vertical-align: top; }
code { overflow-wrap: anywhere; }
.status { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: .85rem; font-weight: 600; }
.covered { color: #14532d; background: #dcfce7; }
.gaps { color: #7f1d1d; background: #fee2e2; }
.unmeasured { color: #374151; background: #e5e7eb; }
.scope { color: #475569; }
CSS;
        $unmeasuredCount = count($unmeasured);
        $base = self::escape($base);
        $head = self::escape($head);
        return <<<HTML
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'">
<title>Test coverage of this diff</title><style>{$style}</style></head><body>
<h1>Test coverage of this diff</h1><p class="scope">Coverage of added and modified executable lines in the merge-request comparison.</p>
<p class="score {$scoreClass}">{$score}</p><div class="summary"><div class="metric"><strong>Measured</strong><br>{$measured} lines</div>
<div class="metric"><strong>Covered</strong><br>{$covered} lines</div><div class="metric"><strong>Uncovered</strong><br>{$uncovered} lines</div>
<div class="metric"><strong>Unmeasured files</strong><br>{$unmeasuredCount}</div></div>
<p>Start with files marked <span class="status gaps">Coverage gaps</span>. Unmeasured files are excluded from the percentage.</p>
<table><thead><tr><th>Source file</th><th>Status</th><th>Diff coverage</th><th>Missing lines</th></tr></thead><tbody>{$rows}</tbody></table>
<p class="scope">Coverage means execution, not proof of correctness. Review failed or skipped tests separately.</p>
<p class="scope">Comparison head: <code>{$head}</code><br>Comparison base: <code>{$base}</code></p></body></html>
HTML;
    }
}
