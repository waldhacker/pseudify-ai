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
use Symfony\Component\Console\Style\SymfonyStyle;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Database\Schema;
use Waldhacker\Pseudify\Core\Processor\Encoder\AdvancedEncoderCollection;
use Waldhacker\Pseudify\Core\Processor\Encoder\ChainedEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\ConditionalEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\EncoderInterface;
use Waldhacker\Pseudify\Core\Profile\ProfileCollection;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\ProfileInterface;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\TableDefinitionAutoConfiguration;

#[AsCommand(
    name: 'pseudify:debug:pseudonymize',
    description: 'Show pseudonymize profile info',
)]
class DebugPseudonymizeProfileCommand extends Command
{
    public function __construct(
        private readonly ProfileCollection $profileCollection,
        private readonly TableDefinitionAutoConfiguration $tableDefinitionAutoConfiguration,
        private readonly AdvancedEncoderCollection $encoderCollection,
        private readonly ConnectionManager $connectionManager,
        private readonly Schema $schema,
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
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connectionName = $this->initializeConnection($input);

        /** @var array<int, string|int>|string|null $profile */
        $profile = $input->getArgument('profile') ?? '';
        $profile = is_array($profile) ? (string) (null == $profile[0]) : (string) $profile;

        if (!$this->profileCollection->hasProfile(ProfileCollection::SCOPE_PSEUDONYMIZE, $profile, $connectionName)) {
            throw new InvalidArgumentException(sprintf('invalid profile "%s". allowed profiles: "%s"', $profile, implode(',', $this->profileCollection->getProfileIdentifiers(ProfileCollection::SCOPE_PSEUDONYMIZE, $connectionName))), 1_668_974_694);
        }

        /** @var ProfileInterface $profile */
        $profile = $this->profileCollection->getProfile(ProfileCollection::SCOPE_PSEUDONYMIZE, $profile, $connectionName);

        $tableDefinition = $this->tableDefinitionAutoConfiguration->configure($profile->getTableDefinition());

        $io->title(sprintf('Pseudonymization profile "%s"', $tableDefinition->getIdentifier()));

        $io->section('Pseudonymize data in this tables');

        $tableData = [];
        foreach ($tableDefinition->getTables() as $table) {
            foreach ($table->getColumns() as $column) {
                $data = [
                    $table->getIdentifier(),
                    sprintf(
                        '%s (%s)',
                        $column->getIdentifier(),
                        $this->schema->getColumn($table->getIdentifier(), $column->getIdentifier())['column']->getType()->getName()
                    ),
                    implode('<>', $this->buildEncoderList($column->getEncoder())),
                    implode(PHP_EOL, $column->getDataProcessingIdentifiersWithConditions()),
                ];

                $tableData[] = $data;
            }
        }
        $io->table(['Table', 'column', 'data decoders', 'data manipulators'], $tableData);

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

    /**
     * @return array<int, scalar>
     */
    private function buildEncoderList(EncoderInterface $encoder): array
    {
        $encoders = [];
        if ($encoder instanceof ConditionalEncoder) {
            $conditionalEncoders = [];
            foreach ($encoder->getConditions() as $condition) {
                $expression = $condition[ConditionalEncoder::CONDITIONS_CONDITION];
                $conditionalEncoder = $condition[ConditionalEncoder::CONDITIONS_ENCODER];
                $conditionalEncoders[] = sprintf('%s [ %s ]', implode('>', $this->buildEncoderList($conditionalEncoder)), $expression);
            }

            $encoders[] = implode(PHP_EOL, $conditionalEncoders);
        } elseif ($encoder instanceof ChainedEncoder) {
            foreach ($encoder->getEncoders() as $subEncoder) {
                $encoders = array_merge($encoders, $this->buildEncoderList($subEncoder));
            }
        } else {
            $encoders[] = $this->encoderCollection->getEncoderShortName($encoder);
        }

        return $encoders;
    }
}
