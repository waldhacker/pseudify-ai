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

use Waldhacker\Pseudify\Core\Processor\Processing\Analyze\SourceDataCollectorContext;
use Waldhacker\Pseudify\Core\Processor\Processing\Analyze\TargetDataDecoderContext;
use Waldhacker\Pseudify\Core\Processor\Processing\Pseudonymize\DataManipulatorContext;

/**
 * @internal
 */
class ConditionExpressionContextFactory
{
    /**
     * @param array<string, mixed> $datebaseRow
     */
    public static function fromColumnProcessorData(mixed $originalData, array $datebaseRow, mixed $decodedData): ConditionExpressionContext
    {
        return new ConditionExpressionContext($originalData, $datebaseRow, $decodedData);
    }

    /**
     * @param array<string, mixed> $datebaseRow
     */
    public static function fromProcessorData(mixed $originalData, array $datebaseRow): ConditionExpressionContext
    {
        return new ConditionExpressionContext($originalData, $datebaseRow, $originalData);
    }

    public static function fromDataManipulatorContext(DataManipulatorContext $context): ConditionExpressionContext
    {
        return new ConditionExpressionContext($context->getRawData(), $context->getDatebaseRow(), $context->getDecodedData());
    }

    public static function fromSourceDataCollectorContext(SourceDataCollectorContext $context): ConditionExpressionContext
    {
        return new ConditionExpressionContext($context->getRawData(), $context->getDatebaseRow(), $context->getDecodedData());
    }

    public static function fromTargetDataDecoderContext(TargetDataDecoderContext $context): ConditionExpressionContext
    {
        return new ConditionExpressionContext($context->getRawData(), $context->getDatebaseRow(), $context->getDecodedData());
    }

    public static function empty(): ConditionExpressionContext
    {
        return new ConditionExpressionContext(null, [], null);
    }
}
