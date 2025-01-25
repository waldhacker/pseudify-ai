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

use Doctrine\DBAL\Result;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Waldhacker\Pseudify\Core\Command\ConcurrentExecutionTrait;
use Waldhacker\Pseudify\Core\Database\Repository;
use Waldhacker\Pseudify\Core\Processor\Analyze\DataSetComparator;
use Waldhacker\Pseudify\Core\Processor\Analyze\FindingDumper\ConsoleDumper;
use Waldhacker\Pseudify\Core\Processor\Analyze\FindingDumper\FindingDumperInterface;
use Waldhacker\Pseudify\Core\Processor\Analyze\StringHelper;
use Waldhacker\Pseudify\Core\Processor\Encoder\ConditionalEncoder;
use Waldhacker\Pseudify\Core\Processor\Processing\Analyze\SourceDataCollector;
use Waldhacker\Pseudify\Core\Processor\Processing\Analyze\SourceDataCollectorContext;
use Waldhacker\Pseudify\Core\Processor\Processing\Analyze\TargetDataDecoder;
use Waldhacker\Pseudify\Core\Processor\Processing\Analyze\TargetDataDecoderContext;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionContextFactory;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionProvider;
use Waldhacker\Pseudify\Core\Profile\Analyze\ProfileInterface;
use Waldhacker\Pseudify\Core\Profile\Analyze\TableDefinitionAutoConfiguration;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\Finding;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\SourceColumn;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\SourceTable;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\TableDefinition;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\TargetColumn;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\TargetTable;

/**
 * @internal
 */
class AnalyzeProcessor
{
    use ConcurrentExecutionTrait;

    private ?InputInterface $input = null;
    private ?OutputInterface $output = null;
    private ?SymfonyStyle $io = null;
    private ?ProgressBar $progressBar = null;
    private ?FindingDumperInterface $findingDumper = null;

    public function __construct(
        private readonly SourceDataCollector $sourceDataCollector,
        private readonly TargetDataDecoder $targetDataDecoder,
        private readonly TableDefinitionAutoConfiguration $tableDefinitionAutoConfiguration,
        private readonly Repository $repository,
        private readonly DataSetComparator $dataSetComparator,
        private readonly ConditionExpressionProvider $conditionExpressionProvider,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger,
        private string $cacheDirectory,
    ) {
        $this->cacheDirectory = rtrim($cacheDirectory, '/');
    }

    public function setIo(InputInterface $input, OutputInterface $output): AnalyzeProcessor
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
        $this->progressBar = $this->io->createProgressBar();
        $this->progressBar->setFormat('debug');
        $this->progressBar->setOverwrite(true);
        $this->findingDumper = new ConsoleDumper($this->io);

