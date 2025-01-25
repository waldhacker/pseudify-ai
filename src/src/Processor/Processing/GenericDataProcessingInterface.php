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

namespace Waldhacker\Pseudify\Core\Processor\Processing;

/**
 * @api
 */
interface GenericDataProcessingInterface extends DataProcessingInterface
{
    final public const string CONTEXT_TYPE = 'type';
    final public const string CONTEXT_CONTEXT = 'context';
    final public const string CONTEXT_SCOPE = 'scope';
    final public const string CONTEXT_PATH = 'path';
    final public const string CONTEXT_MINIMUM_GRAPHEME_LENGTH = 'minimumGraphemeLength';

    /**
     * @internal
     */
    public function getCondition(): ?string;

    /**
     * @return string[]|null
     *
     * @internal
     */
    public function getConditions(): ?array;

    /**
     * @return array<string, mixed>|null
     *
     * @internal
     */
    public function getContext(): ?array;
}
