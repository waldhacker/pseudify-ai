<?php

declare(strict_types=1);

namespace Waldhacker\Pseudify\Core\Tests\Unit\Faker;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Waldhacker\Pseudify\Core\Faker\PseudonymizeRuntimeCachePurger;
use Waldhacker\Pseudify\Core\Faker\UniqueValueClaimStore;

class PseudonymizeRuntimeCachePurgerTest extends TestCase
{
    private string $fakeDataDirectory;
    private string $claimDirectory;

    protected function setUp(): void
    {
        $suffix = bin2hex(random_bytes(8));
        $this->fakeDataDirectory = sys_get_temp_dir().'/pseudify_fakedata_'.$suffix;
        $this->claimDirectory = sys_get_temp_dir().'/pseudify_unique_'.$suffix;
    }

    protected function tearDown(): void
    {
        foreach ([$this->fakeDataDirectory, $this->claimDirectory] as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $fileInfo) {
                $path = $fileInfo->getPathname();
                $fileInfo->isDir() ? rmdir($path) : unlink($path);
            }
            rmdir($directory);
        }
    }

    public function testEnsurePrivateDirectoriesCreatesRootsWithMode0700(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());
        $claimStore = new UniqueValueClaimStore($this->claimDirectory);
        $purger = new PseudonymizeRuntimeCachePurger($cache, $this->fakeDataDirectory, $claimStore);

        $purger->ensurePrivateDirectories();

        $this->assertDirectoryExists($this->fakeDataDirectory);
        $this->assertDirectoryExists($this->claimDirectory);
        $this->assertSame('0700', substr(sprintf('%o', fileperms($this->fakeDataDirectory)), -4));
        $this->assertSame('0700', substr(sprintf('%o', fileperms($this->claimDirectory)), -4));
    }

    public function testPurgeEmptiesRootsAndSecondPurgeIsNoOp(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());
        $claimStore = new UniqueValueClaimStore($this->claimDirectory);
        $purger = new PseudonymizeRuntimeCachePurger($cache, $this->fakeDataDirectory, $claimStore);

        $purger->ensurePrivateDirectories();
        file_put_contents($this->fakeDataDirectory.'/payload.bin', 'x');
        $this->assertTrue($claimStore->claim(hash('sha256', 'domain'), 'held@example.com'));

        $cache->get('pseudonymize_fakedata_probe', static function (ItemInterface $item): string {
            $item->tag(['pseudonymize_fakedata']);

            return 'cached';
        });
        $this->assertSame('cached', $cache->get('pseudonymize_fakedata_probe', static fn (): string => 'miss'));

        $purger->purge();

        $this->assertTrue(
            !is_dir($this->fakeDataDirectory)
            || [] === array_diff(scandir($this->fakeDataDirectory) ?: [], ['.', '..'])
        );
        $this->assertTrue(
            !is_dir($this->claimDirectory)
            || [] === array_diff(scandir($this->claimDirectory) ?: [], ['.', '..'])
        );

        $recomputed = false;
        $value = $cache->get('pseudonymize_fakedata_probe', static function (ItemInterface $item) use (&$recomputed): string {
            $item->tag(['pseudonymize_fakedata']);
            $recomputed = true;

            return 'fresh';
        });
        $this->assertTrue($recomputed, 'tagged cache item must miss after purge/invalidateTags');
        $this->assertSame('fresh', $value);

        $purger->purge();
    }
}
