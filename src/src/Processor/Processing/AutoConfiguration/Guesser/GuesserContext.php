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

namespace Waldhacker\Pseudify\Core\Processor\Processing\AutoConfiguration\Guesser;

/**
 * @internal
 */
class GuesserContext
{
    public function __construct(
        public readonly string $applicationName = '',
        public readonly string $applicationDescription = '',
        public readonly string $tableName = '',
        public readonly string $tableDescription = '',
        public readonly string $columnName = '',
        public readonly string $columnDescription = '',
        public readonly string $columnType = '',
    ) {
    }

    public function withApplicationName(string $applicationName): GuesserContext
    {
        return new self(...array_replace(
            $this->toArray(),
            ['applicationName' => $applicationName]
        ));
    }

    public function withApplicationDescription(string $applicationDescription): GuesserContext
    {
        return new self(...array_replace(
            $this->toArray(),
            ['applicationDescription' => $applicationDescription]
        ));
    }

    public function withTableName(string $tableName): GuesserContext
    {
        return new self(...array_replace(
            $this->toArray(),
            ['tableName' => $tableName]
        ));
    }

    public function withTableDescription(string $tableDescription): GuesserContext
    {
        return new self(...array_replace(
            $this->toArray(),
            ['tableDescription' => $tableDescription]
        ));
    }

    public function withColumnName(string $columnName): GuesserContext
    {
        return new self(...array_replace(
            $this->toArray(),
            ['columnName' => $columnName]
        ));
    }

    public function withColumnType(string $columnType): GuesserContext
    {
        return new self(...array_replace(
            $this->toArray(),
            ['columnType' => $columnType]
        ));
    }

    public function withColumnDescription(string $columnDescription): GuesserContext
    {
        return new self(...array_replace(
            $this->toArray(),
            ['columnDescription' => $columnDescription]
        ));
    }

    /**
     * @return array{applicationName: string, applicationDescription: string, tableName: string, tableDescription: string, columnName: string, columnType: string, columnDescription: string}
     */
    public function toArray(): array
    {
        return [
            'applicationName' => $this->applicationName,
            'applicationDescription' => $this->applicationDescription,
            'tableName' => $this->tableName,
            'tableDescription' => $this->tableDescription,
            'columnName' => $this->columnName,
            'columnType' => $this->columnType,
            'columnDescription' => $this->columnDescription,
        ];
    }
}
