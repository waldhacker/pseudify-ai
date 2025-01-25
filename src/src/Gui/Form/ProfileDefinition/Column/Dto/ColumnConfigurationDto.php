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

namespace Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Dto;

/**
 * @implements \ArrayAccess<int, array>
 */
class ColumnConfigurationDto implements \ArrayAccess, \Countable
{
    /**
     * @param array<int, array{identifier: string, name: string, conditions: array<int, array{condition: string}>, encoders: array<int, array{identifier: string, encoder: string, context: array<int, array<string, mixed>>}>, meanings: array<int, array{identifier: string, name: string, conditions: array<int, array{condition: string}>, property: array{path: string|null, scope: string|null, type: string|null, minimumGraphemeLength: int|null, context: array<int, array<string, mixed>>}}>}> $encodings
     */
    public function __construct(public array $encodings = [], public ?string $columnDescription = '')
    {
    }

    /**
     * @param array{identifier: string, name: string, conditions: array<int, array{condition: string}>, encoders: array<int, array{identifier: string, encoder: string, context: array<int, array<string, mixed>>}>, meanings: array<int, array{identifier: string, name: string, conditions: array<int, array{condition: string}>, property: array{path: string|null, scope: string|null, type: string|null, minimumGraphemeLength: int|null, context: array<int, array<string, mixed>>}}>} $value
     */
    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->encodings[] = $value;
        } else {
            $this->encodings[$offset] = $value;
        }
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->encodings[$offset]);
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->encodings[$offset]);
    }

    /**
     * @return array{identifier: string, name: string, conditions: array<int, array{condition: string}>, encoders: array<int, array{identifier: string, encoder: string, context: array<int, array<string, mixed>>}>, meanings: array<int, array{identifier: string, name: string, conditions: array<int, array{condition: string}>, property: array{path: string|null, scope: string|null, type: string|null, minimumGraphemeLength: int|null, context: array<int, array<string, mixed>>}}>}|null
     */
    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->encodings[$offset] ?? null;
    }

    #[\Override]
    public function count(): int
    {
        return count($this->encodings);
    }
}
