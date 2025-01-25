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

namespace Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning;

use Symfony\Component\Serializer\Annotation\Groups;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\AbstractEntity;

/**
 * @internal
 */
class Property extends AbstractEntity
{
    public function __construct(
        #[Groups(['prototype', 'userland'])]
        protected ?string $path,
        #[Groups(['prototype', 'userland'])]
        protected ?string $scope,
        #[Groups(['prototype', 'userland'])]
        protected ?string $type,
        #[Groups(['prototype', 'userland'])]
        protected ?int $minimumGraphemeLength,
        /** @var array<array-key, mixed> $context */
        #[Groups(['prototype', 'userland'])]
        protected array $context = [],
    ) {
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getMinimumGraphemeLength(): ?int
    {
        return $this->minimumGraphemeLength;
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
    public function setContext(array $context): Property
    {
        $this->context = $context;

        return $this;
    }

    public function merge(self $encoder): Property
    {
        return new self(
            $encoder->getPath(),
            $encoder->getScope(),
            $encoder->getType(),
            $encoder->getMinimumGraphemeLength(),
            $encoder->getContext()
        );
    }
}
