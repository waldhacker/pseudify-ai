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

namespace Waldhacker\Pseudify\Core\Processor\Encoder;

class ScalarEncoder extends AbstractEncoder implements EncoderInterface
{
    /** @var array<string, mixed> */
    protected array $defaultContext = [];

    /**
     * @param array<string, mixed> $context
     *
     * @return string
     *
     * @api
     */
    #[\Override]
    public function decode(mixed $data, array $context = []): mixed
    {
        return $data;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function encode(mixed $data, array $context = []): mixed
    {
        return $data;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function canDecode(mixed $data, array $context = []): bool
    {
        return true;
    }

    /**
     * @api
     */
    #[\Override]
    public function decodesToScalarDataOnly(): bool
    {
        return true;
    }
}
