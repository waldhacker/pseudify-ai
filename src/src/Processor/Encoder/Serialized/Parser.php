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

namespace Waldhacker\Pseudify\Core\Processor\Encoder\Serialized;

use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node\ArrayElementNode;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node\ArrayNode;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node\AttributeNode;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node\BooleanNode;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node\FloatNode;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node\IntegerNode;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node\NullNode;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node\ObjectNode;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node\RecursionByReferenceNode;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node\RecursionNode;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node\SerializableObjectNode;
use Waldhacker\Pseudify\Core\Processor\Encoder\Serialized\Node\StringNode;

/**
 * Based on qafoo/ser-pretty
 * https://github.com/Qafoo/ser-pretty.
 */
class Parser
{
    final public const string TOKEN_NULL = 'N';
    final public const string TOKEN_BOOLEAN = 'b';
    final public const string TOKEN_INTEGER = 'i';
    final public const string TOKEN_FLOAT = 'd';
    final public const string TOKEN_STRING = 's';
    final public const string TOKEN_ARRAY = 'a';
    final public const string TOKEN_OBJECT = 'O';
    final public const string TOKEN_SERIALIZABLE_OBJECT = 'C';
    final public const string TOKEN_RECURSION = 'r';
    final public const string TOKEN_RECURSION_BY_REFERENCE = 'R';

    protected int $currentIndex = 0;
    private int $dataLength = 0;
    private string $serializedData = '';

    /*
     * @api
     */
    public function parse(string $serializedData): Node
    {
        $this->serializedData = $serializedData;
        $this->currentIndex = 0;
        $this->dataLength = strlen($serializedData);

        return $this->parseInternal();
    }

    protected function parseInternal(): Node
    {
        while ($this->currentIndex < $this->dataLength) {
            $dataType = $this->serializedData[$this->currentIndex];

            return match ($dataType) {
                self::TOKEN_INTEGER => $this->parseInt(),
                self::TOKEN_STRING => $this->parseString(),
                self::TOKEN_ARRAY => $this->parseArray(),
                self::TOKEN_OBJECT => $this->parseObject(),
                self::TOKEN_FLOAT => $this->parseFloat(),
                self::TOKEN_NULL => $this->parseNull(),
                self::TOKEN_BOOLEAN => $this->parseBoolean(),
                self::TOKEN_RECURSION => $this->parseRecursion(),
                self::TOKEN_RECURSION_BY_REFERENCE => $this->parseRecursionByReference(),
                self::TOKEN_SERIALIZABLE_OBJECT => $this->parseSerializableObject(),
                default => throw new MissingDataTypeException($this->errorMessage('unknown data type "%s"', $dataType), 1_620_887_372),
            };
        }

        throw new InvalidDataException($this->errorMessage('unparsable data "%s"', $this->serializedData), 1_620_887_373);
    }

    /**
     * s:3:"foo";.
     */
    private function parseString(): StringNode
    {
        $this->advance(2);
        $string = $this->parseRawString();
        $this->advance(1);

        return new StringNode($string);
    }

    /**
     * s:0:"";.
     */
    private function parseRawString(): string
    {
        $stringLength = $this->parseRawInt();
        $this->advance(3);

        $string = '';
        for ($i = 0; $i < $stringLength; ++$i) {
            $string .= $this->current();
            $this->advance();
        }

        return $string;
    }

    /**
     * i:23;.
     */
    private function parseInt(): IntegerNode
    {
        $this->advance(2);
        $integer = $this->parseRawInt();
        $this->advance(1);

        return new IntegerNode($integer);
    }

    private function parseRawInt(): int
    {
        $integer = $this->current();

        if (!ctype_digit($integer)) {
            throw new InvalidDataException($this->errorMessage('unparsable data "%s"', $this->serializedData), 1735911979);
        }

        while (ctype_digit($this->peek())) {
            $this->advance();
            $integer .= $this->current();
        }

        return (int) $integer;
    }

    /**
     * d:42.5;.
     */
    private function parseFloat(): FloatNode
    {
        $this->advance(2);

        $float = '';
        do {
            $float .= $this->current();
            $this->advance();
        } while (';' !== $this->current());

        return new FloatNode((float) $float);
    }

    /**
     * r:66;.
     */
    private function parseRecursion(): RecursionNode
    {
        $this->advance(2);
        $reference = $this->parseRawInt();
        $this->advance(1);

        return new RecursionNode($reference);
    }

    /**
     * R:66;.
     */
    private function parseRecursionByReference(): RecursionByReferenceNode
    {
        $this->advance(2);
        $reference = $this->parseRawInt();
        $this->advance(1);

        return new RecursionByReferenceNode($reference);
    }

    /**
     * N;.
     */
    private function parseNull(): NullNode
    {
        $this->advance(1);

        return new NullNode();
    }

    /**
     * b:1;.
     */
    private function parseBoolean(): BooleanNode
    {
        $this->advance(2);
        $value = (bool) $this->parseRawInt();
        $this->advance(1);

        return new BooleanNode($value);
    }

