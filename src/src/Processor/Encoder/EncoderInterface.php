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

/**
 * @api
 */
interface EncoderInterface
{
    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    public function decode(mixed $data, array $context = []): mixed;

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    public function encode(mixed $data, array $context = []): mixed;

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    public function canDecode(mixed $data, array $context = []): bool;

    /**
     * @api
     */
    public function decodesToScalarDataOnly(): bool;

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    public function setContext(array $context): EncoderInterface;

    /**
     * @return array<string, mixed>
     *
     * @api
     */
    public function getContext(): array;

    /**
     * @api
     */
    public function getContextFormTypeClassName(): ?string;
}
