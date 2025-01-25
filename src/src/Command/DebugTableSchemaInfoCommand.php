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

namespace Waldhacker\Pseudify\Core\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Database\Repository;
use Waldhacker\Pseudify\Core\Database\Schema;

#[AsCommand(
    name: 'pseudify:debug:table_schema',
    description: 'Show database schema info',
)]
class DebugTableSchemaInfoCommand extends Command
{
    public function __construct(
        private readonly Schema $schema,
        private readonly ConnectionManager $connectionManager,
        private readonly Repository $repository,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                'connection',
                null,
                InputOption::VALUE_REQUIRED,
                'The named database connection',
                null
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initializeConnection($input);

        $schema = array_map(
            fn (string $table): array => [
                'table' => $table,
                'columns' => $this->schema->listTableColumns($table),
            ],
            $this->schema->listTableNames()
        );

        usort(
            $schema,
            /**
             * @param array{table: string, columns: array} $itemA
             * @param array{table: string, columns: array} $itemB
             */
            static fn (array $itemA, array $itemB): int => strcmp($itemA['table'], $itemB['table'])
        );

        $io = new SymfonyStyle($input, $output);

        foreach ($schema as $data) {
            $io->section($data['table']);
            $tableData = [];
            foreach ($data['columns'] as $column) {
                $columnsData = $this->repository->findColumnData($data['table'], $column['name']);
                $exampleData = null;
                foreach ($columnsData as $columnData) {
                    $exampleData = null === $columnData ? '_NULL' : (is_string($columnData) ? $columnData : var_export($columnData, true));
                    $exampleData = strlen($exampleData) > 100 ? substr($exampleData, 0, 100).'...' : $exampleData;
                    break;
                }

                $tableData[] = [$column['name'], $column['column']->getType()->getName(), $exampleData];
            }
            $io->table(['column', 'type', 'data example'], $tableData);
        }

        return Command::SUCCESS;
    }

    private function initializeConnection(InputInterface $input): ?string
    {
        $connectionName = null;
        if ($input->hasOption('connection')) {
            /** @var array<int, string|int>|string|null $connectionName */
            $connectionName = $input->getOption('connection') ?? null;
            $connectionName = is_array($connectionName) ? $connectionName[0] : $connectionName;
            $connectionName = is_string($connectionName) ? $connectionName : null;
            $this->connectionManager->setConnectionName($connectionName);
        }

        return $connectionName;
    }
}