    /**
     * a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}.
     */
    private function parseArray(): ArrayNode
    {
        $this->advance(2);

        $arrayCount = $this->parseRawInt();
        $this->advance(3);

        $arrayElementNodes = [];
        for ($i = 0; $i < $arrayCount; ++$i) {
            /** @var IntegerNode|StringNode $keyNode */
            $keyNode = $this->parseInternal();
            $this->advance();

            $valueNode = $this->parseInternal();
            $this->advance();

            $arrayElementNode = new ArrayElementNode($valueNode, $keyNode);
            $keyNode->setParent($arrayElementNode);
            $valueNode->setParent($arrayElementNode);

            $arrayElementNodes[$keyNode->getContent()] = $arrayElementNode;
        }

        $arrayNode = new ArrayNode($arrayElementNodes);
        foreach ($arrayElementNodes as $arrayElementNode) {
            $arrayElementNode->setParent($arrayNode);
        }

        return $arrayNode;
    }

    /**
     * O:27:"Waldhacker\Pseudify\Core\TestClass":2:{s:32:"\x00Waldhacker\Pseudify\Core\TestClass\x00Foo";i:0;s:3:"bar";s:3:"baz";}.
     */
    private function parseObject(): ObjectNode
    {
        $this->advance(2);
        $className = $this->parseRawString();
        $this->advance(2);

        $numAttributes = $this->parseRawInt();
        $this->advance(3);

        $attributeNodes = [];
        for ($i = 0; $i < $numAttributes; ++$i) {
            $rawAttributeName = $this->parseInternal();
            if (!$rawAttributeName instanceof StringNode) {
                throw new InvalidDataTypeException($this->errorMessage('invalid attribute type "%s"', $rawAttributeName::class), 1_620_887_374);
            }
            [$class, $name, $scope] = $this->parseAttributeName($rawAttributeName);

            $this->advance();
            $valueNode = $this->parseInternal();
            $this->advance();

            $attributeNode = new AttributeNode(
                $valueNode,
                $name,
                $scope,
                $class
            );
            $valueNode->setParent($attributeNode);

            $attributeNodes[$name] = $attributeNode;
        }

        $objectNode = new ObjectNode($attributeNodes, $className);
        foreach ($attributeNodes as $attributeNode) {
            $attributeNode->setParent($objectNode);
        }

        return $objectNode;
    }

    /**
     * @return array{string|null, string, "private"|"protected"|"public"}
     */
    private function parseAttributeName(StringNode $stringNode): array
    {
        $nameString = $stringNode->getContent();

        // private member
        // O:53:"Waldhacker\Pseudify\Core\Processor\Encoder\SerializedEncoder":1:{s:61:"\x00Waldhacker\Pseudify\Core\Processor\Encoder\SerializedEncoder\x00member";i:5;}
        // protected member
        // O:53:"Waldhacker\Pseudify\Core\Processor\Encoder\SerializedEncoder":1:{s:9:"\x00*\x00member";i:5;}
        // public member
        // O:53:"Waldhacker\Pseudify\Core\Processor\Encoder\SerializedEncoder":1:{s:6:"member";i:5;}
        if (preg_match('(^\x0([^\x0]+)\x0(.*)$)', $nameString, $matches)) {
            return [
                $matches[1],
                $matches[2],
                '*' === $matches[1] ? AttributeNode::SCOPE_PROTECTED : AttributeNode::SCOPE_PRIVATE,
            ];
        }

        return [
            null,
            $nameString,
            AttributeNode::SCOPE_PUBLIC,
        ];
    }

    /**
     * C:39:"Waldhacker\Pseudify\Core\SerializableTestClass":5:{i:23;}.
     */
    private function parseSerializableObject(): SerializableObjectNode
    {
        $this->advance(2);
        $className = $this->parseRawString();
        $this->advance(2);

        // Not needed, we just parse the content
        $this->parseRawInt();
        $this->advance(3);

        $contentNode = $this->parseInternal();
        $this->advance(1);

        $serializableObjectNode = new SerializableObjectNode($contentNode, $className);
        $contentNode->setParent($serializableObjectNode);

        return $serializableObjectNode;
    }

    private function advance(int $numChars = 1): void
    {
        $this->assertInBounds($numChars);
        $this->currentIndex += $numChars;
    }

    private function current(): string
    {
        return $this->serializedData[$this->currentIndex];
    }

    private function peek(int $offset = 1): string
    {
        $this->assertInBounds($offset);

        return $this->serializedData[$this->currentIndex + $offset];
    }

    private function assertInBounds(int $offset): void
    {
        if ($this->currentIndex + $offset >= $this->dataLength) {
            throw new OutOfBoundsException($this->errorMessage('offset: %s, max: %s', $offset, $this->dataLength), 1_620_887_375);
        }
    }

    private function errorMessage(string $message, mixed ...$arguments): string
    {
        /** @var string $errorMessage */
        $errorMessage = vsprintf($message, $arguments);

        return sprintf('%s (%s)', $errorMessage, $this->getContext());
    }

    private function getContext(): string
    {
        return sprintf(
            'char "%s" (#%d), context: …%s…',
            $this->serializedData[$this->currentIndex] ?? '',
            $this->currentIndex,
            substr($this->serializedData, $this->currentIndex, 7)
        );
    }
}
