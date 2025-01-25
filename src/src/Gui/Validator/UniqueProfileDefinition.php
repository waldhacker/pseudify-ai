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

namespace Waldhacker\Pseudify\Core\Gui\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueProfileDefinition extends Constraint
{
    public string $message = 'The profile identifier "{{ string }}" is already in use.';
    public ?string $allowedIdentifier = null;

    /**
     * @param array<array-key, mixed>|null $options
     * @param array<array-key, mixed>|null $groups
     */
    public function __construct(?string $allowedIdentifier = null, ?array $options = null, ?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct($options ?? [], $groups, $payload);

        $this->message = $message ?? $this->message;
        $this->allowedIdentifier = $allowedIdentifier ?? $this->allowedIdentifier;
    }
}
