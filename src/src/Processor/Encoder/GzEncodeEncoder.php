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

use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Encoder\GzEncodeEncoderType;

class GzEncodeEncoder extends AbstractEncoder implements EncoderInterface
{
    final public const string DECODE_MAX_LENGTH = 'gzdecode_max_length';
    final public const string ENCODE_ENCODING = 'gzencode_encoding';
    final public const string ENCODE_LEVEL = 'gzencode_level';

    /** @var array<string, mixed> */
    protected array $defaultContext = [
        self::DECODE_MAX_LENGTH => 0,
        self::ENCODE_ENCODING => ZLIB_ENCODING_GZIP,
        self::ENCODE_LEVEL => -1,
        self::DATA_PICKER_PATH => null,
    ];

    /**
     * @param array<string, mixed> $context
     *
     * @return string|false
     *
     * @api
     */
    #[\Override]
    public function decode(mixed $data, array $context = []): mixed
    {
        if (!is_string($data)) {
            return false;
        }

        $maxLength = is_int($context[self::DECODE_MAX_LENGTH] ?? null) ? (int) $context[self::DECODE_MAX_LENGTH] : (int) $this->defaultContext[self::DECODE_MAX_LENGTH];

        try {
            return @gzdecode($data, $maxLength);
        } catch (\ValueError) {
            return false;
        }
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
        if (!is_string($data)) {
            return false;
        }

        $level = is_int($context[self::ENCODE_LEVEL] ?? null) ? (int) $context[self::ENCODE_LEVEL] : (int) $this->defaultContext[self::ENCODE_LEVEL];
        $encoding = is_int($context[self::ENCODE_ENCODING] ?? null) ? (int) $context[self::ENCODE_ENCODING] : (int) $this->defaultContext[self::ENCODE_ENCODING];

        try {
            return @gzencode($data, $level, $encoding);
        } catch (\ValueError) {
            return false;
        }
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
            if (is_string($decodedData)) {
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
        return true;
    }

    /**
     * @api
     */
    #[\Override]
    public function getContextFormTypeClassName(): ?string
    {
        return GzEncodeEncoderType::class;
    }
}
