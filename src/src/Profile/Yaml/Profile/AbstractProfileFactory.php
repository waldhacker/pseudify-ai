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

namespace Waldhacker\Pseudify\Core\Profile\Yaml\Profile;

use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Dto\ColumnConfigurationDtoFactory;
use Waldhacker\Pseudify\Core\Profile\Analyze\TableDefinitionAutoConfiguration as AnalyzeTableDefinitionAutoConfiguration;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\TableDefinitionAutoConfiguration as PseudonymizeTableDefinitionAutoConfiguration;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\ProfileDefinitionCollection;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\ProfileDefinitionFactory;

/**
 * @internal
 */
abstract class AbstractProfileFactory
{
    public function __construct(
        protected readonly ProfileDefinitionCollection $profileDefinitionCollection,
        protected readonly ProfileDefinitionFactory $profileDefinitionFactory,
        protected readonly AnalyzeTableDefinitionAutoConfiguration $analyzeTableDefinitionAutoConfiguration,
        protected readonly PseudonymizeTableDefinitionAutoConfiguration $pseudonymizeTableDefinitionAutoConfiguration,
        protected readonly TagAwareCacheInterface $cache,
    ) {
    }

    /**
     * @param array<string, string> $columnConditions
     *
     * @return string[]
     */
    protected function buildMeaningConditions(Meaning $meaning, array $columnConditions): array
    {
        $conditions = [];
        foreach ($meaning->getConditions() as $condition) {
            if (str_starts_with($condition, ColumnConfigurationDtoFactory::CONDITION_COPY_DIRECTIVE)) {
                $conditionIdentifier = substr($condition, strlen(ColumnConfigurationDtoFactory::CONDITION_COPY_DIRECTIVE));
                if (isset($columnConditions[$conditionIdentifier]) && !isset($conditions['reference'])) {
                    $conditions['reference'] = $columnConditions[$conditionIdentifier];
                }
            } else {
                $conditions[] = $condition;
            }
        }

        return $conditions;
    }
}