        return $this;
    }

    public function setFindingDumper(FindingDumperInterface $findingDumper): AnalyzeProcessor
    {
        $this->findingDumper = $findingDumper;

        return $this;
    }

    public function process(ProfileInterface $profile): ProfileInterface
    {
        $tableDefinition = $this->tableDefinitionAutoConfiguration->configure($profile->getTableDefinition());

        $cacheKey = hash('md5', random_bytes(1024));
        $fileInformation = $this->buildFileInformation($tableDefinition, $cacheKey);

        $this->filesystem->remove($this->cacheDirectory);

        try {
            $this->dumpSourceData($tableDefinition, $fileInformation);
            $this->io?->newLine(2);
            $this->dumpTargetData($tableDefinition, $fileInformation);
            $this->io?->newLine(2);
            $this->prepareDataFiles($fileInformation);
            $this->io?->newLine(2);
            $this->processSourceData($fileInformation);
            $this->io?->newLine(2);
            $this->showResult($tableDefinition, $fileInformation);
        } finally {
            $this->filesystem->remove($this->cacheDirectory);
        }

        return $profile;
    }

    /**
     * @param array{basePath: string, source: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}, target: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}} $fileInformation
     */
    private function processSourceData(array $fileInformation): void
    {
        $this->grepWithinTargetData($fileInformation);
        $this->io?->newLine(2);
        $this->processGreppedTargetData($fileInformation);
    }

    /**
     * @param array{basePath: string, source: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}, target: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}} $fileInformation
     */
    private function showResult(TableDefinition $tableDefinition, array $fileInformation): void
    {
        $this->io?->title('Prepare summary');

        $processedDataFinder = new Finder();
        $processedDataFinder->files()->name('*.grep.combined')->in($fileInformation['source']['path']);
        $numberOfFiles = count($processedDataFinder);

        $this->progressBar?->setMaxSteps($numberOfFiles);
        $this->progressBar?->start();

        $findings = [];
        foreach ($processedDataFinder as $file) {
            $processedData = json_decode($file->getContents(), true, JSON_THROW_ON_ERROR);
            $sourceTable = new SourceTable($processedData['sourceTable']);
            $sourceColumn = new SourceColumn($processedData['sourceColumn']);
            $collectedSourceData = $processedData['sourceData'];
            foreach ($processedData['targetData'] as $targetTableIdentifier => $tableData) {
                $targetTable = new TargetTable($targetTableIdentifier);
                foreach ($tableData as $targetColumnIdentifier => $collectedTargetData) {
                    $targetColumn = new TargetColumn($targetColumnIdentifier);
                    $findings = $this->compareDataSets(
                        $collectedSourceData,
                        $collectedTargetData,
                        $sourceTable,
                        $sourceColumn,
                        $targetTable,
                        $targetColumn,
                        $findings,
                        $tableDefinition->getTargetDataFrameCuttingLength()
                    );
                }
            }

            $this->progressBar?->advance();
        }

        $this->progressBar?->display();
        $this->progressBar?->finish();

        $this->io?->newLine(2);
        $this->findingDumper?->dump($findings, $this->io?->isVeryVerbose() ?? false);
    }

    /**
     * @param array<array-key, mixed> $collectedSourceData
     * @param array<array-key, mixed> $collectedTargetData
     * @param array<string, Finding>  $findings
     *
     * @return array<string, Finding>
     */
    private function compareDataSets(
        array $collectedSourceData,
        array $collectedTargetData,
        SourceTable $sourceTable,
        SourceColumn $sourceColumn,
        TargetTable $targetTable,
        TargetColumn $targetColumn,
        array $findings,
        int $targetDataFrameCuttingLength,
    ): array {
        $currentFindings = $this->dataSetComparator->compareDataSets(
            $collectedSourceData,
            $collectedTargetData,
            $sourceTable,
            $sourceColumn,
            $targetTable,
            $targetColumn,
            true,
            $targetDataFrameCuttingLength
        );

        $findings = array_merge($findings, $currentFindings);

        return $findings;
    }

    /**
     * @param array{basePath: string, source: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}, target: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}} $fileInformation
     */
    private function dumpSourceData(TableDefinition $tableDefinition, array $fileInformation): void
    {
        $count = count($tableDefinition->getSourceStrings());
        foreach ($tableDefinition->getSourceTables() as $sourceTable) {
            $count += $this->repository->count($sourceTable->getIdentifier()) * count($sourceTable->getColumns());
        }

        $this->io?->title('Decode and dump source data');
        $this->progressBar?->setMaxSteps($count);
        $this->progressBar?->start();

        if (!$this->filesystem->exists($fileInformation['source']['path'])) {
            $this->filesystem->mkdir($fileInformation['source']['path']);
        }

        foreach ($tableDefinition->getSourceTables() as $sourceTable) {
            $tableInformation = array_values(array_filter($fileInformation['source']['tables'] ?? [], fn (array $info): bool => $info['identifier'] === $sourceTable->getIdentifier()))[0] ?? [];
            if (!$this->filesystem->exists($tableInformation['path'] ?? '')) {
                $this->filesystem->mkdir($tableInformation['path'] ?? '');
            }

            $sourceResult = $this->querySourceData($sourceTable);
            while ($sourceRow = $sourceResult->fetchAssociative()) {
                foreach ($sourceTable->getColumns() as $sourceColumn) {
                    $columnInformation = array_values(array_filter($tableInformation['columns'] ?? [], fn (array $info): bool => $info['identifier'] === $sourceColumn->getIdentifier()))[0] ?? [];

                    $collectedSourceData = $this->collectSourceData($sourceColumn, $sourceRow);
                    if (empty($collectedSourceData)) {
                        $this->progressBar?->advance();
                        continue;
                    }

                    $normalizedSourceData = StringHelper::normalizeStrings($collectedSourceData);
                    foreach ($normalizedSourceData as $sourceData) {
                        if (in_array(strtolower((string) $sourceData), ['null'])) {
                            continue;
                        }

                        $this->filesystem->appendToFile(
                            $columnInformation['path'] ?? '',
                            preg_quote((string) $sourceData).PHP_EOL
                        );
                    }

                    $this->progressBar?->advance();
                }
            }

            $this->progressBar?->display();
        }
        $this->progressBar?->display();

        if (!empty($tableDefinition->getSourceStrings())) {
            $tableInformation = array_values(array_filter($fileInformation['source']['tables'] ?? [], fn (array $info): bool => '__custom__' === $info['identifier']))[0] ?? [];
            if (!$this->filesystem->exists($tableInformation['path'] ?? '')) {
                $this->filesystem->mkdir($tableInformation['path'] ?? '');
            }

            $columnInformation = array_values(array_filter($tableInformation['columns'] ?? [], fn (array $info): bool => '__custom__' === $info['identifier']))[0] ?? [];

            $normalizedSourceData = StringHelper::normalizeStrings($tableDefinition->getSourceStrings());
            foreach ($normalizedSourceData as $sourceData) {
                if ($sourceData->startsWith('regex:')) {
                    $sourceDataRegex = $sourceData->trimStart('regex:');
                    $sourceData = (string) $sourceDataRegex;
                } else {
                    $sourceData = preg_quote((string) $sourceData);
                }

                $this->filesystem->appendToFile(
                    $columnInformation['path'] ?? '',
                    $sourceData.PHP_EOL
                );

                $this->progressBar?->advance();
            }
        }

        $this->progressBar?->display();
        $this->progressBar?->finish();
    }

    /**
     * @param array{basePath: string, source: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}, target: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}} $fileInformation
     */
    private function dumpTargetData(TableDefinition $tableDefinition, array $fileInformation): void
    {
        $count = 0;
        foreach ($tableDefinition->getTargetTables() as $targetTable) {
            $count += $this->repository->count($targetTable->getIdentifier()) * count($targetTable->getColumns());
        }

        $this->io?->title('Decode and dump target data');
        $this->progressBar?->setMaxSteps($count);
        $this->progressBar?->start();

        if (!$this->filesystem->exists($fileInformation['target']['path'])) {
            $this->filesystem->mkdir($fileInformation['target']['path']);
        }

        foreach ($tableDefinition->getTargetTables() as $targetTable) {
            $tableInformation = array_values(array_filter($fileInformation['target']['tables'] ?? [], fn (array $info): bool => $info['identifier'] === $targetTable->getIdentifier()))[0] ?? [];
            if (!$this->filesystem->exists($tableInformation['path'] ?? '')) {
                $this->filesystem->mkdir($tableInformation['path'] ?? '');
            }

            $targetResult = $this->queryTargetData($targetTable);
            while ($targetRow = $targetResult->fetchAssociative()) {
                foreach ($targetTable->getColumns() as $targetColumn) {
                    $columnInformation = array_values(array_filter($tableInformation['columns'] ?? [], fn (array $info): bool => $info['identifier'] === $targetColumn->getIdentifier()))[0] ?? [];

                    $collectedTargetData = $this->collectTargetData($targetColumn, $targetRow);
                    if (empty($collectedTargetData)) {
                        $this->progressBar?->advance();
                        continue;
                    }

                    $normalizedTargetData = StringHelper::normalizeStrings($collectedTargetData);
                    foreach ($normalizedTargetData as $targetData) {
                        if (in_array(strtolower((string) $targetData), ['null'])) {
                            continue;
                        }

                        $this->filesystem->appendToFile(
                            $columnInformation['path'] ?? '',
                            (string) $targetData.PHP_EOL
                        );
                    }

                    $this->progressBar?->advance();
                }
            }
            $this->progressBar?->display();
        }

        $this->progressBar?->display();
        $this->progressBar?->finish();
    }

    private function querySourceData(SourceTable $sourceTable): Result
    {
        $queryBuilder = $this->repository->getFindAllQueryBuilder($sourceTable->getIdentifier());

        return $queryBuilder->executeQuery();
    }

    private function queryTargetData(TargetTable $targetTable): Result
    {
        $queryBuilder = $this->repository->getFindAllQueryBuilder($targetTable->getIdentifier());

        return $queryBuilder->executeQuery();
    }

    /**
     * @param array<array-key, mixed> $row
     *
     * @return array<array-key, int|float|bool|string>
     */
    private function collectSourceData(SourceColumn $column, array $row): array
    {
        $originalData = $row[$column->getIdentifier()] ?? null;
        if (empty($originalData)) {
            return [];
        }

        if (is_resource($originalData)) {
            $originalData = stream_get_contents($originalData);
        }

        $encoder = $column->getEncoder();
        $encoderContext = [];
        if ($encoder instanceof ConditionalEncoder) {
            $encoderContext = [
                ConditionalEncoder::EXPRESSION_FUNCTION_PROVIDERS => [$this->conditionExpressionProvider],
                ConditionalEncoder::EXPRESSION_FUNCTION_CONTEXT => ConditionExpressionContextFactory::fromProcessorData($originalData, $row),
            ];
        }

        $decodedData = $encoder->decode($originalData, $encoderContext);

        $context = new SourceDataCollectorContext($originalData, $decodedData, $row);
        $collectedData = $this->sourceDataCollector->process($context, $column->getDataProcessings());

        return array_filter(array_unique($collectedData));
    }

    /**
     * @param array<array-key, mixed> $row
     *
     * @return array<array-key, int|float|bool|string>
     */
    private function collectTargetData(TargetColumn $column, array $row): array
    {
        $originalData = $row[$column->getIdentifier()] ?? null;
        if (empty($originalData)) {
            return [];
        }

        if (is_resource($originalData)) {
            $originalData = stream_get_contents($originalData);
        }

        $encoder = $column->getEncoder();
        $encoderContext = [];

        if ($encoder instanceof ConditionalEncoder) {
            $encoderContext = [
                ConditionalEncoder::EXPRESSION_FUNCTION_PROVIDERS => [$this->conditionExpressionProvider],
                ConditionalEncoder::EXPRESSION_FUNCTION_CONTEXT => ConditionExpressionContextFactory::fromProcessorData($originalData, $row),
            ];
        }

        $decodedData = $encoder->decode($originalData, $encoderContext);

        $context = new TargetDataDecoderContext($originalData, $decodedData, $row);
        $collectedData = $this->targetDataDecoder->process($context, $column->getDataProcessings());

        return array_filter(array_unique($collectedData));
    }

    /**
     * @param array{basePath: string, source: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}, target: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}} $fileInformation
     */
    private function processGreppedTargetData(array $fileInformation): void
    {
        $greppedDataFinder = new Finder();
        $greppedDataFinder->files()->name('*.grep')->in($fileInformation['source']['path']);
        $numberOfFiles = count($greppedDataFinder);

        $this->io?->title('Process grepped target data');
        $this->progressBar?->setMaxSteps($numberOfFiles);
        $this->progressBar?->start();

        foreach ($greppedDataFinder as $file) {
            $sourceDataFileName = substr($file->getRealPath(), 0, -5);
            if (!$this->filesystem->exists($sourceDataFileName)) {
                $this->progressBar?->advance();
                continue;
            }

            $sourceData = file_get_contents($sourceDataFileName);
            if (empty($sourceData)) {
                $this->progressBar?->advance();
                continue;
            }

            $contents = $file->getContents();
            if (empty($contents)) {
                $this->progressBar?->advance();
                continue;
            }

            preg_match('#/([a-f0-9]{32})/([a-f0-9]{32}).raw.grep#', $file->getRealPath(), $matches);
            if (!($matches[1] ?? null) || !($matches[2] ?? null)) {
                $this->progressBar?->advance();
                continue;
            }

            $sourceTableIdentifier = $fileInformation['source']['tables'][$matches[1]]['identifier'] ?? null;
            $sourceColumnIdentifier = $fileInformation['source']['tables'][$matches[1]]['columns'][$matches[2]]['identifier'] ?? null;
            if (!$sourceTableIdentifier || !$sourceColumnIdentifier) {
                $this->progressBar?->advance();
                continue;
            }

            $targetData = [];
            foreach (explode(PHP_EOL, $contents) as $line) {
                $matches = null;
                preg_match('#^\./([a-f0-9]{32})/([a-f0-9]{32}).raw:(.*)#', $line, $matches);
                if (!($matches[1] ?? null) || !($matches[2] ?? null) || !($matches[3] ?? null)) {
                    continue;
                }

                $targetTableIdentifier = $fileInformation['target']['tables'][$matches[1]]['identifier'] ?? null;
                $targetColumnIdentifier = $fileInformation['target']['tables'][$matches[1]]['columns'][$matches[2]]['identifier'] ?? null;

                if (!$targetTableIdentifier || !$targetColumnIdentifier) {
                    continue;
                }

                $targetData[$targetTableIdentifier][$targetColumnIdentifier][] = $matches[3];
            }

            $processedFile = sprintf('%s.combined', $file->getRealPath());
            $this->filesystem->dumpFile(
                $processedFile,
                json_encode([
                    'sourceTable' => $sourceTableIdentifier,
                    'sourceColumn' => $sourceColumnIdentifier,
                    'sourceData' => array_map(fn (string $string): string => StringHelper::pregUnquote($string), array_filter(explode(PHP_EOL, $sourceData))),
                    'targetData' => $targetData,
                ])
            );

            $this->progressBar?->advance();
        }

        $this->progressBar?->display();
        $this->progressBar?->finish();
    }

    private function uniqueSortFiles(Finder $finder): void
    {
        if (null === $this->input || null === $this->output) {
            return;
        }

        $numberOfFiles = count($finder);
        $filesIterator = $finder->getIterator();
        $filesIterator->rewind();

        $this->progressBar?->setMaxSteps($numberOfFiles);
        $this->progressBar?->start();

        $concurrency = 10;
        $this->executeConcurrent(
            input: $this->input,
            output: $this->output,
            concurrency: $concurrency,
            appendVerbosityFlag: false,
            command: 'sort',
            arguments: ['-u', '-o', escapeshellarg('{{ identifier }}'), escapeshellarg('{{ identifier }}')],
            identifierPool: array_map(fn (SplFileInfo $file): string => $file->getRealPath(), $this->extractFilesToProcess($filesIterator, $concurrency)),
            identifierPoolFiller: function (int $numberOfRemovedItems) use ($filesIterator): array {
                if ($numberOfRemovedItems < 1) {
                    return [];
                }

                $filesToFill = array_map(fn (SplFileInfo $file): string => $file->getRealPath(), $this->extractFilesToProcess($filesIterator, $numberOfRemovedItems));
                $this->progressBar?->advance($numberOfRemovedItems);

                return $filesToFill;
            }
        );

        $this->progressBar?->display();
        $this->progressBar?->finish();
    }

    /**
     * @param array{basePath: string, source: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}, target: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}} $fileInformation
     */
    private function grepWithinTargetData(array $fileInformation): void
    {
        if (null === $this->input || null === $this->output) {
            return;
        }

        $sourceDataFinder = new Finder();
        $sourceDataFinder->files()->name('*.raw')->in($fileInformation['source']['path']);
        $sourceDataFileCount = count($sourceDataFinder);
        $filesIterator = $sourceDataFinder->getIterator();
        $filesIterator->rewind();

        $this->io?->title('Grep within target data');
        $this->progressBar?->setMaxSteps($sourceDataFileCount);
        $this->progressBar?->start();

        $concurrency = 10;
        $this->executeConcurrent(
            input: $this->input,
            output: $this->output,
            concurrency: $concurrency,
            appendVerbosityFlag: false,
            useTty: false,
            cwd: $fileInformation['target']['path'],
            command: 'grep',
            arguments: [
                // '--color=never',
                // '--binary-files=text',
                '-Hirosf',
                escapeshellarg('{{ identifier }}'),
                './',
                '>', escapeshellarg('{{ identifier }}.grep'),
            ],
            identifierPool: array_map(fn (SplFileInfo $file): string => $file->getRealPath(), $this->extractFilesToProcess($filesIterator, $concurrency)),
            identifierPoolFiller: function (int $numberOfRemovedItems) use ($filesIterator): array {
                if ($numberOfRemovedItems < 1) {
                    return [];
                }

                $filesToFill = array_map(fn (SplFileInfo $file): string => $file->getRealPath(), $this->extractFilesToProcess($filesIterator, $numberOfRemovedItems));
                $this->progressBar?->advance($numberOfRemovedItems);

                return $filesToFill;
            }
        );

        $this->progressBar?->display();
        $this->progressBar?->finish();
    }

    /**
     * @param array{basePath: string, source: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}, target: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}} $fileInformation
     */
    private function prepareDataFiles(array $fileInformation): void
    {
        if (null === $this->input || null === $this->output) {
            return;
        }

        $this->io?->title('Prepare data files');

        // Remove source lines with less than 3 characters
        $this->executeConcurrent(
            input: $this->input,
            output: $this->output,
            concurrency: 1,
            appendVerbosityFlag: false,
            cwd: $fileInformation['source']['path'],
            command: 'find',
            arguments: [
                escapeshellarg($fileInformation['source']['path']),
                '-name', escapeshellarg('*.raw'),
                '-exec',
                'sed -i -r', escapeshellarg('/^.{0,3}$/d'), '{} \;',
            ],
        );

        $this->uniqueSortFiles((new Finder())->files()->name('*.raw')->in($fileInformation['basePath']));
    }

    /**
     * @param \Iterator<string, SplFileInfo> $filesIterator
     *
     * @return array<int, SplFileInfo>
     */
    private function extractFilesToProcess(iterable $filesIterator, int $amount): array
    {
        $collection = [];
        for ($a = 0; $a < $amount; ++$a) {
            if ($filesIterator->valid()) {
                $collection[] = $filesIterator->current();
                $filesIterator->next();
            }
        }

        return $collection;
    }

    /**
     * @return array{basePath: string, source: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}, target: array{path: string, tables: array<string, array{path: string, identifier: string, columns: array<string, array{path: string, identifier: string}>}>}}
     */
    private function buildFileInformation(TableDefinition $tableDefinition, string $cacheKey): array
    {
        $fileInformation = [
            'basePath' => $this->cacheDirectory,
            'source' => [
                'path' => sprintf('%s/%s', $this->cacheDirectory, sprintf('analyze_source_data_%s', $cacheKey)),
                'tables' => [],
            ],
            'target' => [
                'path' => sprintf('%s/%s', $this->cacheDirectory, sprintf('analyze_target_data_%s', $cacheKey)),
                'tables' => [],
            ],
        ];

        foreach ($tableDefinition->getSourceTables() as $sourceTable) {
            $tableIdentifier = hash('md5', $sourceTable->getIdentifier());
            $fileInformation['source']['tables'][$tableIdentifier] = [
                'path' => sprintf('%s/%s', $fileInformation['source']['path'], $tableIdentifier),
                'identifier' => $sourceTable->getIdentifier(),
                'columns' => [],
            ];

            foreach ($sourceTable->getColumns() as $sourceColumn) {
                $columnIdentifier = hash('md5', $sourceColumn->getIdentifier());
                $fileInformation['source']['tables'][$tableIdentifier]['columns'][$columnIdentifier] = [
                    'path' => sprintf('%s/%s.raw', $fileInformation['source']['tables'][$tableIdentifier]['path'], $columnIdentifier),
                    'identifier' => $sourceColumn->getIdentifier(),
                ];
            }
        }

        if (!empty($tableDefinition->getSourceStrings())) {
            $tableIdentifier = hash('md5', '__custom__');
            $columnIdentifier = hash('md5', '__custom__');

            $fileInformation['source']['tables'][$tableIdentifier] = [
                'path' => sprintf('%s/%s', $fileInformation['source']['path'], $tableIdentifier),
                'identifier' => '__custom__',
                'columns' => [],
            ];

            $fileInformation['source']['tables'][$tableIdentifier]['columns'][$columnIdentifier] = [
                'path' => sprintf('%s/%s.raw', $fileInformation['source']['tables'][$tableIdentifier]['path'], $columnIdentifier),
                'identifier' => '__custom__',
            ];
        }

        foreach ($tableDefinition->getTargetTables() as $targetTable) {
            $tableIdentifier = hash('md5', $targetTable->getIdentifier());
            $fileInformation['target']['tables'][$tableIdentifier] = [
                'path' => sprintf('%s/%s', $fileInformation['target']['path'], $tableIdentifier),
                'identifier' => $targetTable->getIdentifier(),
                'columns' => [],
            ];

            foreach ($targetTable->getColumns() as $targetColumn) {
                $columnIdentifier = hash('md5', $targetColumn->getIdentifier());
                $fileInformation['target']['tables'][$tableIdentifier]['columns'][$columnIdentifier] = [
                    'path' => sprintf('%s/%s.raw', $fileInformation['target']['tables'][$tableIdentifier]['path'], $columnIdentifier),
                    'identifier' => $targetColumn->getIdentifier(),
                ];
            }
        }

        return $fileInformation;
    }
}
