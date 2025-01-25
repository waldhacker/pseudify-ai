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

use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Parser;

/**
 * Based on qafoo/ser-pretty
 * https://github.com/Qafoo/ser-pretty.
 */
class ArrayNode extends Node implements \ArrayAccess, \Iterator
{
    /**
     * @param array<array-key, ArrayElementNode> $properties
     *
     * @api
     */
    public function __construct(private array $properties, protected ?Node $parentNode = null)
    {
    }

    /**
     * @return array<array-key, ArrayElementNode>
     *
     * @api
     */
    public function getContent(): array
    {
        return $this->properties;
    }

    /*
     * semantic alias
     * @return array<array-key, ArrayElementNode>
     * @api
     */
    public function getProperties(): array
    {
        return $this->getContent();
    }

    /**
     * @api
     */
    public function hasProperty(string|int $identifier): bool
    {
        return isset($this->properties[$identifier]);
    }

    /**
     * @api
     */
    public function getProperty(string|int $identifier): ArrayElementNode
    {
        if (!$this->hasProperty($identifier)) {
            throw new MissingPropertyException(sprintf('missing array property "%s"', $identifier), 1_621_657_002);
        }

        return $this->properties[$identifier];
    }

    /**
     * semantic alias.
     *
     * @api
     */
    public function replaceProperty(string|int $identifier, mixed $property): ArrayNode
    {
        return $this->setProperty($identifier, $property);
    }

    /**
     * @api
     */
    public function setProperty(string|int $identifier, mixed $property): ArrayNode
    {
        $this->offsetSet($identifier, $property);

        return $this;
    }

    /**
     * semantic shortcut.
     *
     * @api
     */
    public function getPropertyContent(string|int $identifier): Node
    {
        if (!$this->hasProperty($identifier)) {
            throw new MissingPropertyException(sprintf('missing array property "%s"', $identifier), 1_621_657_003);
        }

        return $this->properties[$identifier]->getContent();
    }

    /**
     * semantic shortcut.
     *
     * @return array<array-key, Node>
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

    #[\Override]
    public function offsetSet($offset, $value): void
    {
        if (!($value instanceof Node)) {
            $value = (new Parser())->parse(serialize($value));
        }

        if (is_null($offset)) {
            $nummericKeys = empty($this->properties) ? [] : array_map('intval', array_filter(array_keys($this->properties), 'is_int'));
            $offset = empty($nummericKeys) ? 0 : max($nummericKeys) + 1;
        }

        if ($this->hasProperty($offset)) {
            $key = $this->getProperty($offset)->getKey();
        } else {
            $key = is_int($offset) ? new IntegerNode($offset) : new StringNode($offset);
        }

        $arrayElement = new ArrayElementNode($value, $key);
        $this->properties[$offset] = $arrayElement;
        $value->setParent($arrayElement);
        $key->setParent($arrayElement);
        $arrayElement->setParent($this);
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
