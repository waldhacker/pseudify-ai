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

namespace Waldhacker\Pseudify\Core\Profile\Yaml\Profile;

use Waldhacker\Pseudify\Core\Processor\Encoder\ChainedEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\ConditionalEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\JsonEncoder;
use Waldhacker\Pseudify\Core\Processor\Processing\Analyze\SourceDataCollectorPreset;
use Waldhacker\Pseudify\Core\Processor\Processing\Analyze\TargetDataDecoderPreset;
use Waldhacker\Pseudify\Core\Profile\Analyze\ProfileInterface;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\SourceColumn;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\SourceTable;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\TableDefinition;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\TargetColumn;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\TargetTable;
use Waldhacker\Pseudify\Core\Profile\Yaml\Profile\Model\AnalyzerProfile;

/**
 * @internal
 */
class AnalyzerProfileFactory extends AbstractProfileFactory
{
    /**
     * @return array<array-key, ProfileInterface>
     */
    public function createProfiles(?string $connectionName = null): array
    {
        return array_map(fn (string $identifier): AnalyzerProfile => $this->createProfile($identifier, $connectionName), $this->profileDefinitionCollection->getProfileDefinitionIdentifiers());
    }

    public function createProfile(string $identifier, ?string $connectionName = null): AnalyzerProfile
    {
        $profileDefinition = $this->profileDefinitionFactory->load($identifier, $connectionName);
        $tableDefinition = new TableDefinition($identifier);

        $tableDefinition->setTargetDataFrameCuttingLength($profileDefinition->getTargetDataFrameCuttingLength());
        array_map(fn (string $sourceString): TableDefinition => $tableDefinition->addSourceString($sourceString), $profileDefinition->getSourceStrings());
        array_map(fn (string $excludedTargetTable): TableDefinition => $tableDefinition->excludeTargetTable($excludedTargetTable), $profileDefinition->getExcludedTargetTables());
        array_map(fn (string $excludedTargetColumnType): TableDefinition => $tableDefinition->excludeTargetColumnType($excludedTargetColumnType), $profileDefinition->getExcludedTargetColumnTypes());

        foreach ($profileDefinition->getTables() as $profileDefinitionTable) {
            $sourceTable = SourceTable::create($profileDefinitionTable->getIdentifier());
            $targetTable = TargetTable::create($profileDefinitionTable->getIdentifier());
            $tableDefinition->addSourceTable($sourceTable);
            $tableDefinition->addTargetTable($targetTable);

            $targetTable->excludeColumns($profileDefinitionTable->getExcludedTargetColumns());
            $targetTable->excludeColumnTypes($profileDefinitionTable->getExcludedTargetColumnTypes());

            foreach ($profileDefinitionTable->getColumns() as $profileDefinitionColumn) {
                $sourceColumn = SourceColumn::create($profileDefinitionColumn->getIdentifier());
                $targetColumn = TargetColumn::create($profileDefinitionColumn->getIdentifier());
                $sourceTable->addColumn($sourceColumn);
                $targetTable->addColumn($targetColumn);

                $columnConditions = [];
                $columnEncoder = null;
                if ($profileDefinitionColumn->hasEncodings()) {
                    $sourceEncoderContext = [];
                    $targetEncoderContext = [];
                    foreach ($profileDefinitionColumn->getEncodings() as $profileDefinitionEncoding) {
                        if (!$profileDefinitionEncoding->hasEncoders()) {
                            continue;
                        }

                        $condition = $profileDefinitionEncoding->hasConditions()
                            ? implode(' && ', array_map(fn (string $condition): string => sprintf('(%s)', $condition), $profileDefinitionEncoding->getConditions()))
                            : 'nocondition()'
                        ;

                        $columnConditions[$profileDefinitionEncoding->getIdentifier()] = $condition;

                        $sourceEncoders = [];
                        foreach ($profileDefinitionEncoding->getEncoders() as $encoder) {
                            $sourceEncoders[] = $encoder->getEncoder()->setContext($encoder->getContext());
                        }

                        $targetEncoders = [];
                        foreach ($profileDefinitionEncoding->getEncoders() as $encoder) {
                            if (!$encoder->getEncoder()->decodesToScalarDataOnly()) {
                                break;
                            }

                            $targetEncoders[] = $encoder->getEncoder()->setContext($encoder->getContext());
                        }

                        if (!empty($sourceEncoders)) {
                            $sourceEncoderContext[ConditionalEncoder::CONDITIONS][] = [
                                ConditionalEncoder::CONDITIONS_CONDITION => $condition,
                                ConditionalEncoder::CONDITIONS_ENCODER => new ChainedEncoder($sourceEncoders),
                            ];
                        }

                        if (!empty($targetEncoders)) {
                            $targetEncoderContext[ConditionalEncoder::CONDITIONS][] = [
                                ConditionalEncoder::CONDITIONS_CONDITION => $condition,
                                ConditionalEncoder::CONDITIONS_ENCODER => new ChainedEncoder($targetEncoders),
                            ];
                        }

                        $tmpEncoders = $profileDefinitionEncoding->getEncoders();
                        $lastEncoder = array_pop($tmpEncoders);
                        if ($lastEncoder && $lastEncoder->getEncoder() instanceof JsonEncoder) {
                            $targetColumn->addDataProcessing(TargetDataDecoderPreset::normalizedJsonString(
                                processingIdentifier: $profileDefinitionEncoding->getName(),
                                conditions: [$condition],
                            ));
                        }
                    }

                    if (!empty($sourceEncoderContext)) {
                        $columnEncoder = new ConditionalEncoder($sourceEncoderContext);
                        $sourceColumn->setEncoder($columnEncoder);
                    }

                    if (!empty($targetEncoderContext)) {
                        $columnEncoder = new ConditionalEncoder($targetEncoderContext);
                        $targetColumn->setEncoder($columnEncoder);
                    }
                }

                if (!$profileDefinitionColumn->hasMeanings()) {
                    $sourceTable->removeColumn($sourceColumn->getIdentifier());
                    continue;
                }

                $meaningNames = [];
                foreach ($profileDefinitionColumn->getMeanings() as $profileDefinitionMeaning) {
                    $processingIdentifier = $profileDefinitionMeaning->getName();
                    $meaningNames[$processingIdentifier] = isset($meaningNames[$processingIdentifier]) ? $meaningNames[$processingIdentifier] + 1 : 0;
                    if ($meaningNames[$processingIdentifier] > 0) {
                        $processingIdentifier = sprintf('%s (%s)', $processingIdentifier, $meaningNames[$processingIdentifier]);
                    }

                    $sourceColumn->addDataProcessing(SourceDataCollectorPreset::scalarData(
                        processingIdentifier: $processingIdentifier,
                        conditions: $this->buildMeaningConditions($profileDefinitionMeaning, $columnConditions),
                        collectFromPath: $profileDefinitionMeaning->getProperty()->getPath(),
                        minimumGraphemeLength: $profileDefinitionMeaning->getProperty()->getMinimumGraphemeLength()
                    ));
                }
            }
        }

        return new AnalyzerProfile($identifier, $this->analyzeTableDefinitionAutoConfiguration->configure($tableDefinition));
    }
}
