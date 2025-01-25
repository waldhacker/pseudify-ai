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

namespace Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node;

use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\InvalidDataException;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Parser;

/**
 * Based on qafoo/ser-pretty
 * https://github.com/Qafoo/ser-pretty.
 */
class ObjectNode extends Node implements \ArrayAccess, \Iterator
{
    /**
     * @param array<string, AttributeNode> $properties
     *
     * @api
     */
    public function __construct(private array $properties, private readonly string $className, protected ?Node $parentNode = null)
    {
    }

    /**
     * @return array<string, AttributeNode>
     *
     * @api
     */
    public function getContent(): array
    {
        return $this->properties;
    }

    /*
     * semantic alias
     * @return array<string, AttributeNode>
     * @api
     */
    public function getProperties(): array
    {
        return $this->getContent();
    }

    /**
     * @api
     */
    public function hasProperty(string $identifier): bool
    {
        return isset($this->properties[$identifier]);
    }

    /**
     * @api
     */
    public function getProperty(string $identifier): AttributeNode
    {
        if (!$this->hasProperty($identifier)) {
            throw new MissingPropertyException(sprintf('missing object property "%s" for object "%s"', $identifier, $this->className), 1_621_657_000);
        }

        return $this->properties[$identifier];
    }

    /**
     * semantic alias.
     *
     * @api
     */
    public function replaceProperty(string $identifier, mixed $property): ObjectNode
    {
        return $this->setProperty($identifier, $property);
    }

    /**
     * @api
     */
    public function setProperty(string $identifier, mixed $property): ObjectNode
    {
        $this->offsetSet($identifier, $property);

        return $this;
    }

    /**
     * semantic shortcut.
     *
     * @api
     */
    public function getPropertyContent(string $identifier): Node
    {
        if (!$this->hasProperty($identifier)) {
            throw new MissingPropertyException(sprintf('missing object property "%s" for object "%s"', $identifier, $this->className), 1_621_657_001);
        }

        return $this->properties[$identifier]->getContent();
    }

    /**
     * semantic shortcut.
     *
     * @return array<string, Node>
     *
     * @api
     */
    public function getPropertiesContents(): array
    {
        $properties = [];
        foreach ($this->properties as $identifier => $property) {
            $properties[$identifier] = $property->getContent();
        }

        return $properties;
    }

    /**
     * @api
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    #[\Override]
    public function offsetSet($offset, $value): void
    {
        if (!is_string($offset)) {
            throw new InvalidDataException('Object properties must be strings', 1_706_562_196);
        }

        if (empty($offset)) {
            throw new InvalidDataException('Object properties must be non empty strings', 1_706_562_197);
        }

        if (!$this->hasProperty($offset)) {
            throw new InvalidDataException('Only existing object properties can be written', 1_706_562_198);
        }

        if (!($value instanceof Node)) {
            $value = (new Parser())->parse(serialize($value));
        }

        $originalNode = $this->getProperty($offset);
        $attributeElement = new AttributeNode(
            $value,
            $originalNode->getPropertyName(),
            $originalNode->getScope(),
            $originalNode->getClassName()
        );

        $this->properties[$offset] = $attributeElement;
        $value->setParent($attributeElement);
        $attributeElement->setParent($this);
    }

    #[\Override]
    public function offsetExists($offset): bool
    {
        return $this->hasProperty($offset);
    }

    #[\Override]
    public function offsetUnset($offset): void
    {
        unset($this->properties[$offset]);
    }

    #[\Override]
    public function offsetGet($offset): mixed
    {
        return $this->hasProperty($offset)
                    ? (
                        $this->getProperty($offset)->getContent() instanceof \ArrayAccess
                            ? $this->getProperty($offset)->getContent()
                            : $this->getProperty($offset)->getPropertyContent()
                    )
                    : null
        ;
    }

    #[\Override]
    public function rewind(): void
    {
        reset($this->properties);
    }

    #[\ReturnTypeWillChange]
    #[\Override]
    public function current(): mixed
    {
        return current($this->properties);
    }

    #[\ReturnTypeWillChange]
    #[\Override]
    public function key(): mixed
    {
        return key($this->properties);
    }

    #[\Override]
    public function next(): void
    {
        next($this->properties);
    }

    #[\Override]
    public function valid(): bool
    {
        return null !== key($this->properties);
    }
}
