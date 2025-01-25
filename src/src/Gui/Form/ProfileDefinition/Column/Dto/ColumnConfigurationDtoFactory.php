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

namespace Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Dto;

use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Waldhacker\Pseudify\Core\Faker\FormatterInformation;
use Waldhacker\Pseudify\Core\Processor\Encoder\AdvancedEncoderCollection;
use Waldhacker\Pseudify\Core\Processor\Encoder\ScalarEncoder;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Column;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Encoder;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Encoding;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning\Property;

class ColumnConfigurationDtoFactory
{
    public const string NO_ENCODING_IDENTIFIER = '__internal__default__1736007947__internal__';
    public const string CONDITION_COPY_DIRECTIVE = 'use_encoding_conditions:';

    public function __construct(
        private readonly AdvancedEncoderCollection $encoderCollection,
        private readonly FormatterInformation $formatterInformation,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function fromColumn(Column $column): ColumnConfigurationDto
    {
        $meaningsByEncoding = [];
        foreach ($column->getMeanings() as $meaning) {
            $conditionReference = array_values(array_filter($meaning->getConditions(), fn (string $condition): bool => str_starts_with($condition, self::CONDITION_COPY_DIRECTIVE)))[0] ?? null;
            $encodingIdentifier = $conditionReference ? substr($conditionReference, strlen(self::CONDITION_COPY_DIRECTIVE)) : self::NO_ENCODING_IDENTIFIER;
            $meaningsByEncoding[$encodingIdentifier][] = $meaning;
        }

        $encodings = [];
        foreach ($column->getEncodings() as $encoding) {
            $encodings[] = $this->encodingToArray($encoding, $meaningsByEncoding);
        }

        foreach ($meaningsByEncoding[self::NO_ENCODING_IDENTIFIER] ?? [] as $meaning) {
            $encoding = new Encoding(
                identifier: self::NO_ENCODING_IDENTIFIER,
                name: $this->translator->trans('Auto generated'),
                encoders: [new Encoder((string) Uuid::v4(), new ScalarEncoder())],
            );

            $encodings[] = $this->encodingToArray($encoding, $meaningsByEncoding);
        }

        return new ColumnConfigurationDto($encodings, $column->getColumnDescription());
    }

    /**
     * @return Encoding[]
     */
    public function dtoToEncodings(ColumnConfigurationDto $encodingsDto): array
    {
        $encodings = [];
        foreach ($encodingsDto->encodings as $encoding) {
            $encoders = [];
            foreach ($encoding['encoders'] ?? [] as $encoder) {
                $object = $this->encoderCollection->hasEncoder($encoder['encoder'] ?? '') ? $this->encoderCollection->getEncoder($encoder['encoder'] ?? '') : new ScalarEncoder();
                $context = array_intersect_key(
                    $encoder['context'][0] ?? [],
                    $object->getContext()
                );

                $encoders[] = new Encoder(
                    $encoder['identifier'] ?? (string) Uuid::v4(),
                    $object,
                    $context
                );
            }

            $encodings[] = new Encoding(
                $encoding['identifier'] ?? (string) Uuid::v4(),
                $encoding['name'] ?? '',
                array_map(
                    fn (array $condition): string => empty($condition['condition']) ? 'nocondition()' : $condition['condition'],
                    $encoding['conditions'] ?? []
                ),
                $encoders,
            );
        }

        return $encodings;
    }

    /**
     * @return Meaning[]
     */
    public function dtoToMeanings(ColumnConfigurationDto $encodingsDto): array
    {
        $meanings = [];
        foreach ($encodingsDto->encodings as $encoding) {
            foreach ($encoding['meanings'] as $meaning) {
                $formatter = $this->formatterInformation->buildFormatterInformation()[$meaning['property']['type'] ?? ''] ?? null;
                if ($formatter) {
                    $defaultContext = [];
                    foreach ($formatter['parameters'] as $parameter) {
                        $defaultContext[$parameter['name']] = $parameter['defaultValue'] ?? '';
                    }

                    $context = array_intersect_key(
                        $meaning['property']['context'][0] ?? [],
                        $defaultContext
                    );
                } else {
                    $meaning['property']['type'] = '';
                    $context = [];
                }

                $meanings[] = new Meaning(
                    $meaning['identifier'] ?? (string) Uuid::v4(),
                    new Property(
                        $meaning['property']['path'] ?? '',
                        $meaning['property']['scope'] ?? '',
                        $meaning['property']['type'] ?? '',
                        (int) ($meaning['property']['minimumGraphemeLength'] ?? 3),
                        $context
                    ),
                    $meaning['name'] ?? '',
                    array_merge(
                        [self::CONDITION_COPY_DIRECTIVE.($encoding['identifier'] ?? '')],
                        array_map(
                            fn (array $condition): string => empty($condition['condition']) ? 'nocondition()' : $condition['condition'],
                            $meaning['conditions'] ?? []
                        )
                    )
                );
            }
        }

        return $meanings;
    }

    /**
     * @param array<string, array<array-key, Meaning>> $meaningsByEncoding
     *
     * @return array{identifier: string, name: string, conditions: array<int, array{condition: string}>, encoders: array<int, array{identifier: string, encoder: string, context: array<int, array<string, mixed>>}>, meanings: array<int, array{identifier: string, name: string, conditions: array<int, array{condition: string}>, property: array{path: string|null, scope: string|null, type: string|null, minimumGraphemeLength: int|null, context: array<int, array<string, mixed>>}}>}
     */
    private function encodingToArray(Encoding $encoding, array $meaningsByEncoding): array
    {
        $encoders = [];
        foreach ($encoding->getEncoders() as $encoder) {
            $encoders[] = $this->encoderToArray($encoder);
        }

        $meanings = [];
        foreach ($meaningsByEncoding[$encoding->getIdentifier()] ?? [] as $meaning) {
            $meanings[] = $this->meaningToArray($meaning);
        }

        return [
            'identifier' => self::NO_ENCODING_IDENTIFIER === $encoding->getIdentifier() ? (string) Uuid::v4() : $encoding->getIdentifier(),
            'name' => $encoding->getName(),
            'conditions' => array_map(fn (string $condition): array => ['condition' => $condition], $encoding->getConditions()),
            'encoders' => $encoders,
            'meanings' => $meanings,
        ];
    }

    /**
     * @return array{identifier: string, encoder: string, context: array<int, array<string, mixed>>}
     */
    private function encoderToArray(Encoder $encoder): array
    {
        $context = array_merge(
            $encoder->getEncoder()->getContext(),
            array_intersect_key(
                $encoder->getContext(),
                $encoder->getEncoder()->getContext()
            )
        );

        return [
            'identifier' => $encoder->getIdentifier(),
            'encoder' => $this->encoderCollection->getEncoderIdentifier($encoder->getEncoder()),
            'context' => [$context],
        ];
    }

    /**
     * @return array{identifier: string, name: string, conditions: array<int, array{condition: string}>, property: array{path: string|null, scope: string|null, type: string|null, minimumGraphemeLength: int|null, context: array<int, array<string, mixed>>}}
     */
    private function meaningToArray(Meaning $meaning): array
    {
        $formatter = $this->formatterInformation->buildFormatterInformation()[$meaning->getProperty()->getType()] ?? null;
        if ($formatter) {
            $defaultContext = [];
            foreach ($formatter['parameters'] as $parameter) {
                $defaultContext[$parameter['name']] = $parameter['defaultValue'] ?? null;
            }

            $context = array_merge(
                $defaultContext,
                array_intersect_key(
                    $meaning->getProperty()->getContext(),
                    $defaultContext
                )
            );
        } else {
            $context = [];
        }

        return [
            'identifier' => $meaning->getIdentifier(),
            'name' => $meaning->getName(),
            'conditions' => array_map(fn (string $condition): array => ['condition' => $condition], array_filter($meaning->getConditions(), fn (string $condition): bool => !str_starts_with($condition, self::CONDITION_COPY_DIRECTIVE))),
            'property' => [
                'path' => $meaning->getProperty()->getPath(),
                'scope' => $meaning->getProperty()->getScope(),
                'type' => $meaning->getProperty()->getType(),
                'minimumGraphemeLength' => $meaning->getProperty()->getMinimumGraphemeLength(),
                'context' => [$context],
            ],
        ];
    }
}
