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

use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder as SymfonyEncoder;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Encoder\JsonEncoderType;

class JsonEncoder extends AbstractEncoder implements EncoderInterface
{
    final public const string DECODE_ASSOCIATIVE = JsonDecode::ASSOCIATIVE;
    final public const string DECODE_OPTIONS = JsonDecode::OPTIONS;
    final public const string DECODE_RECURSION_DEPTH = JsonDecode::RECURSION_DEPTH;
    final public const string ENCODE_OPTIONS = JsonEncode::OPTIONS;

    /** @var array<string, mixed> */
    protected array $defaultContext = [
        self::DECODE_ASSOCIATIVE => true,
        self::DECODE_OPTIONS => 0,
        self::DECODE_RECURSION_DEPTH => 512,
        self::ENCODE_OPTIONS => 0,
        self::DATA_PICKER_PATH => null,
    ];

    protected JsonDecode $concreteDecoder;
    protected JsonEncode $concreteEncoder;

    /**
     * @param array<string, mixed> $defaultContext
     *
     * @api
     */
    public function __construct(array $defaultContext = [])
    {
        parent::__construct($defaultContext);
        $this->concreteEncoder = new JsonEncode($this->defaultContext);
        $this->concreteDecoder = new JsonDecode($this->defaultContext);
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
        $this->concreteEncoder = new JsonEncode($this->defaultContext);
        $this->concreteDecoder = new JsonDecode($this->defaultContext);

        return $this;
    }

    /**
     * @param array<string, mixed> $context
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

        return $this->concreteDecoder->decode($data, SymfonyEncoder::FORMAT, $context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return string
     *
     * @api
     */
    #[\Override]
    public function encode(mixed $data, array $context = []): mixed
    {
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
            if (is_array($decodedData) || is_object($decodedData)) {
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
        return JsonEncoderType::class;
    }
}
