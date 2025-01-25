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
class AttributeNode extends Node
{
    final public const string SCOPE_PRIVATE = 'private';
    final public const string SCOPE_PROTECTED = 'protected';
    final public const string SCOPE_PUBLIC = 'public';

    /*
     * @api
     */
    public function __construct(private Node $content, private readonly string $propertyName, private readonly string $scope, private readonly ?string $className = null, protected ?Node $parentNode = null)
    {
    }

    /*
     * @api
     */
    public function getContent(): Node
    {
        return $this->content;
    }

    /*
     * @api
     */
    public function setContent(mixed $content): AttributeNode
    {
        if (!($content instanceof Node)) {
            $content = (new Parser())->parse(serialize($content));
        }

        $content->setParent($this);
        $this->content = $content;

        return $this;
    }

    /*
     * semantic alias
     * @api
     */
    public function getValue(): Node
    {
        return $this->getContent();
    }

    /*
     * semantic alias
     * @api
     */
    public function setValue(mixed $content): AttributeNode
    {
        return $this->setContent($content);
    }

    /**
     * semantic shortcut.
     *
     * @api
     */
    public function getPropertyContent(): mixed
    {
        return $this->getContent()->getContent();
    }

    /*
     * @api
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /*
     * @api
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /*
     * @api
     */
    public function getScope(): string
    {
        return $this->scope;
    }
}
