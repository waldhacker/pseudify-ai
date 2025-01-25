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

namespace Waldhacker\Pseudify\Core\Gui\Processing;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Waldhacker\Pseudify\Core\Faker\Faker;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Dto\ColumnConfigurationDtoFactory;
use Waldhacker\Pseudify\Core\Processor\Encoder\ChainedEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\ConditionalEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\EncoderInterface;
use Waldhacker\Pseudify\Core\Processor\Encoder\ScalarEncoder;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionContextFactory;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionProvider;
use Waldhacker\Pseudify\Core\Processor\Processing\Helper;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Column;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Encoder;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning;

/**
 * @internal
 */
class ColumnProcessor
{
    private readonly PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        private readonly ConditionExpressionProvider $conditionExpressionProvider,
        private readonly Faker $faker,
    ) {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();
    }

    /**
     * @param array<int, array<string, mixed>> $databaseRows
     *
     * @return iterable<mixed>
     */
    public function processDatabaseRows(Column $column, array $databaseRows): iterable
    {
        foreach ($databaseRows as $databaseRow) {
            yield $this->processDatabaseRow($column, $databaseRow);
        }
    }

    /**
     * @param array<string, mixed> $databaseRow
     *
     * @return array{original: array<string, mixed>, decoded: mixed, paths: array<string, mixed>, meanings: array<int, array{identifier: string, name: string, originalValue: mixed, fakedValue: mixed}>, context: array<string, mixed>}
     */
    public function processDatabaseRow(Column $column, array $databaseRow): array
    {
        if (!array_key_exists($column->getIdentifier(), $databaseRow)) {
            throw new MissingColumnException(sprintf('Table column "%s" does not exist. Available table columns are: %s', $column->getIdentifier(), implode(', ', array_keys($databaseRow))), 1730899735);
        }

        $originalData = $databaseRow[$column->getIdentifier()] ?? null;
        if (is_resource($originalData)) {
            $originalData = stream_get_contents($originalData);
        }

        $encoderData = $this->buildEncoder($column);
        $encoder = $encoderData['encoder'];
        $columnConditions = $encoderData['columnConditions'];

        $encoderContext = [];
        if ($encoder instanceof ConditionalEncoder) {
            $encoderContext = [
                ConditionalEncoder::EXPRESSION_FUNCTION_PROVIDERS => [$this->conditionExpressionProvider],
                ConditionalEncoder::EXPRESSION_FUNCTION_CONTEXT => ConditionExpressionContextFactory::fromProcessorData($originalData, $databaseRow),
            ];
        }

        try {
            $decodedData = null === $originalData ? null : $encoder->decode($originalData, $encoderContext);
            $meanings = $this->buildMeanings($column, $databaseRow, $originalData, $decodedData, $columnConditions);
        } catch (\Throwable $e) {
            $decodedData = ['error' => true, 'message' => $e->getMessage()];
            $meanings = [];
        }

        unset($databaseRow[$column->getIdentifier()]);

        return [
            'original' => $originalData,
            'decoded' => $decodedData,
            'paths' => is_array($decodedData) || $decodedData instanceof \ArrayAccess ? $this->flattenArray($decodedData) : [],
            'meanings' => $meanings,
            'context' => $databaseRow,
        ];
    }

    /**
     * @param array<string, mixed>  $databaseRow
     * @param array<string, string> $columnConditions
     *
     * @return array<int, array{identifier: string, name: string, originalValue: mixed, fakedValue: mixed}>
     */
    private function buildMeanings(Column $column, array $databaseRow, mixed $originalData, mixed $decodedData, array $columnConditions): array
    {
        $expressionLanguage = new ExpressionLanguage(providers: [$this->conditionExpressionProvider]);
        $conditionExpressionContext = ConditionExpressionContextFactory::fromColumnProcessorData($originalData, $databaseRow, $decodedData);

        $meanings = [];
        foreach ($column->getMeanings() as $meaning) {
            $meaningProperty = $meaning->getProperty();
            $conditions = $this->buildMeaningConditions($meaning, $columnConditions);
            $condition = empty($conditions) ? null : implode(' && ', array_map(fn (string $condition): string => sprintf('(%s)', $condition), $conditions));
            if (
                $condition
                && !$expressionLanguage->evaluate($condition, ['context' => $conditionExpressionContext])
            ) {
                continue;
            }

            $dataToFake = $decodedData;

            $dataPath = Helper::buildPropertyAccessorPath($decodedData, $meaningProperty->getPath());

            if (!empty($dataPath)) {
                $dataToFake = $this->propertyAccessor->getValue($decodedData, $dataPath);
            }

            $scopedFaker = $this->faker
                ->withScope($meaningProperty->getScope() ?? Faker::DEFAULT_SCOPE)
                ->withSource($dataToFake)
            ;

            $callable = [$scopedFaker, $meaningProperty->getType()];
            $fakedData = call_user_func($callable, ...$meaningProperty->getContext());

            $meanings[] = [
                'identifier' => $meaning->getIdentifier(),
                'name' => $meaning->getName(),
                'originalValue' => $dataToFake,
                'fakedValue' => $fakedData,
            ];
        }

        return $meanings;
    }

    /**
     * @param array<string, string> $columnConditions
     *
     * @return array<array-key, string>
     */
    private function buildMeaningConditions(Meaning $meaning, array $columnConditions): array
    {
        $conditions = [];
        foreach ($meaning->getConditions() as $condition) {
            if (str_starts_with($condition, ColumnConfigurationDtoFactory::CONDITION_COPY_DIRECTIVE)) {
                $conditionIdentifier = substr($condition, strlen(ColumnConfigurationDtoFactory::CONDITION_COPY_DIRECTIVE));
                if (isset($columnConditions[$conditionIdentifier]) && !isset($conditions['reference'])) {
                    $conditions['reference'] = $columnConditions[$conditionIdentifier];
                }
            } else {
                $conditions[] = $condition;
            }
        }

        return $conditions;
    }

    /**
     * @return array{encoder: EncoderInterface, columnConditions: array<string, string>}
     */
    private function buildEncoder(Column $column): array
    {
        $columnConditions = [];
        if (!$column->hasEncodings()) {
            return [
                'encoder' => new ScalarEncoder([]),
                'columnConditions' => $columnConditions,
            ];
        }

        $encoderContext = [];
        foreach ($column->getEncodings() as $encoding) {
            if (!$encoding->hasEncoders()) {
                continue;
            }

            $condition = $encoding->hasConditions()
                ? implode(' && ', array_map(fn (string $condition): string => sprintf('(%s)', $condition), $encoding->getConditions()))
                : 'nocondition()'
            ;

            $columnConditions[$encoding->getIdentifier()] = $condition;

            $encoders = array_map(fn (Encoder $encoder): EncoderInterface => $encoder->getEncoder()->setContext($encoder->getContext()), $encoding->getEncoders());

            $encoderContext[ConditionalEncoder::CONDITIONS][] = [
                ConditionalEncoder::CONDITIONS_CONDITION => $condition,
                ConditionalEncoder::CONDITIONS_ENCODER => new ChainedEncoder($encoders),
            ];
        }

        return [
            'encoder' => empty($encoderContext) ? new ScalarEncoder([]) : new ConditionalEncoder($encoderContext),
            'columnConditions' => $columnConditions,
        ];
    }

    /**
     * @param array<array-key, mixed>|\ArrayAccess<int|string, mixed> $array
     *
     * @return array<string, mixed>
     */
    private function flattenArray(array|\ArrayAccess $array, string $prefix = ''): array
    {
        $flatArray = [];
        foreach ($array as $key => $_) {
            $key = rtrim((string) $key, '.');
            $value = $array[$key];
            if (is_array($value) || $value instanceof \ArrayAccess) {
                $newPrefix = $prefix.$key.'.';
                $flatArray = array_merge($flatArray, $this->flattenArray($value, $newPrefix));
            } else {
                $flatArray[$prefix.$key] = $value;
            }
        }

        return $flatArray;
    }
}
