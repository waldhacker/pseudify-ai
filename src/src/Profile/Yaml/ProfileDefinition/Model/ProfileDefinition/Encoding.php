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

/**
 * @internal
 */
class Encoding extends AbstractEntity implements IdentifierAwareInterface
{
    public function __construct(
        #[Groups(['prototype', 'userland'])]
        protected string $identifier,
        #[Groups(['prototype', 'userland'])]
        protected string $name = '',
        /** @var string[] $conditions */
        #[Groups(['prototype', 'userland'])]
        protected array $conditions = [],
        /** @var Encoder[] $encoders */
        #[Groups(['prototype', 'userland'])]
        protected array $encoders = [],
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

    public function addCondition(string $condition): Encoding
    {
        $this->conditions[] = $condition;

        return $this;
    }

    public function hasEncoders(): bool
    {
        return !empty($this->encoders);
    }

    /**
     * @return Encoder[]
     */
    public function getEncoders(): array
    {
        return $this->encoders;
    }

    public function addEncoder(Encoder $encoder): Encoding
    {
        return $this->addToCollection($this->encoders, $encoder);
    }

    public function getEncoderByIdentifier(string $identifier): ?Encoder
    {
        /** @var ?Encoder $encoder */
        $encoder = $this->getFromCollectionByIdentifier($this->encoders, $identifier);

        return $encoder;
    }

    public function removeEncoder(Encoder $encoder): Encoding
    {
        return $this->removeFromCollection($this->encoders, $encoder);
    }

    public function merge(self $encoding): Encoding
    {
        /** @var Encoder[] $encoders */
        $encoders = $this->mergeCollection('encoders', $encoding);

        return new self(
            $this->identifier,
            $encoding->getName(),
            array_merge($this->conditions, $encoding->getConditions()),
            $encoders
        );
    }
}
