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

namespace Waldhacker\Pseudify\Core\Profile\Analyze;

use Waldhacker\Pseudify\Core\Database\Repository;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\Stats;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\TableDefinition;

/**
 * @internal
 */
class Statistics
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function create(TableDefinition $tableDefinition): Stats
    {
        $sourceTableRowCount = [];
        $targetTableRowCount = [];
        $sourceTableColumnCount = [];
        $targetTableColumnCount = [];
        foreach ($tableDefinition->getSourceTables() as $table) {
            $sourceTableRowCount[$table->getIdentifier()] = $this->repository->count($table->getIdentifier());
            $sourceTableColumnCount[$table->getIdentifier()] = count($table->getColumns());
        }
        foreach ($tableDefinition->getTargetTables() as $table) {
            $targetTableRowCount[$table->getIdentifier()] = $this->repository->count($table->getIdentifier());
            $targetTableColumnCount[$table->getIdentifier()] = count($table->getColumns());
        }

        $stats = new Stats(
            $sourceTableRowCount,
            $sourceTableColumnCount,
            $targetTableRowCount,
            $targetTableColumnCount,
            !empty($tableDefinition->getSourceStrings())
        );

        return $stats;
    }
}
