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

namespace Waldhacker\Pseudify\Core\Processor\Encoder;

use Symfony\Component\Serializer\Encoder\XmlEncoder as SymfonyEncoder;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Encoder\XmlEncoderType;

class XmlEncoder extends AbstractEncoder implements EncoderInterface
{
    final public const string AS_COLLECTION = SymfonyEncoder::AS_COLLECTION;
    final public const string DECODER_IGNORED_NODE_TYPES = SymfonyEncoder::DECODER_IGNORED_NODE_TYPES;
    final public const string ENCODER_IGNORED_NODE_TYPES = SymfonyEncoder::ENCODER_IGNORED_NODE_TYPES;
    final public const string ENCODING = SymfonyEncoder::ENCODING;
    final public const string FORMAT_OUTPUT = SymfonyEncoder::FORMAT_OUTPUT;
    final public const string LOAD_OPTIONS = SymfonyEncoder::LOAD_OPTIONS;
    final public const string REMOVE_EMPTY_TAGS = SymfonyEncoder::REMOVE_EMPTY_TAGS;
    final public const string ROOT_NODE_NAME = SymfonyEncoder::ROOT_NODE_NAME;
    final public const string STANDALONE = SymfonyEncoder::STANDALONE;
    final public const string TYPE_CAST_ATTRIBUTES = SymfonyEncoder::TYPE_CAST_ATTRIBUTES;
    final public const string VERSION = SymfonyEncoder::VERSION;

    /** @var array<string, mixed> */
    protected array $defaultContext = [
        self::AS_COLLECTION => false,
        self::DECODER_IGNORED_NODE_TYPES => [\XML_PI_NODE, \XML_COMMENT_NODE],
        self::ENCODER_IGNORED_NODE_TYPES => [],
        self::LOAD_OPTIONS => \LIBXML_NONET | \LIBXML_NOBLANKS,
        self::REMOVE_EMPTY_TAGS => false,
        self::ROOT_NODE_NAME => 'response',
        self::TYPE_CAST_ATTRIBUTES => true,
        self::DATA_PICKER_PATH => null,
    ];

    protected SymfonyEncoder $concreteEncoder;

    /**
     * @param array<string, mixed> $defaultContext
     *
     * @api
     */
    public function __construct(array $defaultContext = [])
    {
        parent::__construct($defaultContext);
        $this->concreteEncoder = new SymfonyEncoder($this->defaultContext);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function setContext(array $context): EncoderInterface
    {
        $this->defaultContext = array_merge($this->defaultContext, $context);
        $this->concreteEncoder = new SymfonyEncoder($this->defaultContext);

        return $this;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<array-key, mixed>|null
     *
     * @api
     */
    #[\Override]
    public function decode(mixed $data, array $context = []): mixed
    {
        if (!is_string($data)) {
            return null;
        }

        $context = array_merge($this->defaultContext, $context);

        return (array) $this->concreteEncoder->decode($data, SymfonyEncoder::FORMAT, $context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return string|false
     *
     * @api
     */
    #[\Override]
    public function encode(mixed $data, array $context = []): mixed
    {
        if (!is_array($data)) {
            return false;
        }

        $context = array_merge($this->defaultContext, $context);

        return $this->concreteEncoder->encode($data, SymfonyEncoder::FORMAT, $context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function canDecode(mixed $data, array $context = []): bool
    {
        try {
            $decodedData = $this->decode($data, $context);
            if (is_array($decodedData)) {
                return true;
            }
        } catch (\Throwable) {
        }

        return false;
    }

    /**
     * @api
     */
    #[\Override]
    public function decodesToScalarDataOnly(): bool
    {
        return false;
    }

    /**
     * @api
     */
    #[\Override]
    public function getContextFormTypeClassName(): ?string
    {
        return XmlEncoderType::class;
    }
}
