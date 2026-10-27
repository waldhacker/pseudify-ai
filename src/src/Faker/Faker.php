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

use Faker\Generator;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @internal
 */
class Faker
{
    final public const string DEFAULT_SCOPE = '*';
    private const int UNIQUE_RETRY_LIMIT = 64;

    /** @var list<string> */
    private const array EMAIL_FORMATTERS = ['email', 'safeEmail', 'freeEmail', 'companyEmail'];

    private readonly string $source;
    private readonly string $scope;
    private readonly bool $unique;

    public function __construct(
        private readonly Generator $faker,
        private readonly TagAwareCacheInterface $cache,
        private readonly UniqueValueClaimStore $claimStore,
        mixed $source = null,
        ?string $scope = null,
        bool $unique = false,
        bool $sourceIsHash = false,
    ) {
        if ($sourceIsHash) {
            $this->source = \is_string($source) ? $source : hash('sha256', bin2hex(serialize($source)));
        } else {
            /* @var mixed $source */
            $source = $source ?? bin2hex(random_bytes(100));
            $this->source = hash('sha256', bin2hex(serialize($source)));
        }
        $this->scope = $scope ?? self::DEFAULT_SCOPE;
        $this->unique = $unique;
    }

    public function withSource(mixed $source): Faker
    {
        return new self(
            faker: $this->faker,
            cache: $this->cache,
            claimStore: $this->claimStore,
            source: $source,
            scope: $this->scope,
            unique: $this->unique,
        );
    }

    public function withScope(string $scope): Faker
    {
        // Legacy: pass the already-hashed source through the hashing constructor
        // (same as pre-unique Faker). fake() always calls withSource afterwards.
        return new self(
            faker: $this->faker,
            cache: $this->cache,
            claimStore: $this->claimStore,
            source: $this->source,
            scope: $scope,
            unique: $this->unique,
        );
    }

    public function withUnique(bool $unique): Faker
    {
        // Must not re-hash: called after withSource(), and cache keys must stay
        // identical to the pre-unique path when $unique is false.
        return new self(
            faker: $this->faker,
            cache: $this->cache,
            claimStore: $this->claimStore,
            source: $this->source,
            scope: $this->scope,
            unique: $unique,
            sourceIsHash: true,
        );
    }

    /**
     * @param string            $fakerFormatter
     * @param array<int, mixed> $fakerArguments
     *
     * @internal
     */
    public function __call($fakerFormatter, array $fakerArguments = []): mixed
    {
        if ($this->unique) {
            return $this->callUnique($fakerFormatter, $fakerArguments);
        }

        $cacheKey = sprintf(
            'pseudonymize_fakedata_%s',
            hash('sha256', json_encode([$fakerFormatter, $fakerArguments, $this->scope, $this->source], JSON_THROW_ON_ERROR))
        );

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($fakerFormatter, $fakerArguments): mixed {
                $item->tag(['pseudonymize_fakedata']);

                return $this->faker->$fakerFormatter(...$fakerArguments);
            }
        );
    }

    /**
     * @param array<int, mixed> $fakerArguments
     */
    private function callUnique(string $fakerFormatter, array $fakerArguments): mixed
    {
        $cacheKey = sprintf(
            'pseudonymize_fakedata_%s',
            hash('sha256', json_encode([$fakerFormatter, $fakerArguments, $this->scope, $this->source], JSON_THROW_ON_ERROR))
        );

        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($fakerFormatter, $fakerArguments): mixed {
                $item->tag(['pseudonymize_fakedata']);

                $domainHash = hash('sha256', json_encode([$this->scope, $fakerFormatter, $fakerArguments], JSON_THROW_ON_ERROR));

                $lastCandidate = null;
                for ($attempt = 0; $attempt < self::UNIQUE_RETRY_LIMIT; ++$attempt) {
                    $lastCandidate = $this->faker->$fakerFormatter(...$fakerArguments);
                    if ($this->claimStore->claim($domainHash, $lastCandidate)) {
                        return $lastCandidate;
                    }
                }

                return $this->allocateFallback($domainHash, $fakerFormatter, $lastCandidate);
            }
        );
    }

    private function allocateFallback(string $domainHash, string $fakerFormatter, mixed $lastCandidate): mixed
    {
        $hex16 = substr($this->source, 0, 16);
        $hex12 = substr($this->source, 0, 12);

        if (in_array($fakerFormatter, self::EMAIL_FORMATTERS, true)) {
            $candidate = 'u'.$hex16.'@example.com';
        } elseif (\is_string($lastCandidate)) {
            $candidate = $lastCandidate.'_u'.$hex12;
        } else {
            $candidate = 'u'.$hex16;
        }

        $suffix = 0;
        while (!$this->claimStore->claim($domainHash, $candidate)) {
            if (in_array($fakerFormatter, self::EMAIL_FORMATTERS, true)) {
                $candidate = 'u'.$hex16.'_r'.$suffix.'@example.com';
            } elseif (\is_string($lastCandidate)) {
                $candidate = $lastCandidate.'_u'.$hex12.'_r'.$suffix;
            } else {
                $candidate = 'u'.$hex16.'_r'.$suffix;
            }
            ++$suffix;
            if ($suffix > 1000) {
                throw new \RuntimeException('Unable to allocate unique fallback value.');
            }
        }

        return $candidate;
    }
}
