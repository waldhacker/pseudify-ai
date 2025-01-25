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

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Psr\Log\LoggerInterface;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Database\Repository;
use Waldhacker\Pseudify\Core\Database\Schema;
use Waldhacker\Pseudify\Core\Faker\Faker;
use Waldhacker\Pseudify\Core\Processor\Encoder\ConditionalEncoder;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionContextFactory;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionProvider;
use Waldhacker\Pseudify\Core\Processor\Processing\Pseudonymize\DataManipulator;
use Waldhacker\Pseudify\Core\Processor\Processing\Pseudonymize\DataManipulatorContext;
use Waldhacker\Pseudify\Core\Profile\Model\Pseudonymize\Column;
use Waldhacker\Pseudify\Core\Profile\Model\Pseudonymize\Table;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\ProfileInterface;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\TableDefinitionAutoConfiguration;

/**
 * @internal
 */
class PseudonymizeProcessor
{
    /** @var array<string, array<string, DoctrineColumn>> */
    private array $columnInfo = [];
    /** @var array<string, array<string, array<int, string>>> */
    private array $tableInfo = [];

    public function __construct(
        private readonly TableDefinitionAutoConfiguration $tableDefinitionAutoConfiguration,
        private readonly ConnectionManager $connectionManager,
        private readonly Schema $schema,
        private readonly Repository $repository,
        private readonly DataManipulator $dataManipulator,
        private readonly ConditionExpressionProvider $conditionExpressionProvider,
        private readonly Faker $faker,
        private readonly LoggerInterface $pseudifyDryRunLogger,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function process(
        ProfileInterface $profile,
        bool $dryRun = false,
        ?string $tableName = null,
        ?int $page = null,
        ?int $itemsPerPage = null,
        ?callable $onBeforeTables = null,
        ?callable $onBeforeTable = null,
        ?callable $onAfterTable = null,
        ?callable $onTick = null,
    ): ProfileInterface {
        $tableDefinition = $this->tableDefinitionAutoConfiguration->configure($profile->getTableDefinition());
        $connection = $this->connectionManager->getConnection();

        foreach ($tableDefinition->getTables() as $table) {
            $this->setupTableInfo($table);
            foreach ($table->getColumns() as $column) {
                $this->setupColumnInfo($table, $column);
            }
        }

        if ($onBeforeTables) {
            $onBeforeTables($tableDefinition);
        }

        $isInTestMode = ($_SERVER['APP_ENV'] ?? null) === 'test';

        $tickInterval = 0.250000;
        $nextTick = microtime(true) + $tickInterval;
        foreach ($tableDefinition->getTables() as $table) {
            if ($onBeforeTable) {
                $onBeforeTable($table);
            }

            if ($tableName && $table->getIdentifier() !== $tableName) {
                continue;
            }

            $primaryKeyColumnNames = null;
            $updatedDataFromTablesWithoutPrimaryKeys = [];
            $result = $this->queryData($table, $page, $itemsPerPage);
            while ($row = $result->fetchAssociative()) {
                if (null === $primaryKeyColumnNames) {
                    $primaryKeyColumnNames = [];
                    foreach ($this->tableInfo[$table->getIdentifier()]['primaryKeyColumnNames'] ?? [] as $primaryKeyColumnName) {
                        if (!isset($row[$primaryKeyColumnName])) {
                            continue;
                        }

                        $primaryKeyColumnNames[] = $primaryKeyColumnName;
                        $primaryKeyColumn = Column::create($primaryKeyColumnName);
                        $this->setupColumnInfo($table, $primaryKeyColumn);
                    }
                }

                foreach ($table->getColumns() as $column) {
                    $originalData = $row[$column->getIdentifier()] ?? null;

                    if (empty($primaryKeyColumnNames) && in_array(md5((string) $originalData), $updatedDataFromTablesWithoutPrimaryKeys)) {
                        continue;
                    }

                    if (is_resource($originalData)) {
                        $originalData = stream_get_contents($originalData);
                        $row[$column->getIdentifier()] = $originalData;
                    }

                    $processedData = $this->processData($column, $row);

                    if ($originalData === $processedData) {
                        continue;
                    }

                    $updatedRows = 0;
                    $retries = 0;
                    $maxRetries = 10;
                    while ($retries < $maxRetries) {
                        if (!$isInTestMode) {
                            $connection->beginTransaction();
                        }

                        try {
                            $updatedRows = $this->updateData($table, $column, $originalData, $processedData, $row, $dryRun, $primaryKeyColumnNames);
                            if (!$isInTestMode) {
                                $connection->commit();
                            }

                            if (empty($primaryKeyColumnNames)) {
                                $updatedDataFromTablesWithoutPrimaryKeys[] = md5((string) $originalData);
                            }

                            break;
                        } catch (RetryableException $e) {
                            ++$retries;
                            if ($retries >= $maxRetries) {
                                $this->logger->error($e->getMessage());
                            } else {
                                $this->logger->debug($e->getMessage());
                            }

                            if (!$isInTestMode) {
                                $connection->rollBack();
                            }

                            if ($retries < $maxRetries) {
                                usleep(250000);
                            }
                        }
                    }

                    if (!$dryRun && 0 === $updatedRows) {
                        // $this->logger->warning(sprintf('table "%s" column "%s" could not be updated!', $table->getIdentifier(), $column->getIdentifier()));
                    }

                    if ($onTick && microtime(true) >= $nextTick) {
                        $onTick();
                        $nextTick = microtime(true) + $tickInterval;
                    }
                }

                if ($onTick && microtime(true) >= $nextTick) {
                    $onTick();
                    $nextTick = microtime(true) + $tickInterval;
                }
            }

            if ($onTick && microtime(true) >= $nextTick) {
                $onTick();
                $nextTick = microtime(true) + $tickInterval;
            }

            if ($onAfterTable) {
                $onAfterTable($table);
            }
        }

        return $profile;
    }

    private function queryData(Table $table, ?int $page = null, ?int $itemsPerPage = null): Result
    {
        $queryBuilder = $this->repository->getFindAllQueryBuilder(
            $table->getIdentifier(),
            $this->tableInfo[$table->getIdentifier()]['primaryKeyColumnNames'] ?? null
        );

        if ($page && $itemsPerPage) {
            $queryBuilder
                ->setFirstResult($itemsPerPage * ($page - 1))
                ->setMaxResults($itemsPerPage)
            ;
        }

        return $queryBuilder->executeQuery();
    }

    /**
     * @param array<array-key, mixed> $row
     */
    private function processData(Column $column, array $row): mixed
    {
        $originalData = $row[$column->getIdentifier()] ?? null;
        if (empty($originalData)) {
            return $originalData;
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

        $context = new DataManipulatorContext($this->faker, $originalData, $decodedData, $row);

        $processedData = $this->dataManipulator->process($context, $column->getDataProcessings());

        return (string) $encoder->encode($processedData, $encoderContext);
    }

    /**
     * @param array<array-key, mixed>  $row
     * @param array<array-key, string> $primaryKeyColumnNames
     */
    private function updateData(
        Table $table,
        Column $column,
        mixed $originalData,
        mixed $processedData,
        array $row,
        bool $dryRun,
        array $primaryKeyColumnNames,
    ): int {
        $connection = $this->connectionManager->getConnection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder
            ->update($connection->quoteIdentifier($table->getIdentifier()))
            ->set(
                $connection->quoteIdentifier($column->getIdentifier()),
                $queryBuilder->createNamedParameter($processedData, $this->getBindingType($table, $column))
            )
        ;

        if (empty($primaryKeyColumnNames)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $connection->quoteIdentifier($column->getIdentifier()),
                    $queryBuilder->createNamedParameter($originalData, $this->getBindingType($table, $column))
                )
            );
        } else {
            foreach ($primaryKeyColumnNames as $primaryKeyColumnName) {
                $primaryKeyValue = $row[$primaryKeyColumnName];
                $bindingType = $this->getBindingType($table, Column::create($primaryKeyColumnName));
                if (ParameterType::INTEGER === $bindingType && ctype_digit((string) $primaryKeyValue)) {
                    $primaryKeyValue = (int) $primaryKeyValue;
                }

                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq(
                        $connection->quoteIdentifier($primaryKeyColumnName),
                        $queryBuilder->createNamedParameter($primaryKeyValue, $bindingType)
                    )
                );
            }
        }

