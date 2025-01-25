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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Processor\PseudonymizeProcessor;
use Waldhacker\Pseudify\Core\Profile\Model\Pseudonymize\Table;
use Waldhacker\Pseudify\Core\Profile\Model\Pseudonymize\TableDefinition;
use Waldhacker\Pseudify\Core\Profile\ProfileCollection;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\ProfileInterface;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\TableDefinitionAutoConfiguration;

#[AsCommand(
    name: 'pseudify:pseudonymize',
    description: 'Pseudonymize the database',
)]
class PseudonymizeCommand extends Command
{
    use ConcurrentExecutionTrait;

    public function __construct(
        private readonly ProfileCollection $profileCollection,
        private readonly PseudonymizeProcessor $processor,
        private readonly TableDefinitionAutoConfiguration $tableDefinitionAutoConfiguration,
        private readonly ConnectionManager $connectionManager,
        private readonly TagAwareCacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument(
                'profile',
                InputArgument::REQUIRED,
                'The pseudonymization profile'
            )
            ->addOption(
                'connection',
                null,
                InputOption::VALUE_REQUIRED,
                'The named database connection',
                null
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show update queries while not executing it'
            )->addOption(
                'parallel',
                'p',
                InputOption::VALUE_NONE,
                'Use parallel execution'
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
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $connectionName = $this->initializeConnection($input);
        $this->cache->invalidateTags(['pseudonymize_fakedata']);

        try {
            /** @var array<int, string|int>|string|null $profile */
            $profile = $input->getArgument('profile') ?? '';
            $profile = is_array($profile) ? (string) (null == $profile[0]) : (string) $profile;

            /** @var array<int, string>|string|null $dryRun */
            $dryRun = $input->getOption('dry-run');
            $dryRun = is_array($dryRun) ? (bool) $dryRun[0] : (bool) $dryRun;

            if (!$this->profileCollection->hasProfile(ProfileCollection::SCOPE_PSEUDONYMIZE, $profile, $connectionName)) {
                throw new InvalidArgumentException(sprintf('invalid profile "%s". allowed profiles: "%s"', $profile, implode(',', $this->profileCollection->getProfileIdentifiers(ProfileCollection::SCOPE_PSEUDONYMIZE, $connectionName))), 1_619_592_554);
            }

            /** @var ProfileInterface $profile */
            $profile = $this->profileCollection->getProfile(ProfileCollection::SCOPE_PSEUDONYMIZE, $profile, $connectionName);

            ProgressBar::setFormatDefinition('custom', ProgressBar::getFormatDefinition(ProgressBar::FORMAT_VERY_VERBOSE).'%message%');
            $progressBar = $output->isVerbose() ? new ProgressBar($output) : null;
            $progressBar?->setOverwrite(true);
            $progressBar?->setFormat('custom');
            $progressBar?->setMessage('');

            if ($input->getOption('parallel')) {
                /** @var array<int, string>|string|null $concurrency */
                $concurrency = $input->getOption('concurrency');
                $concurrency = is_array($concurrency) ? (int) $concurrency[0] : (int) $concurrency;

                /** @var array<int, string>|string|null $itemsPerProcess */
                $itemsPerProcess = $input->getOption('items-per-process');
                $itemsPerProcess = is_array($itemsPerProcess) ? (int) $itemsPerProcess[0] : (int) $itemsPerProcess;

                $requestIdentifier = sha1(random_bytes(100));
                $tableDefinition = $this->tableDefinitionAutoConfiguration->configure($profile->getTableDefinition());
                $tables = $tableDefinition->getTables();

                $progressBar?->setMaxSteps(count($tables));
                $progressBar?->start();

                foreach ($tables as $table) {
                    $progressBar?->setMessage(sprintf(' -- Process table %s', $table->getIdentifier()));
                    $progressBar?->display();

                    $this->executeConcurrent(
                        input: $input,
                        output: $output,
                        command: 'bin/pseudify',
                        arguments: [
                            'pseudify:pseudonymize:table:concurrent',
                            '--request-id',
                            escapeshellarg($requestIdentifier),
                            '--pseudonymization-profile',
                            escapeshellarg($profile->getIdentifier()),
                            '--table',
                            escapeshellarg($table->getIdentifier()),
                            '--connection',
                            escapeshellarg($connectionName ?? 'default'),
                            '--concurrency',
                            escapeshellarg((string) $concurrency),
                            '--items-per-process',
                            escapeshellarg((string) $itemsPerProcess),
                            '--initial',
                            $dryRun ? '--dry-run' : '',
                        ],
                        onTick: fn () => $progressBar?->display()
                    );

                    $progressBar?->advance();
                }
            } else {
                $this->processor->process(
                    profile: $profile,
                    dryRun: $dryRun,
                    onBeforeTables: function (TableDefinition $tableDefinition) use ($progressBar) {
                        $progressBar?->setMaxSteps(count($tableDefinition->getTables()));
                        $progressBar?->start();
                    },
                    onBeforeTable: function (Table $table) use ($progressBar) {
                        $progressBar?->setMessage(sprintf(' -- Process table %s', $table->getIdentifier()));
                        $progressBar?->display();
                    },
                    onAfterTable: fn (Table $table) => $progressBar?->advance(),
                    onTick: fn () => $progressBar?->display(),
                );
            }

            $progressBar?->finish();
            $io->newLine();
        } finally {
            $this->cache->invalidateTags(['pseudonymize_fakedata']);
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
