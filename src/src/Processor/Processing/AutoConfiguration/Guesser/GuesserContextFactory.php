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

use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Database\Schema;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Column;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Table;

/**
 * @internal
 */
class GuesserContextFactory
{
    public function __construct(
        private readonly Schema $schema,
        private readonly ConnectionManager $connectionManager,
    ) {
    }

    public function fromProfileDefinition(
        ?ProfileDefinition $profileDefinition = null,
        ?Table $table = null,
        ?Column $column = null,
    ): GuesserContext {
        $columnType = '';
        if ($table && $column) {
            $connection = $this->connectionManager->getConnection();
            $platform = $connection->getDatabasePlatform();
            $databaseColumn = $this->schema->getColumn($table->getIdentifier(), $column->getIdentifier())['column'];
            $columnType = $databaseColumn->getType()->getSQLDeclaration($databaseColumn->toArray(), $platform);
        }

        return new GuesserContext(
            $profileDefinition?->getApplicationName() ?? '',
            $profileDefinition?->getApplicationDescription() ?? '',
            $table?->getIdentifier() ?? '',
            $table?->getTableDescription() ?? '',
            $column?->getIdentifier() ?? '',
            $column?->getColumnDescription() ?? '',
            $columnType
        );
    }
}
