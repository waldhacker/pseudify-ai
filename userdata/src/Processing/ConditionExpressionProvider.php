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

namespace Waldhacker\Pseudify\Processing;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionProviderInterface;

/**
 * Example to show how custom condition functions can be implemented.
 */
class ConditionExpressionProvider implements ConditionExpressionProviderInterface
{

    /**
     * array<int, array{description: string, function: ExpressionFunction}>
     */
    public function getFunctions(): array
    {
        return [
            [
                ConditionExpressionProviderInterface::EXPRESSION_DESCRIPTION => 'Check if Bob Ross is present. The value must be passed as the 1. argument. Use the function `value()` to use the current decoded column data. Example: `isBobRoss(value())`',
                ConditionExpressionProviderInterface::EXPRESSION_FUNCTION => new ExpressionFunction(
                    name: 'isBobRoss',
                    compiler: function () {},
                    evaluator: fn (array $arguments, mixed $value): bool => is_string($value) && $value === 'Bob Ross'
                ),
            ],
        ];
    }
}
