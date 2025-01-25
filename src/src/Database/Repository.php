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

namespace Waldhacker\Pseudify\Core\Database;

use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Platforms\SQLServer2012Platform;
use Doctrine\DBAL\Query\QueryBuilder;

class Repository
{
    public function __construct(
        private readonly ConnectionManager $connectionManager,
        private readonly Schema $schema,
    ) {
    }

    /**
     * @param array<int, string>|null $orderBy
     */
    public function getFindAllQueryBuilder(string $tableName, ?array $orderBy = null): QueryBuilder
    {
        $connection = $this->connectionManager->getConnection();
        $queryBuilder = $connection->createQueryBuilder()
            ->select('*')
            ->from($connection->quoteIdentifier($tableName))
        ;

        $orderBy ??= $this->schema->getPrimaryKeyColumnNames($tableName);
        foreach ($orderBy ?? [] as $columnName) {
            $queryBuilder->addOrderBy($connection->quoteIdentifier($columnName), 'ASC');
        }

        return $queryBuilder;
    }

    public function count(string $tableName): int
    {
        $connection = $this->connectionManager->getConnection();
        $queryBuilder = $connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($connection->quoteIdentifier($tableName))
        ;

        $result = $queryBuilder->executeQuery()->fetchOne();

        return false === $result ? 0 : (int) $result;
    }

    /**
     * @param array<int, string>|null $orderBy
     *
     * @return iterable<array<array-key, mixed>>
     */
    public function findAll(string $tableName, ?array $orderBy = null): iterable
    {
        $result = $this->getFindAllQueryBuilder($tableName, $orderBy)->executeQuery();
        while ($row = $result->fetchAssociative()) {
            yield $row;
        }
    }

    /**
     * @return \Generator<mixed>
     */
    public function findColumnData(string $tableName, string $columnName, int $resultItems = 1): \Generator
    {
        $connection = $this->connectionManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder
          ->select($connection->quoteIdentifier($columnName))
          ->from($connection->quoteIdentifier($tableName))
          ->where($queryBuilder->expr()->isNotNull($connection->quoteIdentifier($columnName)))
          ->setMaxResults($resultItems)
        ;

        if ($platform instanceof SQLServer2012Platform) {
            $queryBuilder
                ->orderBy(sprintf('DATALENGTH(%s)', $connection->quoteIdentifier($columnName)), 'DESC')
                ->addOrderBy(sprintf('CONVERT(VARCHAR(MAX), %s)', $connection->quoteIdentifier($columnName)), 'DESC')
            ;
        } elseif ($platform instanceof PostgreSQL94Platform) {
            $queryBuilder
                ->orderBy(sprintf('LENGTH(CAST(%s AS TEXT))', $connection->quoteIdentifier($columnName)), 'DESC')
                ->addOrderBy($connection->quoteIdentifier($columnName), 'DESC')
            ;
        } else {
            $queryBuilder
                ->orderBy(sprintf('LENGTH(%s)', $connection->quoteIdentifier($columnName)), 'DESC')
                ->addOrderBy($connection->quoteIdentifier($columnName), 'DESC')
            ;
        }

        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            $columnData = $row[$columnName] ?? null;
            if (is_resource($columnData)) {
                $columnData = stream_get_contents($columnData);
            }

            yield $columnData;
        }
    }
}
