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

namespace Waldhacker\Pseudify\Core\Profile;

use Waldhacker\Pseudify\Core\Profile\Analyze\ProfileCollection as AnalyzeProfileCollection;
use Waldhacker\Pseudify\Core\Profile\Pseudonymize\ProfileCollection as PseudonymizeProfileCollection;
use Waldhacker\Pseudify\Core\Profile\Yaml\Profile\AnalyzerProfileFactory;
use Waldhacker\Pseudify\Core\Profile\Yaml\Profile\Model\AnalyzerProfile as YamlAnalyzerProfile;
use Waldhacker\Pseudify\Core\Profile\Yaml\Profile\Model\PseudonymizeProfile as YamlPseudonymizeProfile;
use Waldhacker\Pseudify\Core\Profile\Yaml\Profile\PseudonymizeProfileFactory;

class ProfileCollection
{
    final public const string SCOPE_ANALYZE = 'analyze';
    final public const string SCOPE_PSEUDONYMIZE = 'pseudonymize';

    /** @var array<string, array<string, ProfileInterface>> */
    private array $profiles = [];

    private bool $initialized = false;

    /**
     * @internal
     */
    public function __construct(
        private readonly AnalyzerProfileFactory $analyzerProfileFactory,
        private readonly PseudonymizeProfileFactory $pseudonymizeProfileFactory,
        AnalyzeProfileCollection $analyzeProfileCollection,
        PseudonymizeProfileCollection $pseudonymizeProfileCollection,
    ) {
        foreach ($analyzeProfileCollection->getProfileIdentifiers() as $identifier) {
            $this->profiles[self::SCOPE_ANALYZE][$identifier] = $analyzeProfileCollection->getProfile($identifier);
        }

        foreach ($pseudonymizeProfileCollection->getProfileIdentifiers() as $identifier) {
            $this->profiles[self::SCOPE_PSEUDONYMIZE][$identifier] = $pseudonymizeProfileCollection->getProfile($identifier);
        }
    }

    /**
     * @api
     */
    public function hasProfile(string $scope, string $identifier, ?string $connectionName = null): bool
    {
        if (!$this->initialized) {
            $this->initialize($connectionName);
        }

        return isset($this->profiles[$scope][$identifier]);
    }

    /**
     * @api
     */
    public function getProfile(string $scope, string $identifier, ?string $connectionName = null): ProfileInterface
    {
        if (!$this->initialized) {
            $this->initialize($connectionName);
        }

        if (!$this->hasProfile($scope, $identifier)) {
            throw new MissingProfileException(sprintf('missing profile "%s" (%s)', $identifier, $scope), 1_729_507_406);
        }

        return $this->profiles[$scope][$identifier];
    }

    /**
     * @api
     */
    public function addProfile(string $scope, ProfileInterface $profile): ProfileCollection
    {
        $this->profiles[$scope][$profile->getIdentifier()] = $profile;

        return $this;
    }

    /**
     * @api
     */
    public function removeProfile(string $scope, string $identifier): ProfileCollection
    {
        unset($this->profiles[$scope][$identifier]);

        return $this;
    }

    /**
     * @return array<int, string>
     *
     * @api
     */
    public function getProfileIdentifiers(string $scope, ?string $connectionName = null): array
    {
        if (!$this->initialized) {
            $this->initialize($connectionName);
        }

        return array_keys($this->profiles[$scope] ?? []);
    }

    /**
     * @return array<int, string>
     *
     * @api
     */
    public function getYamlProfileIdentifiers(string $scope, ?string $connectionName = null): array
    {
        if (!$this->initialized) {
            $this->initialize($connectionName);
        }

        return array_keys(array_filter(
            $this->profiles[$scope] ?? [],
            fn (ProfileInterface $profileInterface): bool => ($profileInterface instanceof YamlAnalyzerProfile || $profileInterface instanceof YamlPseudonymizeProfile)
        ));
    }

    private function initialize(?string $connectionName = null): void
    {
        foreach ($this->analyzerProfileFactory->createProfiles($connectionName) as $profile) {
            $this->profiles[self::SCOPE_ANALYZE][$profile->getIdentifier()] = $profile;
        }
        foreach ($this->pseudonymizeProfileFactory->createProfiles($connectionName) as $profile) {
            $this->profiles[self::SCOPE_PSEUDONYMIZE][$profile->getIdentifier()] = $profile;
        }

        $this->initialized = true;
    }
}
