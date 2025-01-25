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

namespace Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model;

use Symfony\Component\Serializer\Annotation\Groups;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Column;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Schema;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Table;

/**
 * @internal
 */
class ProfileDefinition
{
    public function __construct(
        #[Groups(['prototype', 'userland'])]
        private string $identifier,
        #[Groups(['prototype', 'userland'])]
        private string $description,
        #[Groups(['prototype', 'userland'])]
        private readonly Schema $schema,
        /** @var string[] $sourceStrings */
        #[Groups(['prototype', 'userland'])]
        private array $sourceStrings = [],
        /** @var string[] $excludedTargetTables */
        #[Groups(['prototype', 'userland'])]
        private array $excludedTargetTables = [],
        /** @var string[] $excludedTargetColumnTypes */
        #[Groups(['prototype', 'userland'])]
        private array $excludedTargetColumnTypes = [],
        #[Groups(['prototype', 'userland'])]
        private int $targetDataFrameCuttingLength = 10,
        #[Groups(['prototype'])]
        private ?string $path = null,
        #[Groups(['prototype'])]
        private ?string $contentHash = null,
        #[Groups(['prototype', 'userland'])]
        private string $applicationName = '',
        #[Groups(['prototype', 'userland'])]
        private string $applicationDescription = '',
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): ProfileDefinition
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): ProfileDefinition
    {
        $this->description = $description;

        return $this;
    }

    public function getApplicationName(): string
    {
        return $this->applicationName;
    }

    public function setApplicationName(string $applicationName): ProfileDefinition
    {
        $this->applicationName = $applicationName;

        return $this;
    }

    public function getApplicationDescription(): string
    {
        return $this->applicationDescription;
    }

    public function setApplicationDescription(string $applicationDescription): ProfileDefinition
    {
        $this->applicationDescription = $applicationDescription;

        return $this;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * @return string[]
     */
    public function getSourceStrings(): array
    {
        return $this->sourceStrings;
    }

    /**
     * @param array<array-key, mixed> $sourceStrings
     */
    public function setSourceStrings(array $sourceStrings): ProfileDefinition
    {
        $this->sourceStrings = array_values(array_filter($sourceStrings, 'is_string'));

        return $this;
    }

    /**
     * @return string[]
     */
    public function getExcludedTargetTables(): array
    {
        return $this->excludedTargetTables;
    }

    /**
     * @param array<array-key, mixed> $excludedTargetTables
     */
    public function setExcludedTargetTables(array $excludedTargetTables): ProfileDefinition
    {
        $this->excludedTargetTables = array_values(array_filter($excludedTargetTables, 'is_string'));

        return $this;
    }

    public function addExcludedTargetTable(string $excludedTargetTable): ProfileDefinition
    {
        if (!in_array($excludedTargetTable, $this->excludedTargetTables)) {
            $this->excludedTargetTables[] = $excludedTargetTable;
        }

        return $this;
    }

    public function removeExcludedTargetTable(string $excludedTargetTable): ProfileDefinition
    {
        $this->excludedTargetTables = array_values(array_filter($this->excludedTargetTables, fn (string $tableName): bool => $tableName !== $excludedTargetTable));

        return $this;
    }

    /**
     * @return string[]
     */
    public function getExcludedTargetColumnTypes(): array
    {
        return $this->excludedTargetColumnTypes;
    }

    /**
     * @param array<array-key, mixed> $excludedTargetColumnTypes
     */
    public function setExcludedTargetColumnTypes(array $excludedTargetColumnTypes): ProfileDefinition
    {
        $this->excludedTargetColumnTypes = array_values(array_filter($excludedTargetColumnTypes, 'is_string'));

        return $this;
    }

    public function getTargetDataFrameCuttingLength(): int
    {
        return $this->targetDataFrameCuttingLength;
    }

    public function setTargetDataFrameCuttingLength(int|float $targetDataFrameCuttingLength): ProfileDefinition
    {
        $this->targetDataFrameCuttingLength = (int) $targetDataFrameCuttingLength;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): ProfileDefinition
    {
        $this->path = $path;

        return $this;
    }

    public function getContentHash(): ?string
    {
        return $this->contentHash;
    }

    public function setContentHash(string $contentHash): ProfileDefinition
    {
        $this->contentHash = $contentHash;

        return $this;
    }

    /**
     * @return Table[]
     */
    public function getTables(): array
    {
        return $this->schema->getTables();
    }

    public function tableExists(Table|string $table): bool
    {
        return null !== $this->getTable($table);
    }

    public function getTable(Table|string $table): ?Table
    {
        return $this->schema->getTableByIdentifier(is_string($table) ? $table : $table->getIdentifier());
    }

    public function removeTable(Table $table): ProfileDefinition
    {
        $this->schema->removeTable($table);

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTableNames(): array
    {
        return array_map(fn (Table $table): string => $table->getIdentifier(), $this->schema->getTables());
    }

    /**
     * @return Column[]
     */
    public function getColumns(Table|string $table): array
    {
        return $this->getTable($table)?->getColumns() ?? [];
    }

    public function columnExists(Table|string $table, Column|string $column): bool
    {
        return null !== $this->getColumn($table, $column);
    }

    public function getColumn(Table|string $table, Column|string $column): ?Column
    {
        return $this->getTable($table)?->getColumnByIdentifier(is_string($column) ? $column : $column->getIdentifier());
    }

    public function removeColumn(Table|string $table, Column $column): ProfileDefinition
    {
        $this->getTable($table)?->removeColumn($column);

        return $this;
    }

    public function merge(self $profileDefinition): ProfileDefinition
    {
        return new self(
            $profileDefinition->getIdentifier(),
            $profileDefinition->getDescription(),
            $this->schema->merge($profileDefinition->getSchema()),
            $profileDefinition->getSourceStrings(),
            $profileDefinition->getExcludedTargetTables(),
            $profileDefinition->getExcludedTargetColumnTypes(),
            $profileDefinition->getTargetDataFrameCuttingLength(),
            $profileDefinition->getPath(),
            $profileDefinition->getContentHash(),
            $profileDefinition->getApplicationName(),
            $profileDefinition->getApplicationDescription(),
        );
    }
}
