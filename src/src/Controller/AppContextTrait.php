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

namespace Waldhacker\Pseudify\Core\Controller;

use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition;

/**
 * @internal
 */
trait AppContextTrait
{
    /**
     * @return array{app: array{version: string}, current_year: numeric-string, activeProfile: ProfileDefinition|null, activeProfileHasUpdates : bool, activeConnectionName: string}
     */
    private function getAppContext(): array
    {
        $session = $this->requestStack->getSession();

        /** @var ?string $buildTag */
        $buildTag = $this->params->get('build_tag');
        /** @var ?ProfileDefinition $profileDefinition */
        $profileDefinition = $session->get('profileDefinition', null);
        /** @var bool $activeProfileHasUpdates */
        $activeProfileHasUpdates = $session->get('activeProfileHasUpdates', false);
        /** @var string $activeConnectionName */
        $activeConnectionName = $session->get('activeConnectionName', 'default');

        return [
            'app' => [
                'version' => $buildTag,
            ],
            'current_year' => date('Y'),
            'activeProfile' => $profileDefinition,
            'activeProfileHasUpdates' => $activeProfileHasUpdates,
            'activeConnectionName' => $activeConnectionName,
        ];
    }
}
