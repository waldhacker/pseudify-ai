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

namespace Waldhacker\Pseudify\Core\Processor;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Database\Repository;
use Waldhacker\Pseudify\Core\Database\Schema;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Dto\ColumnConfigurationDtoFactory;
use Waldhacker\Pseudify\Core\Gui\Processing\ColumnProcessor;
use Waldhacker\Pseudify\Core\Processor\Encoder\ScalarEncoder;
use Waldhacker\Pseudify\Core\Processor\Processing\AutoConfiguration\Guesser\ApplicationGuesser;
use Waldhacker\Pseudify\Core\Processor\Processing\AutoConfiguration\Guesser\ColumnGuesser;
use Waldhacker\Pseudify\Core\Processor\Processing\AutoConfiguration\Guesser\EncodingsGuesser;
use Waldhacker\Pseudify\Core\Processor\Processing\AutoConfiguration\Guesser\GuesserContext;
use Waldhacker\Pseudify\Core\Processor\Processing\AutoConfiguration\Guesser\GuesserContextFactory;
use Waldhacker\Pseudify\Core\Processor\Processing\AutoConfiguration\Guesser\MeaningGuesser;
use Waldhacker\Pseudify\Core\Processor\Processing\AutoConfiguration\Guesser\TableGuesser;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\TableDefinition as AnalyzeTableDefinition;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Column;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Encoder;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Encoding;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Table;

/**
 * @internal
 */
class AutoConfigurationProcessor
{
    private ?SymfonyStyle $io = null;
    private ?ProgressBar $progressBar = null;

    public function __construct(
        private readonly EncodingsGuesser $encodingsGuesser,
        private readonly MeaningGuesser $meaningGuesser,
        private readonly ApplicationGuesser $applicationGuesser,
        private readonly TableGuesser $tableGuesser,
        private readonly ColumnGuesser $columnGuesser,
        private readonly GuesserContextFactory $guesserContextFactory,
        private readonly ConnectionManager $connectionManager,
        private readonly Repository $repository,
        private readonly ColumnProcessor $columnProcessor,
        private readonly Schema $schema,
    ) {
    }

    public function setIo(InputInterface $input, OutputInterface $output): AutoConfigurationProcessor
    {
        $this->io = new SymfonyStyle($input, $output);

        ProgressBar::setFormatDefinition('custom', ProgressBar::getFormatDefinition(ProgressBar::FORMAT_VERY_VERBOSE).'%message%');
        $this->progressBar = $this->io->createProgressBar();
        $this->progressBar->setOverwrite(true);
        $this->progressBar->setFormat('custom');
        $this->progressBar->setMessage('');

        return $this;
    }

