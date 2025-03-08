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
class Column extends AbstractEntity implements IdentifierAwareInterface
{
    public function __construct(
        #[Groups(['prototype', 'userland'])]
        protected string $identifier,
        /** @var Encoding[] $encodings */
        #[Groups(['prototype', 'userland'])]
        protected array $encodings = [],
        /** @var Meaning[] $meanings */
        #[Groups(['prototype', 'userland'])]
        protected array $meanings = [],
        #[Groups(['prototype'])]
        protected ?string $databaseType = null,
        #[Groups(['prototype', 'userland'])]
        protected string $columnDescription = '',
        #[Groups(['prototype', 'userland'])]
        protected bool $emptyTheColumn = false,
    ) {
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getColumnDescription(): string
    {
        return $this->columnDescription;
    }

    public function setColumnDescription(string $columnDescription): Column
    {
        $this->columnDescription = $columnDescription;

        return $this;
    }

    public function getEmptyTheColumn(): bool
    {
        return $this->emptyTheColumn;
    }

    public function setEmptyTheColumn(bool $emptyTheColumn): Column
    {
        $this->emptyTheColumn = $emptyTheColumn;

        return $this;
    }

    public function hasEncodings(): bool
    {
        return !empty($this->encodings);
    }

    /**
     * @return Encoding[]
     */
    public function getEncodings(): array
    {
        return $this->encodings;
    }

    public function addEncoding(Encoding $encoding): Column
    {
        return $this->addToCollection($this->encodings, $encoding);
    }

    /**
     * @param Encoding[] $encodings
     */
    public function setEncodings(array $encodings): Column
    {
        $this->encodings = array_filter($encodings, fn (Encoding $encoding): bool => $encoding instanceof Encoding);

        return $this;
    }

    public function getEncodingByIdentifier(string $identifier): ?Encoding
    {
        /** @var ?Encoding $encoding */
        $encoding = $this->getFromCollectionByIdentifier($this->encodings, $identifier);

        return $encoding;
    }

    public function removeEncoding(Encoding $encoding): Column
    {
        return $this->removeFromCollection($this->encodings, $encoding);
    }

    public function hasMeanings(): bool
    {
        return !empty($this->meanings);
    }

    /**
     * @return Meaning[]
     */
    public function getMeanings(): array
    {
        return $this->meanings;
    }

    public function addMeaning(Meaning $meaning): Column
    {
        return $this->addToCollection($this->meanings, $meaning);
    }

    /**
     * @param Meaning[] $meanings
     */
    public function setMeanings(array $meanings): Column
    {
        $this->meanings = array_filter($meanings, fn (Meaning $meaning): bool => $meaning instanceof Meaning);

        return $this;
    }

    public function getMeaningByIdentifier(string $identifier): ?Meaning
    {
        /** @var ?Meaning $meaning */
        $meaning = $this->getFromCollectionByIdentifier($this->meanings, $identifier);

        return $meaning;
    }

    public function removeMeaning(Meaning $meaning): Column
    {
        return $this->removeFromCollection($this->meanings, $meaning);
    }

    public function getDatabaseType(): ?string
    {
        return $this->databaseType;
    }

    public function merge(self $column): Column
    {
        /** @var Encoding[] $encodings */
        $encodings = $this->mergeCollection('encodings', $column);
        /** @var Meaning[] $meanings */
        $meanings = $this->mergeCollection('meanings', $column);

        return new self(
            $this->identifier,
            $encodings,
            $meanings,
            $this->databaseType,
            $column->getColumnDescription(),
            $column->getEmptyTheColumn(),
        );
    }
}
