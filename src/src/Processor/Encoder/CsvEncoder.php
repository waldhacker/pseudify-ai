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

use Symfony\Component\Serializer\Encoder\CsvEncoder as SymfonyEncoder;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Encoder\CsvEncoderType;

class CsvEncoder extends AbstractEncoder implements EncoderInterface
{
    final public const string AS_COLLECTION_KEY = SymfonyEncoder::AS_COLLECTION_KEY;
    final public const string DELIMITER_KEY = SymfonyEncoder::DELIMITER_KEY;
    final public const string ENCLOSURE_KEY = SymfonyEncoder::ENCLOSURE_KEY;
    final public const string ESCAPE_CHAR_KEY = SymfonyEncoder::ESCAPE_CHAR_KEY;
    final public const string ESCAPE_FORMULAS_KEY = SymfonyEncoder::ESCAPE_FORMULAS_KEY;
    final public const string HEADERS_KEY = SymfonyEncoder::HEADERS_KEY;
    final public const string KEY_SEPARATOR_KEY = SymfonyEncoder::KEY_SEPARATOR_KEY;
    final public const string NO_HEADERS_KEY = SymfonyEncoder::NO_HEADERS_KEY;
    final public const string OUTPUT_UTF8_BOM_KEY = SymfonyEncoder::OUTPUT_UTF8_BOM_KEY;

    /** @var array<string, mixed> */
    protected array $defaultContext = [
        self::AS_COLLECTION_KEY => true,
        self::DELIMITER_KEY => ',',
        self::ENCLOSURE_KEY => '"',
        self::ESCAPE_CHAR_KEY => '',
        self::ESCAPE_FORMULAS_KEY => false,
        self::HEADERS_KEY => [],
        self::KEY_SEPARATOR_KEY => '.',
        self::NO_HEADERS_KEY => true,
        self::OUTPUT_UTF8_BOM_KEY => false,
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
            if (is_array($decodedData) && count($decodedData[0] ?? []) > 1) {
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
        return CsvEncoderType::class;
    }
}
