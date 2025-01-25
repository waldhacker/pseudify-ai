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

use Doctrine\DBAL\Types\Type;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Processor\Encoder\AdvancedEncoderCollection;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionProvider;
use Waldhacker\Pseudify\Core\Profile\ProfileCollection;

#[AsCommand(
    name: 'pseudify:information',
    description: 'Show application information',
)]
class InformationCommand extends Command
{
    public function __construct(
        private readonly ProfileCollection $profileCollection,
        private readonly ConditionExpressionProvider $conditionExpressionProvider,
        private readonly AdvancedEncoderCollection $encoderCollection,
        private readonly ConnectionManager $connectionManager,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Registered analyze profiles');
        $io->table(
            ['Profile name'],
            array_map(static fn (string $profileName): array => [$profileName], $this->profileCollection->getProfileIdentifiers(ProfileCollection::SCOPE_ANALYZE))
        );
        $io->section('Registered pseudonymize profiles');
        $io->table(
            ['Profile name'],
            array_map(static fn (string $profileName): array => [$profileName], $this->profileCollection->getProfileIdentifiers(ProfileCollection::SCOPE_PSEUDONYMIZE))
        );
        $io->section('Registered condition expression functions');
        $io->table(
            ['Function name', 'Description'],
            $this->conditionExpressionProvider->getFunctionInformation()
        );
        $io->section('Registered encoders');
        $io->table(
            ['Encoder name'],
            array_map(static fn (string $encoderName): array => [$encoderName], array_keys($this->encoderCollection->getEncodersIndexedByShortName()))
        );
        $io->section('Registered doctrine types');
        $io->table(
            ['Doctrine type name', 'Doctrine type implementation'],
            array_map(static fn (Type $type): array => [$type->getName(), $type::class], Type::getTypeRegistry()->getMap())
        );

        // https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/configuration.html#driver
        $io->section('Available built-in database drivers');
        $io->table(
            ['Driver', 'Description', 'Installed version'],
            [
                [new TableCell('MySQL / MariaDB', ['colspan' => 3])],
                new TableSeparator(),
                ['pdo_mysql', 'A MySQL driver that uses the pdo_mysql PDO extension', empty(phpversion('pdo_mysql')) ? 'N/A' : phpversion('pdo_mysql')],
                ['mysqli', 'A MySQL driver that uses the mysqli extension', empty(phpversion('mysqli')) ? 'N/A' : phpversion('mysqli')],
                new TableSeparator(),
                [new TableCell('PostgreSQL', ['colspan' => 3])],
                new TableSeparator(),
                ['pdo_pgsql', 'A PostgreSQL driver that uses the pdo_pgsql PDO extension', empty(phpversion('pdo_pgsql')) ? 'N/A' : phpversion('pdo_pgsql')],
                new TableSeparator(),
                [new TableCell('SQLite', ['colspan' => 3])],
                new TableSeparator(),
                ['pdo_sqlite', 'An SQLite driver that uses the pdo_sqlite PDO extension', empty(phpversion('pdo_sqlite')) ? 'N/A' : phpversion('pdo_sqlite')],
                ['sqlite3', 'An SQLite driver that uses the sqlite3 extension', empty(phpversion('sqlite3')) ? 'N/A' : phpversion('sqlite3')],
                new TableSeparator(),
                [new TableCell('SQL Server', ['colspan' => 3])],
                new TableSeparator(),
                ['pdo_sqlsrv', 'A Microsoft SQL Server driver that uses pdo_sqlsrv PDO', empty(phpversion('pdo_sqlsrv')) ? 'N/A' : phpversion('pdo_sqlsrv')],
                ['sqlsrv', 'A Microsoft SQL Server driver that uses the sqlsrv PHP extension', empty(phpversion('sqlsrv')) ? 'N/A' : phpversion('sqlsrv')],
                new TableSeparator(),
                [new TableCell('Oracle Database', ['colspan' => 3])],
                new TableSeparator(),
                ['pdo_oci', 'An Oracle driver that uses the pdo_oci PDO extension (not recommended by doctrine)', empty(phpversion('pdo_oci')) ? 'N/A' : phpversion('pdo_oci')],
                ['oci8', 'An Oracle driver that uses the oci8 PHP extension', empty(phpversion('oci8')) ? 'N/A' : phpversion('oci8')],
                new TableSeparator(),
                [new TableCell('IBM DB2', ['colspan' => 3])],
                new TableSeparator(),
                ['pdo_ibm', 'An DB2 driver that uses the pdo_ibm PHP extension', empty(phpversion('pdo_ibm')) ? 'N/A' : phpversion('pdo_ibm')],
                ['ibm_db2', 'An DB2 driver that uses the ibm_db2 extension', empty(phpversion('ibm_db2')) ? 'N/A' : phpversion('ibm_db2')],
            ]
        );

        foreach ($this->connectionManager->getConnections() as $connectionName => $connection) {
            $platform = $connection->getDatabasePlatform();
            $configuredBuiltInDriver = $connection->getParams()['driver'] ?? null;
            /** @var array<string, string> $doctrineTypeMappings */
            $doctrineTypeMappings = (new \ReflectionClass($platform))->getProperty('doctrineTypeMapping')->getValue($platform);
            $doctrineTypeMappings = array_map(
                static fn (string $databaseType, string $doctrineTypeName): array => [$databaseType, $doctrineTypeName, Type::getType($doctrineTypeName)::class],
                array_keys($doctrineTypeMappings),
                array_values($doctrineTypeMappings),
            );

            $io->title(sprintf('Connection information for connection "%s"', $connectionName));

            $io->section('Registered doctrine database data type mappings');
            $io->table(
                ['Database type', 'Doctrine type name', 'Doctrine type implementation'],
                $doctrineTypeMappings
            );

            $io->section('Connection details');
            $io->table(
                ['Name', 'Value'],
                [
                    ['Used connection implementation', $connection::class],
                    ['Used database driver implementation', $connection->getDriver()::class],
                    ['Used database platform implementation', $platform::class],
                    ['Used database platform version', (new \ReflectionMethod($connection, 'getDatabasePlatformVersion'))->invoke($connection) ?? 'N/A'],
                    ['Used built-in database driver', $configuredBuiltInDriver ? sprintf('%s (%s)', $configuredBuiltInDriver, empty(phpversion($configuredBuiltInDriver)) ? 'N/A' : phpversion($configuredBuiltInDriver)) : 'N/A'],
                ]
            );
        }

        return Command::SUCCESS;
    }
}
