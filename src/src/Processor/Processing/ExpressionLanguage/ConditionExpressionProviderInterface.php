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
 * @api
 */
interface ConditionExpressionProviderInterface
{
    final public const string EXPRESSION_FUNCTION = 'function';
    final public const string EXPRESSION_DESCRIPTION = 'description';

    /**
     * @return array<int, array{description: string, function: ExpressionFunction}>
     */
    public function getFunctions(): array;
}
