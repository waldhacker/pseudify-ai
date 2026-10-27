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

/**
 * @internal
 */
class UniqueValueClaimStore
{
    public function __construct(
        private readonly string $claimDirectory,
    ) {
    }

    public function claim(string $domainHash, mixed $candidate): bool
    {
        $domainDirectory = $this->claimDirectory.'/'.$domainHash;
        if (!is_dir($domainDirectory) && !mkdir($domainDirectory, 0700, true) && !is_dir($domainDirectory)) {
            throw new \RuntimeException(sprintf('Unable to create claim directory "%s".', $domainDirectory));
        }

        $path = $domainDirectory.'/'.hash('sha256', serialize($candidate));
        $handle = @fopen($path, 'x');
        if (false === $handle) {
            return false;
        }

        fwrite($handle, '1');
        fclose($handle);

        return true;
    }

    public function clear(): void
    {
        if (!is_dir($this->claimDirectory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->claimDirectory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $fileInfo) {
            $path = $fileInfo->getPathname();
            $fileInfo->isDir() ? @rmdir($path) : @unlink($path);
        }
        @rmdir($this->claimDirectory);
    }

    public function getClaimDirectory(): string
    {
        return $this->claimDirectory;
    }
}
