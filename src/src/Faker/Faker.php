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

    public function __construct(
        private readonly Generator $faker,
        private readonly TagAwareCacheInterface $cache,
        private mixed $source = null,
        private ?string $scope = null,
    ) {
        /* @var mixed $source */
        $this->source = $source ?? bin2hex(random_bytes(100));
        $this->source = hash('sha256', bin2hex(serialize($this->source)));
        $this->scope = $scope ?? self::DEFAULT_SCOPE;
    }

    public function withSource(mixed $source): Faker
    {
        return new self(source: $source, scope: $this->scope, faker: $this->faker, cache: $this->cache);
    }

    public function withScope(string $scope): Faker
    {
        return new self(source: $this->source, scope: $scope, faker: $this->faker, cache: $this->cache);
    }

    /**
     * @param string            $fakerFormatter
     * @param array<int, mixed> $fakerArguments
     *
     * @internal
     */
    public function __call($fakerFormatter, array $fakerArguments = []): mixed
    {
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
}
