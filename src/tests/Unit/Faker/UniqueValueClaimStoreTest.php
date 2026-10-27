<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Unit\Faker;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Waldhacker\Pseudify\Core\Faker\UniqueValueClaimStore;

class UniqueValueClaimStoreTest extends TestCase
{
    private string $claimDirectory;

    protected function setUp(): void
    {
        $this->claimDirectory = sys_get_temp_dir().'/pseudify_unique_claim_'.bin2hex(random_bytes(8));
        mkdir($this->claimDirectory, 0700, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->claimDirectory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->claimDirectory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $fileInfo) {
                $path = $fileInfo->getPathname();
                $fileInfo->isDir() ? rmdir($path) : unlink($path);
            }
            rmdir($this->claimDirectory);
        }
    }

    public function testClaimCreatesPathUsingDomainHashAndSerializedCandidateHash(): void
    {
        $domainHash = hash('sha256', json_encode(['*', 'email', []], JSON_THROW_ON_ERROR));
        $candidate = 'dup@example.com';
        $expectedPath = $this->claimDirectory.'/'.$domainHash.'/'.hash('sha256', serialize($candidate));

        $store = new UniqueValueClaimStore($this->claimDirectory);

        $this->assertTrue($store->claim($domainHash, $candidate));
        $this->assertFileExists($expectedPath);
        $this->assertFalse($store->claim($domainHash, $candidate));
    }

    public function testClearRemovesClaimDirectoryContentsIdempotently(): void
    {
        $domainHash = hash('sha256', 'domain');
        $store = new UniqueValueClaimStore($this->claimDirectory);
        $this->assertTrue($store->claim($domainHash, 'a@example.com'));

        $store->clear();
        $this->assertTrue(!is_dir($this->claimDirectory) || [] === array_diff(scandir($this->claimDirectory) ?: [], ['.', '..']));

        $store->clear();
    }

    public function testTwoParallelProcessesAcquireExactlyOneClaim(): void
    {
        $domainHash = hash('sha256', json_encode(['*', 'email', []], JSON_THROW_ON_ERROR));
        $candidate = 'race@example.com';
        $script = __DIR__.'/claim_race_worker.php';

        $process1 = new Process([PHP_BINARY, $script, $this->claimDirectory, $domainHash, $candidate]);
        $process2 = new Process([PHP_BINARY, $script, $this->claimDirectory, $domainHash, $candidate]);

        $process1->start();
        $process2->start();
        $process1->wait();
        $process2->wait();

        $combinedOutput = $process1->getOutput()."\n".$process2->getOutput();
        $acquiredCount = substr_count($combinedOutput, 'acquired=1');
        $rejectedCount = substr_count($combinedOutput, 'acquired=0');

        $this->assertSame(1, $acquiredCount, sprintf("expected exactly one acquisition, got:\n%s", $combinedOutput));
        $this->assertSame(1, $rejectedCount, sprintf("expected exactly one rejection, got:\n%s", $combinedOutput));

        $expectedPath = $this->claimDirectory.'/'.$domainHash.'/'.hash('sha256', serialize($candidate));
        $this->assertFileExists($expectedPath);
    }
}