    public function process(ProfileDefinition $profileDefinition): ProfileDefinition
    {
        $connection = $this->connectionManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        // @todo: event before process profile definition
        if (empty($profileDefinition->getExcludedTargetColumnTypes())) {
            $profileDefinition->setExcludedTargetColumnTypes(AnalyzeTableDefinition::COMMON_EXCLUED_TARGET_COLUMN_TYPES);
        }

        $guesserContext = $this->guesserContextFactory->fromProfileDefinition($profileDefinition);

        $processingCount = 0;
        foreach ($profileDefinition->getTables() as $profileDefinitionTable) {
            if (in_array($profileDefinitionTable->getIdentifier(), $profileDefinition->getExcludedTargetTables())) {
                continue;
            }
            foreach ($profileDefinitionTable->getColumns() as $profileDefinitionColumn) {
                if (
                    in_array($profileDefinitionColumn->getIdentifier(), $profileDefinitionTable->getExcludedTargetColumns())
                    || in_array($profileDefinitionColumn->getDatabaseType(), $profileDefinitionTable->getExcludedTargetColumnTypes())
                    || in_array($profileDefinitionColumn->getDatabaseType(), $profileDefinition->getExcludedTargetColumnTypes())
                ) {
                    continue;
                }
                ++$processingCount;
            }
        }

        $this->progressBar?->setMaxSteps($processingCount);
        $this->progressBar?->start();

        $this->io?->title('Prepare the analyzer');
        if (empty($profileDefinition->getApplicationName()) || empty($profileDefinition->getApplicationDescription())) {
            try {
                $guesserContext = $this->enrichContextWithApplicationInformation($profileDefinition, $guesserContext);
            } catch (\Throwable) {
            }

            $profileDefinition
                ->setApplicationName($guesserContext->applicationName)
                ->setApplicationDescription($guesserContext->applicationDescription)
            ;
        }

        $guesserContext = $guesserContext
            ->withApplicationName($profileDefinition->getApplicationName())
            ->withApplicationDescription($profileDefinition->getApplicationDescription())
        ;

        $this->io?->newLine(2);
        $this->io?->title('Start autoconfiguration');
        foreach ($profileDefinition->getTables() as $profileDefinitionTable) {
            $guesserContext = $guesserContext->withTableName($profileDefinitionTable->getIdentifier());

            // @todo: event before process table
            if (in_array($profileDefinitionTable->getIdentifier(), $profileDefinition->getExcludedTargetTables())) {
                continue;
            }

            if (empty($profileDefinitionTable->getTableDescription())) {
                try {
                    $guesserContext = $this->enrichContextWithTableInformation($profileDefinitionTable, $guesserContext);
                } catch (\Throwable) {
                }

                $profileDefinitionTable->setTableDescription($guesserContext->tableDescription);
            }

            $guesserContext = $guesserContext->withTableDescription($profileDefinitionTable->getTableDescription());

            foreach ($profileDefinitionTable->getColumns() as $profileDefinitionColumn) {
                $guesserContext = $guesserContext->withColumnName($profileDefinitionColumn->getIdentifier());

                // @todo: event before process column
                if (
                    in_array($profileDefinitionColumn->getIdentifier(), $profileDefinitionTable->getExcludedTargetColumns())
                    || in_array($profileDefinitionColumn->getDatabaseType(), $profileDefinitionTable->getExcludedTargetColumnTypes())
                    || in_array($profileDefinitionColumn->getDatabaseType(), $profileDefinition->getExcludedTargetColumnTypes())
                ) {
                    continue;
                }

                $databaseColumn = $this->schema->getColumn($profileDefinitionTable->getIdentifier(), $profileDefinitionColumn->getIdentifier())['column'];
                $guesserContext = $guesserContext->withColumnType($databaseColumn->getType()->getSQLDeclaration($databaseColumn->toArray(), $platform));

                $this->progressBar?->setMessage(sprintf(' -- Process %s.%s (%s) - %s', $guesserContext->tableName, $guesserContext->columnName, $guesserContext->applicationName, 'collect column data'));
                $this->progressBar?->display();

                try {
                    $originalData = array_filter(
                        iterator_to_array($this->repository->findColumnData($profileDefinitionTable->getIdentifier(), $profileDefinitionColumn->getIdentifier(), 300)),
                        fn (mixed $data): bool => is_string($data) && mb_strlen($data) >= MeaningGuesser::MIN_DATA_LENGTH
                    );
                } catch (\Throwable) {
                    $originalData = [];
                }
                if (empty($originalData)) {
                    $this->progressBar?->advance();
                    continue;
                }

                if (empty($profileDefinitionColumn->getColumnDescription())) {
                    try {
                        $guesserContext = $this->enrichContextWithColumnInformation($guesserContext);
                    } catch (\Throwable) {
                    }

                    $profileDefinitionColumn->setColumnDescription($guesserContext->columnDescription);
                }

                $guesserContext = $guesserContext->withColumnDescription($profileDefinitionColumn->getColumnDescription());

                // @todo: event before detect encoding
                if (!$profileDefinitionColumn->hasEncodings()) {
                    $encodersToApply = $this->detectEncoders($originalData, $guesserContext);
                    $profileDefinitionColumn->addEncoding(new Encoding((string) Uuid::v4(), '', [], $encodersToApply));
                }

                $this->progressBar?->setMessage(sprintf(' -- Process %s.%s (%s) - %s', $guesserContext->tableName, $guesserContext->columnName, $guesserContext->applicationName, 'decode column data'));
                $this->progressBar?->display();

                $decodedData = [];
                foreach ($originalData as $columnData) {
                    $decodedColumnData = $this->columnProcessor->processDatabaseRow($profileDefinitionColumn, [$profileDefinitionColumn->getIdentifier() => $columnData]);
                    unset($decodedColumnData['context'], $decodedColumnData['meanings']);
                    $decodedData[] = $decodedColumnData;
                }

                // @todo: event before detect meaning
                if (!$profileDefinitionColumn->hasMeanings()) {
                    $meaningsToApply = $this->detectMeanings($decodedData, $guesserContext);

                    foreach ($profileDefinitionColumn->getEncodings() as $encoding) {
                        foreach ($meaningsToApply as $meaningToApply) {
                            $meaningToApply->addCondition(ColumnConfigurationDtoFactory::CONDITION_COPY_DIRECTIVE.$encoding->getIdentifier());
                        }

                        break;
                    }

                    $profileDefinitionColumn->setMeanings($meaningsToApply);
                }

                $this->progressBar?->advance();
            }
        }

        return $profileDefinition;
    }

