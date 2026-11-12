<?php

declare(strict_types=1);

namespace Tiki\Tests\Lib;

use ErrorException;
use GitLib;
use TikiTestCase;

require_once __DIR__ . '/../../gitlib.php';

/**
 * @group unit
 */
class GitLibHeadTest extends TikiTestCase
{
    private const HASH = '0123456789abcdef0123456789abcdef01234567';
    private string $gitDir;

    protected function setUp(): void
    {
        $this->gitDir = sys_get_temp_dir() . '/tiki-gitlib-' . uniqid();
        mkdir($this->gitDir . '/refs/heads', 0777, true);
        set_error_handler(function (int $errno, string $errstr): bool {
            throw new ErrorException($errstr, 0, $errno);
        });
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        @unlink($this->gitDir . '/refs/heads/main');
        @unlink($this->gitDir . '/HEAD');
        rmdir($this->gitDir . '/refs/heads');
        rmdir($this->gitDir . '/refs');
        rmdir($this->gitDir);
    }

    public function testDetachedHeadHasNoBranchAndItsHashIsTheCommit(): void
    {
        file_put_contents($this->gitDir . '/HEAD', self::HASH . "\n");
        $git = new GitLib($this->gitDir);

        $this->assertNull($git->get_branch());
        $this->assertSame(self::HASH, $git->get_commit());
    }

    public function testBranchHeadResolvesThroughItsRef(): void
    {
        file_put_contents($this->gitDir . '/HEAD', "ref: refs/heads/main\n");
        file_put_contents($this->gitDir . '/refs/heads/main', self::HASH . "\n");
        $git = new GitLib($this->gitDir);

        $this->assertSame('refs/heads/main', $git->get_branch());
        $this->assertSame(self::HASH, $git->get_commit());
    }
}
