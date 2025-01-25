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
use Waldhacker\Pseudify\Core\Processor\Encoder\EncoderInterface;
use Waldhacker\Pseudify\Core\Processor\Processing\Pseudonymize\DataManipulatorPreset;
use Waldhacker\Pseudify\Core\Profile\Model\Pseudonymize\Column;
use Waldhacker\Pseudify\Core\Profile\Model\Pseudonymize\Table;
use Waldhacker\Pseudify\Core\Profile\Model\Pseudonymize\TableDefinition;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\ProfileInterface;
use Waldhacker\Pseudify\Core\Profile\Yaml\Profile\Model\PseudonymizeProfile;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Encoder;

/**
 * @internal
 */
class PseudonymizeProfileFactory extends AbstractProfileFactory
{
    /**
     * @return array<array-key, ProfileInterface>
     */
    public function createProfiles(?string $connectionName = null): array
    {
        return array_map(fn (string $identifier): PseudonymizeProfile => $this->createProfile($identifier, $connectionName), $this->profileDefinitionCollection->getProfileDefinitionIdentifiers());
    }

    public function createProfile(string $identifier, ?string $connectionName = null): PseudonymizeProfile
    {
        $profileDefinition = $this->profileDefinitionFactory->load($identifier, $connectionName);
        $tableDefinition = new TableDefinition($identifier);

        foreach ($profileDefinition->getTables() as $profileDefinitionTable) {
            $table = Table::create($profileDefinitionTable->getIdentifier());
            $tableDefinition->addTable($table);

            foreach ($profileDefinitionTable->getColumns() as $profileDefinitionColumn) {
                $columnConditions = [];
                $encoderContext = [];
                foreach ($profileDefinitionColumn->getEncodings() as $profileDefinitionEncoding) {
                    if (!$profileDefinitionEncoding->hasEncoders()) {
                        continue;
                    }

                    $condition = $profileDefinitionEncoding->hasConditions()
                        ? implode(' && ', array_map(fn (string $condition): string => sprintf('(%s)', $condition), $profileDefinitionEncoding->getConditions()))
                        : 'nocondition()'
                    ;

                    $columnConditions[$profileDefinitionEncoding->getIdentifier()] = $condition;

                    $encoders = array_map(fn (Encoder $encoder): EncoderInterface => $encoder->getEncoder()->setContext($encoder->getContext()), $profileDefinitionEncoding->getEncoders());

                    $encoderContext[ConditionalEncoder::CONDITIONS][] = [
                        ConditionalEncoder::CONDITIONS_CONDITION => $condition,
                        ConditionalEncoder::CONDITIONS_ENCODER => new ChainedEncoder($encoders),
                    ];
                }

                if (!$profileDefinitionColumn->hasMeanings()) {
                    continue;
                }

                $column = Column::create($profileDefinitionColumn->getIdentifier());
                $table->addColumn($column);

                if (!empty($encoderContext)) {
                    $column->setEncoder(new ConditionalEncoder($encoderContext));
                }

                $meaningNames = [];
                foreach ($profileDefinitionColumn->getMeanings() as $profileDefinitionMeaning) {
                    $fakerFormatter = $profileDefinitionMeaning->getProperty()->getType();
                    if (null === $fakerFormatter) {
                        continue;
                    }

                    $processingIdentifier = $profileDefinitionMeaning->getName();
                    $meaningNames[$processingIdentifier] = isset($meaningNames[$processingIdentifier]) ? $meaningNames[$processingIdentifier] + 1 : 0;
                    if ($meaningNames[$processingIdentifier] > 0) {
                        $processingIdentifier = sprintf('%s (%s)', $processingIdentifier, $meaningNames[$processingIdentifier]);
                    }

                    $column->addDataProcessing(DataManipulatorPreset::scalarData(
                        processingIdentifier: $processingIdentifier,
                        conditions: $this->buildMeaningConditions($profileDefinitionMeaning, $columnConditions),
                        fakerFormatter: $fakerFormatter,
                        scope: $profileDefinitionMeaning->getProperty()->getScope(),
                        fakerArguments: $profileDefinitionMeaning->getProperty()->getContext(),
                        writeToPath: $profileDefinitionMeaning->getProperty()->getPath()
                    ));
                }
            }
        }

        return new PseudonymizeProfile($identifier, $this->pseudonymizeTableDefinitionAutoConfiguration->configure($tableDefinition));
    }
}