    private function enrichContextWithApplicationInformation(ProfileDefinition $profileDefinition, GuesserContext $guesserContext): GuesserContext
    {
        $this->progressBar?->setMessage(sprintf(' -- %s', 'Ask LLM for application information'));
        $this->progressBar?->display();

        return $this->applicationGuesser->guess($profileDefinition, $guesserContext);
    }

    private function enrichContextWithTableInformation(Table $profileDefinitionTable, GuesserContext $guesserContext): GuesserContext
    {
        $this->progressBar?->setMessage(sprintf(' -- Process %s (%s) - %s', $guesserContext->tableName, $guesserContext->applicationName, 'Guess table information'));
        $this->progressBar?->display();

        return $this->tableGuesser->guess($profileDefinitionTable, $guesserContext);
    }

    private function enrichContextWithColumnInformation(GuesserContext $guesserContext): GuesserContext
    {
        $this->progressBar?->setMessage(sprintf(' -- Process %s.%s (%s) - %s', $guesserContext->tableName, $guesserContext->columnName, $guesserContext->applicationName, 'Guess column information'));
        $this->progressBar?->display();

        return $this->columnGuesser->guess($guesserContext);
    }

    /**
     * @param array<string, mixed> $originalData
     *
     * @return Encoder[]
     */
    private function detectEncoders(
        array $originalData,
        GuesserContext $guesserContext,
    ): array {
        $this->progressBar?->setMessage(sprintf(' -- Process %s.%s (%s) - %s', $guesserContext->tableName, $guesserContext->columnName, $guesserContext->applicationName, 'Guess column encoding'));
        $this->progressBar?->display();

        $possibleEncoders = $this->encodingsGuesser->guess($originalData, $guesserContext);

        $encodersToApply = [];
        foreach ($possibleEncoders as $possibleEncoder) {
            $encoder = $possibleEncoder['encoder'] ?? null;
            if (!$encoder) {
                continue;
            }

            $context = array_intersect_key(
                $possibleEncoder['context'] ?? [],
                $encoder->getContext()
            );

            $encodersToApply[] = new Encoder((string) Uuid::v4(), $encoder, $context);
            // At the moment we are using the most likely encoder
            break;
        }

        if (empty($encodersToApply)) {
            $encodersToApply[] = new Encoder((string) Uuid::v4(), new ScalarEncoder(), []);
        }

        return $encodersToApply;
    }

    /**
     * @param array<array-key, array{original: array<string, mixed>, decoded: mixed, paths: array<string, mixed>}> $decodedData
     *
     * @return Meaning[]
     */
    private function detectMeanings(
        array $decodedData,
        GuesserContext $guesserContext,
    ): array {
        $this->progressBar?->setMessage(sprintf(' -- Process %s.%s (%s) - %s', $guesserContext->tableName, $guesserContext->columnName, $guesserContext->applicationName, 'Guess column meanings'));
        $this->progressBar?->display();

        return $this->meaningGuesser->guess($decodedData, $guesserContext);
    }
}
