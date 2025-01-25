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
use Waldhacker\Pseudify\Core\Processor\Encoder\EncoderInterface;

/**
 * @internal
 */
class Encoder extends AbstractEntity implements IdentifierAwareInterface
{
    public function __construct(
        #[Groups(['prototype', 'userland'])]
        protected string $identifier,
        #[Groups(['prototype', 'userland'])]
        protected EncoderInterface $encoder,
        /** @var array<array-key, mixed> $context */
        #[Groups(['prototype', 'userland'])]
        protected array $context = [],
    ) {
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getEncoder(): EncoderInterface
    {
        return $this->encoder;
    }

    public function setEncoder(EncoderInterface $encoder): Encoder
    {
        $this->encoder = $encoder;

        return $this;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array<array-key, mixed> $context
     */
    public function setContext(array $context): Encoder
    {
        $this->context = $context;

        return $this;
    }

    public function merge(self $encoder): Encoder
    {
        return new self(
            $this->identifier,
            $encoder->getEncoder(),
            $encoder->getContext()
        );
    }
}
