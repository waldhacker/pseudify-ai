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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Processor\AutoConfigurationProcessor;
use Waldhacker\Pseudify\Core\Profile\ProfileCollection;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\ProfileDefinitionCollection;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\ProfileDefinitionFactory;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Writer\ProfileDefinitionWriter;

#[AsCommand(
    name: 'pseudify:autoconfiguration',
    description: 'Try to configure a profile automatically (experimental)',
    hidden: true,
)]
class AutoConfigurationCommand extends Command
{
    public function __construct(
        private readonly ProfileCollection $profileCollection,
        private readonly ProfileDefinitionCollection $profileDefinitionCollection,
        private readonly ProfileDefinitionFactory $profileDefinitionFactory,
        private readonly ProfileDefinitionWriter $profileDefinitionWriter,
        private readonly AutoConfigurationProcessor $processor,
        private readonly ConnectionManager $connectionManager,
        private readonly TagAwareCacheInterface $cache,
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
                'The analyzation profile'
            )
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
        $connectionName = $this->initializeConnection($input);
        $this->cache->invalidateTags(['auto_configuration']);

        try {
            /** @var array<int, string|int>|string|null $profile */
            $profile = $input->getArgument('profile') ?? '';
            $profile = is_array($profile) ? (string) (null == $profile[0]) : (string) $profile;

            if ($this->profileDefinitionCollection->hasProfileDefinition($profile)) {
                $profileDefinition = $this->profileDefinitionFactory->load($profile, $connectionName);
            } else {
                if (
                    $this->profileCollection->hasProfile(ProfileCollection::SCOPE_ANALYZE, $profile, $connectionName)
                    || $this->profileCollection->hasProfile(ProfileCollection::SCOPE_PSEUDONYMIZE, $profile, $connectionName)
                ) {
                    throw new InvalidArgumentException(sprintf('Invalid profile "%s". Only YAML based profiles are allowed.', $profile), 1735848399);
                }

                $profileDefinition = $this->profileDefinitionFactory->create($profile, '', $connectionName);
            }

            $profileDefinition = $this->processor->setIo($input, $output)->process($profileDefinition);
            $this->profileDefinitionWriter->write($profileDefinition->getIdentifier(), $profileDefinition);
        } finally {
            $this->cache->invalidateTags(['auto_configuration']);
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
