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
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\String\Exception\InvalidArgumentException;
use Symfony\Component\String\UnicodeString;
use Waldhacker\Pseudify\Core\Processor\Processing\Helper;

/**
 * @internal
 */
class ConditionExpressionProvider implements ExpressionFunctionProviderInterface
{
    private readonly PropertyAccessorInterface $propertyAccessor;

    public function __construct(private readonly ConditionExpressionProviderCollection $conditionExpressionProviderCollection)
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();
    }

    /**
     * @return array<array-key, ExpressionFunction>
     */
    #[\Override]
    public function getFunctions(): array
    {
        $coreFunctions = $this->getCoreFunctions();
        $userFunctions = $this->conditionExpressionProviderCollection->getFunctions();
        $coreFunctionNames = array_keys($coreFunctions);

        foreach (array_keys($userFunctions) as $userFunctionName) {
            if (in_array($userFunctionName, $coreFunctionNames)) {
                throw new AmbiguousConditionExpressionException(sprintf('The expression function name "%s" is reserved.', $userFunctionName), 1_706_625_936);
            }
        }

        return array_merge(
            array_map(fn (array $expressionFunction): ExpressionFunction => $expressionFunction[ConditionExpressionProviderInterface::EXPRESSION_FUNCTION], $coreFunctions),
            array_map(fn (array $expressionFunction): ExpressionFunction => $expressionFunction[ConditionExpressionProviderInterface::EXPRESSION_FUNCTION], $userFunctions)
        );
    }

    /**
     * @return array<array-key, array{array-key, string}>
     */
    public function getFunctionInformation(): array
    {
        $coreFunctions = $this->getCoreFunctions();
        $userFunctions = $this->conditionExpressionProviderCollection->getFunctions();

        $functions = array_merge(
            array_map(
                fn (array $expressionFunction): array => [
                    sprintf('%s() (core)', $expressionFunction[ConditionExpressionProviderInterface::EXPRESSION_FUNCTION]->getName()),
                    $expressionFunction[ConditionExpressionProviderInterface::EXPRESSION_DESCRIPTION],
                ],
                $coreFunctions
            ),
            array_map(
                fn (array $expressionFunction): array => [
                    sprintf('%s() (user)', $expressionFunction[ConditionExpressionProviderInterface::EXPRESSION_FUNCTION]->getName()),
                    $expressionFunction[ConditionExpressionProviderInterface::EXPRESSION_DESCRIPTION],
                ],
                $userFunctions
            ),
        );

        ksort($functions);

        return $functions;
    }

    /**
     * @return array<string, array{description: string, function: ExpressionFunction}>
     */
    private function getCoreFunctions(): array
    {
        return array_merge(
            $this->alias(
                evaluator: fn (array $arguments, string $columnName): string|int|float|null => ($arguments['context']->datebaseRow[$columnName] ?? null) === null ? null : $arguments['context']->datebaseRow[$columnName],
                description: 'Retrieves the value from a column from the currently processed database row. Example: `column(\'id\')`.',
                names: ['column']
            ),
            $this->alias(
                evaluator: function (array $arguments, ?string $path = null): mixed {
                    $data = $arguments['context']->decodedData;

                    if (is_scalar($data) || empty($path)) {
                        return $data;
                    }

                    if (is_array($data) || is_object($data)) {
                        $data = $this->propertyAccessor->getValue($data, Helper::buildPropertyAccessorPath($data, $path));
                    }

                    return $data;
                },
                description: 'Retrieves the value from the currently decoded database column. You can optionally pass a data path to grab the value from structured data like json. Example: `value(\'some.path\')`.',
                names: ['value']
            ),
            $this->alias(
                evaluator: fn (array $arguments, mixed $value): bool => is_string($value) && false !== filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4),
                description: 'Check if the value looks like a IPv4 address. The value must be passed as the 1. argument. Use the function `value()` to use the current decoded column data. Example: `isIPv4(value())`',
                names: ['isIPv4']
            ),
            $this->alias(
                evaluator: fn (array $arguments, mixed $value): bool => is_string($value) && false !== filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6),
                description: 'Check if the value looks like a IPv6 address. The value must be passed as the 1. argument. Use the function `value()` to use the current decoded column data. Example: `isIPv6(value())`',
                names: ['isIPv6']
            ),
            $this->alias(
                evaluator: fn (array $arguments, mixed $value): ?UnicodeString => is_string($value) ? $this->normalizeString($value) : null,
                description: 'Object-oriented access to the string using the symfony string component. The value to "stringify" must be passed as the 1. argument. Example: `string(\'foo\')`',
                names: ['string']
            ),
            $this->alias(
                evaluator: fn (array $arguments): bool => true,
                description: 'Always true. Example: `true()`',
                names: ['true', 'always', 'nocondition']
            ),
        );
    }

    /**
     * @param string[] $names
     *
     * @return array<string, array{description: string, function: ExpressionFunction}>
     */
    private function alias(callable $evaluator, string $description, array $names): array
    {
        $aliasFunctions = [];
        foreach ($names as $aliasName) {
            $aliasFunctions[$aliasName] = [
                ConditionExpressionProviderInterface::EXPRESSION_DESCRIPTION => $description,
                ConditionExpressionProviderInterface::EXPRESSION_FUNCTION => new ExpressionFunction(
                    name: $aliasName,
                    compiler: function () {},
                    evaluator: $evaluator
                ),
            ];
        }

        return $aliasFunctions;
    }

    private function normalizeString(string $input): UnicodeString
    {
        try {
            $result = new UnicodeString($input);
        } catch (InvalidArgumentException) {
            $normalized = preg_replace('/[^[:print:]]/', '', $input);
            $result = new UnicodeString($normalized ?? $input);
        }

        return $result;
    }
}
