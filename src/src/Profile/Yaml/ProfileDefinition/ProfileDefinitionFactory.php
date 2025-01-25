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

namespace Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Database\Schema;
use Waldhacker\Pseudify\Core\Profile\Model\Analyze\TableDefinition as AnalyzeTableDefinition;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition;

/**
 * @internal
 */
class ProfileDefinitionFactory
{
    /** @var array<string, mixed> */
    private array $runtimeCache = [];

    public function __construct(
        private readonly ProfileDefinitionCollection $profileDefinitionCollection,
        private readonly Schema $schema,
        private readonly ConnectionManager $connectionManager,
        private readonly ProfileDefinitionSerializerFactory $profileDefinitionSerializerFactory,
        private readonly TagAwareCacheInterface $cache,
    ) {
    }

    public function load(string $identifier, ?string $connectionName = null, bool $reload = false): ProfileDefinition
    {
        $this->connectionManager->setConnectionName($connectionName);

        if ($reload) {
            $this->profileDefinitionCollection->reload();
        }

        $profileDefinitionPrototype = $this->buildProfileDefinitionPrototype();
        $profileDefinition = $profileDefinitionPrototype->merge($this->profileDefinitionCollection->getProfileDefinition($identifier));

        // @todo: don't remove, taint?
        foreach ($profileDefinition->getTables() as $table) {
            if (!$profileDefinitionPrototype->tableExists($table)) {
                $profileDefinition->removeTable($table);
                continue;
            }

            foreach ($profileDefinition->getColumns($table) as $column) {
                if (!$profileDefinitionPrototype->columnExists($table, $column)) {
                    $profileDefinition->removeColumn($table, $column);
                }
            }
        }

        return $profileDefinition;
    }

    public function create(string $identifier, string $description, ?string $connectionName = null): ProfileDefinition
    {
        $this->connectionManager->setConnectionName($connectionName);

        $profileDefinition = $this->buildProfileDefinitionPrototype($identifier, $description);
        $profileDefinition->setExcludedTargetColumnTypes(AnalyzeTableDefinition::COMMON_EXCLUED_TARGET_COLUMN_TYPES);

        return $profileDefinition;
    }

    private function buildProfileDefinitionPrototype(?string $identifier = null, ?string $description = null): ProfileDefinition
    {
        $cacheKey = sprintf('profile_definition_prototype_%s', hash('md5', json_encode([$identifier, $description], JSON_THROW_ON_ERROR)));
        $profileDefinitionPrototype = $this->runtimeCache[$cacheKey] ?? [
            'identifier' => $identifier ?? '__prototype',
            'description' => $description ?? '',
            'schema' => [
                'tables' => array_map(
                    fn (string $table): array => [
                        'identifier' => $table,
                        'columns' => array_map(
                            fn (array $column): array => [
                                'identifier' => $column['name'],
                                'databaseType' => $column['column']->getType()->getName(),
                            ],
                            array_values($this->schema->listTableColumns($table))
                        ),
                    ],
                    $this->schema->listTableNames()
                ),
            ],
        ];
        $this->runtimeCache[$cacheKey] = $profileDefinitionPrototype;

        $cacheKey = sprintf('profile_definition_prototype_%s', hash('md5', json_encode($profileDefinitionPrototype, JSON_THROW_ON_ERROR)));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($profileDefinitionPrototype): ProfileDefinition {
            $item->tag(['profile_definition_prototype']);

            return $this->profileDefinitionSerializerFactory
                ->create()
                ->denormalize(
                    $profileDefinitionPrototype,
                    ProfileDefinition::class,
                    'array',
                    [
                        AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
                        AbstractNormalizer::GROUPS => ['prototype'],
                    ]
                )
            ;
        });
    }
}