        call_user_func(
            $column->getBeforeUpdateDataCallback(),
            $queryBuilder,
            $table,
            $column,
            $this->columnInfo[$table->getIdentifier()][$column->getIdentifier()],
            $originalData,
            $processedData,
            $row
        );

        if (true === $dryRun) {
            $this->dumpSql($queryBuilder);

            return 0;
        }

        return $queryBuilder->executeStatement();
    }

    private function getBindingType(Table $table, Column $column): ?int
    {
        $columnInfo = $this->columnInfo[$table->getIdentifier()][$column->getIdentifier()] ?? null;

        return null === $columnInfo ? null : ($column->getBindingType() ?? $columnInfo->getType()->getBindingType());
    }

    private function setupTableInfo(Table $table): void
    {
        $this->tableInfo[$table->getIdentifier()] = [
            'primaryKeyColumnNames' => $this->schema->getPrimaryKeyColumnNames($table->getIdentifier()) ?? [],
        ];
    }

    private function setupColumnInfo(Table $table, Column $column): void
    {
        $this->columnInfo[$table->getIdentifier()] ??= [];
        if (null === ($this->columnInfo[$table->getIdentifier()][$column->getIdentifier()] ?? null)) {
            $columnInfo = $this->schema->getColumn($table->getIdentifier(), $column->getIdentifier());
            $this->columnInfo[$table->getIdentifier()][$column->getIdentifier()] = $columnInfo['column'];
        }
    }

    private function dumpSql(QueryBuilder $queryBuilder): void
    {
        $sql = $queryBuilder->getSQL();

        /** @var mixed $parameterValue */
        foreach ($queryBuilder->getParameters() as $parameterName => $parameterValue) {
            $sql = str_replace(
                sprintf(':%s', (string) $parameterName),
                sprintf(':%s:%s', $parameterName, var_export($parameterValue, true)),
                $sql
            );
        }

        $this->pseudifyDryRunLogger->info($sql);
    }
}
