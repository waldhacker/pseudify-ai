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

namespace Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition;

use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @internal
 */
class Table extends AbstractEntity implements IdentifierAwareInterface
{
    public function __construct(
        #[Groups(['prototype', 'userland'])]
        protected string $identifier,
        /** @var Column[] $columns */
        #[Groups(['prototype', 'userland'])]
        protected array $columns = [],
        /** @var string[] $excludedTargetColumns */
        #[Groups(['prototype', 'userland'])]
        protected array $excludedTargetColumns = [],
        /** @var string[] $excludedTargetColumnTypes */
        #[Groups(['prototype', 'userland'])]
        protected array $excludedTargetColumnTypes = [],
        #[Groups(['prototype', 'userland'])]
        protected string $tableDescription = '',
    ) {
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getTableDescription(): string
    {
        return $this->tableDescription;
    }

    public function setTableDescription(string $tableDescription): Table
    {
        $this->tableDescription = $tableDescription;

        return $this;
    }

    public function hasColumns(): bool
    {
        return !empty($this->columns);
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function addColumn(Column $column): Table
    {
        return $this->addToCollection($this->columns, $column);
    }

    public function getColumnByIdentifier(string $identifier): ?Column
    {
        /** @var ?Column $column */
        $column = $this->getFromCollectionByIdentifier($this->columns, $identifier);

        return $column;
    }

    public function removeColumn(Column $column): Table
    {
        return $this->removeFromCollection($this->columns, $column);
    }

    /**
     * @return string[]
     */
    public function getExcludedTargetColumns(): array
    {
        return $this->excludedTargetColumns;
    }

    public function addExcludedTargetColumn(string $excludedTargetColumn): Table
    {
        if (!in_array($excludedTargetColumn, $this->excludedTargetColumns)) {
            $this->excludedTargetColumns[] = $excludedTargetColumn;
        }

        return $this;
    }

    public function removeExcludedTargetColumn(string $excludedTargetColumn): Table
    {
        $this->excludedTargetColumns = array_values(array_filter($this->excludedTargetColumns, fn (string $columnName): bool => $columnName !== $excludedTargetColumn));

        return $this;
    }

    /**
     * @return string[]
     */
    public function getExcludedTargetColumnTypes(): array
    {
        return $this->excludedTargetColumnTypes;
    }

    public function merge(self $table): Table
    {
        /** @var Column[] $columns */
        $columns = $this->mergeCollection('columns', $table);

        return new self(
            $this->identifier,
            $columns,
            $table->getExcludedTargetColumns(),
            $table->getExcludedTargetColumnTypes(),
            $table->getTableDescription()
        );
    }
}
