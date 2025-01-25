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

namespace Waldhacker\Pseudify\Core\Processor\Processing\Pseudonymize;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Waldhacker\Pseudify\Core\Processor\Processing\AmbiguousDataProcessingException;
use Waldhacker\Pseudify\Core\Processor\Processing\DataProcessingInterface;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionContextFactory;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionProvider;
use Waldhacker\Pseudify\Core\Processor\Processing\GenericDataProcessingInterface;

/**
 * @internal
 */
class DataManipulator
{
    public function __construct(private readonly ConditionExpressionProvider $conditionExpressionProvider)
    {
    }

    /**
     * @param array<int, DataProcessingInterface> $processings
     */
    public function process(DataManipulatorContext $context, array $processings): mixed
    {
        $data = $context->getDecodedData();
        $context->setProcessedData($data);
        $expressionLanguage = new ExpressionLanguage(providers: [$this->conditionExpressionProvider]);

        foreach ($this->getValidProcessings($processings) as $processing) {
            if (
                $processing instanceof GenericDataProcessingInterface
                && $processing->getCondition()
                && !$expressionLanguage->evaluate($processing->getCondition(), ['context' => ConditionExpressionContextFactory::fromDataManipulatorContext($context)])
            ) {
                continue;
            }

            $processor = $processing->getProcessor();
            $context = $context->withProcessedData($context->getProcessedData());
            $processor($context);
        }

        return $context->getProcessedData();
    }

    /**
     * @param array<array-key, DataProcessingInterface> $allProcessings
     *
     * @return array<int, DataProcessingInterface>
     */
    private function getValidProcessings(array $allProcessings): array
    {
        $validProcessings = [];
        $identifiers = [];
        foreach ($allProcessings as $processing) {
            if (!$processing instanceof DataProcessingInterface) {
                continue;
            }

            if (in_array($processing->getIdentifier(), $identifiers, true)) {
                throw new AmbiguousDataProcessingException(sprintf('the dataProcessing identifier "%s" must be unique.', $processing->getIdentifier()), 1_620_916_028);
            }
            $identifiers[] = $processing->getIdentifier();
            $validProcessings[] = $processing;
        }

        return $validProcessings;
    }
}
