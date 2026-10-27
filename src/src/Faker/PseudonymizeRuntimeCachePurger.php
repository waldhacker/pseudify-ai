<?php

declare(strict_types=1);

/*
 * This file is part of the pseudify database pseudonymizer project
 * - (c) 2025 waldhacker UG (haftungsbeschränkt)
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Waldhacker\Pseudify\Core\Faker;

use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @internal
 */
class PseudonymizeRuntimeCachePurger
{
    public function __construct(
        private readonly TagAwareCacheInterface $cache,
        private readonly string $fakeDataDirectory,
        private readonly UniqueValueClaimStore $claimStore,
    ) {
    }

    public function ensurePrivateDirectories(): void
    {
        foreach ([$this->fakeDataDirectory, $this->claimStore->getClaimDirectory()] as $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Unable to create cache directory "%s".', $directory));
            }
            @chmod($directory, 0700);
        }
    }

    public function purge(): void
    {
        $this->cache->invalidateTags(['pseudonymize_fakedata']);
        $this->removeDirectory($this->fakeDataDirectory);
        $this->claimStore->clear();
    }

    public function getFakeDataDirectory(): string
    {
        return $this->fakeDataDirectory;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $fileInfo) {
            $path = $fileInfo->getPathname();
            $fileInfo->isDir() ? @rmdir($path) : @unlink($path);
        }
        @rmdir($directory);
    }
}
