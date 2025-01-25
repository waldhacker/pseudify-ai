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

use Symfony\Component\Serializer\Encoder\YamlEncoder as SymfonyEncoder;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Encoder\YamlEncoderType;

class YamlEncoder extends AbstractEncoder implements EncoderInterface
{
    final public const string PRESERVE_EMPTY_OBJECTS = SymfonyEncoder::PRESERVE_EMPTY_OBJECTS;
    final public const string YAML_FLAGS = SymfonyEncoder::YAML_FLAGS;
    final public const string YAML_INDENT = SymfonyEncoder::YAML_INDENT;
    final public const string YAML_INLINE = SymfonyEncoder::YAML_INLINE;

    /** @var array<string, mixed> */
    protected array $defaultContext = [
        self::YAML_FLAGS => 0,
        self::YAML_INDENT => 0,
        self::YAML_INLINE => 0,
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
        $this->concreteEncoder = new SymfonyEncoder(new Dumper(), new Parser(), $this->defaultContext);
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
        $this->concreteEncoder = new SymfonyEncoder(new Dumper(), new Parser(), $this->defaultContext);

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

        return $this->concreteEncoder->decode($data, SymfonyEncoder::FORMAT, $context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return string|null
     *
     * @api
     */
    #[\Override]
    public function encode(mixed $data, array $context = []): mixed
    {
        if (!is_array($data)) {
            return null;
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
        return YamlEncoderType::class;
    }
}
