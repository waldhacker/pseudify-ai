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

namespace Waldhacker\Pseudify\Core\Processor\Processing;

class GenericDataProcessing extends DataProcessing implements GenericDataProcessingInterface
{
    /**
     * @param string[]|null             $conditions
     * @param array<string, mixed>|null $context
     *
     * @api
     */
    public function __construct(
        \Closure $processor,
        ?string $identifier = null,
        private readonly ?array $conditions = null,
        private readonly ?array $context = null,
    ) {
        parent::__construct($processor, $identifier);
    }

    /**
     * @internal
     */
    #[\Override]
    public function getCondition(): ?string
    {
        return empty($this->conditions) ? null : implode(' && ', array_map(fn (string $condition): string => sprintf('(%s)', $condition), $this->conditions));
    }

    /**
     * @return string[]|null
     *
     * @internal
     */
    #[\Override]
    public function getConditions(): ?array
    {
        return $this->conditions;
    }

    /**
     * @return array<string, mixed>|null
     *
     * @internal
     */
    #[\Override]
    public function getContext(): ?array
    {
        return $this->context;
    }
}
