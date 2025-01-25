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

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

/**
 * @internal
 */
class ConditionExpressionProviderCollection
{
    /** @var array<string, array{description: string, function: ExpressionFunction}> */
    private array $expressionFunctions = [];

    /**
     * @param array<int, mixed> $conditionExpressionProviders
     */
    public function __construct(iterable $conditionExpressionProviders = [])
    {
        foreach ($conditionExpressionProviders as $conditionExpressionProvider) {
            if (!($conditionExpressionProvider instanceof ConditionExpressionProviderInterface)) {
                continue;
            }

            foreach ($conditionExpressionProvider->getFunctions() as $expressionFunction) {
                $function = $expressionFunction[ConditionExpressionProviderInterface::EXPRESSION_FUNCTION] ?? null;
                if (!($function instanceof ExpressionFunction)) {
                    continue;
                }
                $expressionFunction[ConditionExpressionProviderInterface::EXPRESSION_DESCRIPTION] ??= '';

                $this->expressionFunctions[$function->getName()] = $expressionFunction;
            }
        }
    }

    /**
     * @return array<string, array{description: string, function: ExpressionFunction}>
     */
    public function getFunctions(): array
    {
        return $this->expressionFunctions;
    }
}
