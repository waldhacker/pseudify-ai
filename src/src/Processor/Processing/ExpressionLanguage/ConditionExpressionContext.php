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

namespace Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage;

/**
 * @internal
 */
class ConditionExpressionContext
{
    public function __construct(
        public readonly mixed $originalData,
        /** @var array<string, mixed> */
        public readonly array $datebaseRow,
        public readonly mixed $decodedData,
    ) {
    }
}
