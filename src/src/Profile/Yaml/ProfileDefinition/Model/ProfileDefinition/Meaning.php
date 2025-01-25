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

namespace Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition;

use Symfony\Component\Serializer\Annotation\Groups;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning\Property;

/**
 * @internal
 */
class Meaning extends AbstractEntity implements IdentifierAwareInterface
{
    public function __construct(
        #[Groups(['prototype', 'userland'])]
        protected string $identifier,
        #[Groups(['prototype', 'userland'])]
        protected Property $property,
        #[Groups(['prototype', 'userland'])]
        protected string $name = '',
        /** @var string[] $conditions */
        #[Groups(['prototype', 'userland'])]
        protected array $conditions = [],
    ) {
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasConditions(): bool
    {
        return !empty($this->conditions);
    }

    /**
     * @return string[]
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function addCondition(string $condition): Meaning
    {
        $this->conditions[] = $condition;

        return $this;
    }

    public function getProperty(): Property
    {
        return $this->property;
    }

    public function setProperty(Property $property): Meaning
    {
        $this->property = $property;

        return $this;
    }

    public function merge(self $meaning): Meaning
    {
        return new self(
            $this->identifier,
            $meaning->getProperty(),
            $meaning->getName(),
            array_merge($this->conditions, $meaning->getConditions())
        );
    }
}
