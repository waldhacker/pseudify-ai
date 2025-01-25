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

use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Loader\ProfileDefinitionLoader;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition;

/**
 * @internal
 */
class ProfileDefinitionCollection
{
    /** @var array<string, ProfileDefinition> */
    private array $profileDefinitions = [];

    public function __construct(private readonly ProfileDefinitionLoader $loader, private readonly string $dataDirectory)
    {
        $this->reload();
    }

    public function hasProfileDefinition(string $identifier): bool
    {
        return isset($this->profileDefinitions[$identifier]);
    }

    public function getProfileDefinition(string $identifier): ProfileDefinition
    {
        if (!$this->hasProfileDefinition($identifier)) {
            throw new MissingProfileDefinitionException(sprintf('missing profile definition "%s"', $identifier), 1_706_133_088);
        }

        return $this->profileDefinitions[$identifier];
    }

    public function addProfileDefinition(ProfileDefinition $profileDefinition): ProfileDefinitionCollection
    {
        $this->profileDefinitions[$profileDefinition->getIdentifier()] = $profileDefinition;

        return $this;
    }

    public function removeProfileDefinition(string $identifier): ProfileDefinitionCollection
    {
        unset($this->profileDefinitions[$identifier]);

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getProfileDefinitionIdentifiers(): array
    {
        return array_keys($this->profileDefinitions);
    }

    public function reload(): void
    {
        $this->profileDefinitions = [];

        $profileDefinitions = $this->loader->import(rtrim($this->dataDirectory, '/').'/*.yaml');
        $profileDefinitions = is_array($profileDefinitions) ? $profileDefinitions : [$profileDefinitions];
        foreach ($profileDefinitions as $profileDefinition) {
            if (!$profileDefinition instanceof ProfileDefinition) {
                continue;
            }
            $this->profileDefinitions[$profileDefinition->getIdentifier()] = $profileDefinition;
        }
    }
}
