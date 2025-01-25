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

/**
 * @internal
 */
abstract class AbstractEntity
{
    /**
     * @param IdentifierAwareInterface[] $collection
     */
    protected function getFromCollectionByIdentifier(array &$collection, string $identifier): ?IdentifierAwareInterface
    {
        return array_values(array_filter($collection, fn (IdentifierAwareInterface $entity): bool => $entity->getIdentifier() === $identifier))[0] ?? null;
    }

    /**
     * @param IdentifierAwareInterface[] $collection
     *
     * @return static
     */
    protected function addToCollection(array &$collection, IdentifierAwareInterface $entity): self
    {
        $collection[] = $entity;

        return $this;
    }

    /**
     * @param IdentifierAwareInterface[] $collection
     *
     * @return static
     */
    protected function removeFromCollection(array &$collection, IdentifierAwareInterface $entity): self
    {
        $index = $this->getCollectionIndexByIdentifier($collection, $entity->getIdentifier());
        if (null === $index) {
            return $this;
        }

        array_splice($collection, $index, 1);

        return $this;
    }

    /**
     * @return IdentifierAwareInterface[]
     */
    protected function mergeCollection(string $collectionName, object $entityToMerge): array
    {
        $collection = [];
        foreach ($this->$collectionName as $entity) {
            $collection[$entity->getIdentifier()] = $entity;
        }

        $getter = 'get'.ucfirst($collectionName);
        foreach ($entityToMerge->$getter() as $collectionEntityToMerge) {
            if (!($collectionEntityToMerge instanceof IdentifierAwareInterface)) {
                continue;
            }

            $collection[$collectionEntityToMerge->getIdentifier()] = isset($collection[$collectionEntityToMerge->getIdentifier()])
                                              ? $collection[$collectionEntityToMerge->getIdentifier()]->merge($collectionEntityToMerge)
                                              : $collectionEntityToMerge
            ;
        }

        return array_values($collection);
    }

    /**
     * @param IdentifierAwareInterface[] $collection
     */
    private function getCollectionIndexByIdentifier(array $collection, string $identifier): ?int
    {
        foreach ($collection as $index => $entity) {
            if ($entity->getIdentifier() === $identifier) {
                return $index;
            }
        }

        return null;
    }
}
