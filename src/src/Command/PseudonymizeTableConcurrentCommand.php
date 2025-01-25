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

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Database\Repository;
use Waldhacker\Pseudify\Core\Processor\PseudonymizeProcessor;
use Waldhacker\Pseudify\Core\Profile\ProfileCollection;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\ProfileInterface;

#[AsCommand(
    name: 'pseudify:pseudonymize:table:concurrent',
    description: 'Pseudonymize a table concurrent. This is an internal command.',
    hidden: true,
)]
class PseudonymizeTableConcurrentCommand extends Command
{
    use ConcurrentExecutionTrait;

    public function __construct(
        private readonly ProfileCollection $profileCollection,
        private readonly PseudonymizeProcessor $processor,
        private readonly ConnectionManager $connectionManager,
        private readonly Repository $repository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption(
                'pseudonymization-profile',
                null,
                InputOption::VALUE_REQUIRED,
                'The profile'
            )
            ->addOption(
                'table',
                null,
                InputOption::VALUE_REQUIRED,
                'The table'
            )
            ->addOption(
                'connection',
                null,
                InputOption::VALUE_REQUIRED,
                'The named database connection'
            )
            ->addOption(
                'page',
                null,
                InputOption::VALUE_REQUIRED,
                'The page'
            )
            ->addOption(
                'concurrency',
                null,
                InputOption::VALUE_REQUIRED,
                'How many parallel processes (parallel execution)',
                10
            )
            ->addOption(
                'items-per-process',
                null,
                InputOption::VALUE_REQUIRED,
                'How many rows to processes each parallel execution',
                5000
            )
            ->addOption(
                'request-id',
                null,
                InputOption::VALUE_REQUIRED,
                'The request id'
            )
            ->addOption(
                'initial',
                null,
                InputOption::VALUE_NONE,
                'Initial command'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show update queries while not executing it'
            )
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connectionName = $this->initializeConnection($input);

        /** @var array<int, string|int>|string|null $profileName */
        $profileName = $input->getOption('pseudonymization-profile') ?? null;
        $profileName = is_array($profileName) ? (string) ($profileName[0] ?? null) : (string) $profileName;

        /** @var array<int, string|int>|string|null $tableName */
        $tableName = $input->getOption('table') ?? null;
        $tableName = is_array($tableName) ? (string) $tableName[0] : (string) $tableName;

        /** @var array<int, string|int>|string|null $requestIdentifier */
        $requestIdentifier = $input->getOption('request-id') ?? null;
        $requestIdentifier = is_array($requestIdentifier) ? (string) $requestIdentifier[0] : (string) $requestIdentifier;

        /** @var array<int, string>|string|null $dryRun */
        $dryRun = $input->getOption('dry-run');
        $dryRun = is_array($dryRun) ? (bool) $dryRun[0] : (bool) $dryRun;

        /** @var array<int, string>|string|null $concurrency */
        $concurrency = $input->getOption('concurrency');
        $concurrency = is_array($concurrency) ? (int) $concurrency[0] : (int) $concurrency;

        /** @var array<int, string>|string|null $itemsPerProcess */
        $itemsPerProcess = $input->getOption('items-per-process');
        $itemsPerProcess = is_array($itemsPerProcess) ? (int) $itemsPerProcess[0] : (int) $itemsPerProcess;

        if (empty($profileName)) {
            throw new InvalidArgumentException('No profile given.', 1731590867);
        }
        if (empty($tableName)) {
            throw new InvalidArgumentException('No table given.', 1731590868);
        }
        if (empty($requestIdentifier)) {
            throw new InvalidArgumentException('No request id given.', 1731590869);
        }

        if ($input->getOption('initial')) {
            $itemCount = $this->repository->count($tableName);
            $pageCount = $itemCount > 0 ? (int) ceil($itemCount / $itemsPerProcess) : 0;

            if (0 === $pageCount) {
                return Command::SUCCESS;
            }

            $this->executeConcurrent(
                input: $input,
                output: $output,
                command: 'bin/pseudify',
                arguments: [
                    'pseudify:pseudonymize:table:concurrent',
                    '--request-id',
                    escapeshellarg($requestIdentifier),
                    '--pseudonymization-profile',
                    escapeshellarg($profileName),
                    '--table',
                    escapeshellarg($tableName),
                    '--connection',
                    escapeshellarg($connectionName ?? 'default'),
                    '--concurrency',
                    escapeshellarg((string) $concurrency),
                    '--items-per-process',
                    escapeshellarg((string) $itemsPerProcess),
                    '--page',
                    '{{ identifier }}',
                    $dryRun ? '--dry-run' : '',
                ],
                identifierPool: range(1, $pageCount),
                concurrency: $concurrency,
            );

            return Command::SUCCESS;
        }

        /** @var array<int, string|int>|string|null $page */
        $page = $input->getOption('page') ?? null;
        $page = is_array($page) ? (int) $page[0] : (int) $page;
        if ($page <= 0) {
            return Command::FAILURE;
        }

        if (!$this->profileCollection->hasProfile(ProfileCollection::SCOPE_PSEUDONYMIZE, $profileName, $connectionName)) {
            throw new InvalidArgumentException(sprintf('invalid profile "%s". allowed profiles: "%s"', $profileName, implode(',', $this->profileCollection->getProfileIdentifiers(ProfileCollection::SCOPE_PSEUDONYMIZE, $connectionName))), 1731599119);
        }

        /** @var ProfileInterface $profile */
        $profile = $this->profileCollection->getProfile(ProfileCollection::SCOPE_PSEUDONYMIZE, $profileName, $connectionName);

        $this->processor->process(
            profile: $profile,
            dryRun: $dryRun,
            tableName: $tableName,
            page: $page,
            itemsPerPage: $itemsPerProcess
        );

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
