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
class Schema extends AbstractEntity
{
    public function __construct(
        /** @var Table[] $tables */
        #[Groups(['prototype', 'userland'])]
        protected array $tables = [],
    ) {
    }

    public function hasTables(): bool
    {
        return !empty($this->tables);
    }

    /**
     * @return Table[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function addTable(Table $table): Schema
    {
        return $this->addToCollection($this->tables, $table);
    }

    public function getTableByIdentifier(string $identifier): ?Table
    {
        /** @var ?Table $table */
        $table = $this->getFromCollectionByIdentifier($this->tables, $identifier);

        return $table;
    }

    public function removeTable(Table $table): Schema
    {
        return $this->removeFromCollection($this->tables, $table);
    }

    public function merge(self $schema): Schema
    {
        /** @var Table[] $tables */
        $tables = $this->mergeCollection('tables', $schema);

        return new self(
            $tables
        );
    }
}
