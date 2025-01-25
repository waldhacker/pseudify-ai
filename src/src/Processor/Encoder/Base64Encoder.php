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

use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Encoder\Base64EncoderType;

class Base64Encoder extends AbstractEncoder implements EncoderInterface
{
    final public const string DECODE_STRICT = 'base64_decode_strict';

    /** @var array<string, mixed> */
    protected array $defaultContext = [
        self::DECODE_STRICT => false,
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

        $strict = is_bool($context[self::DECODE_STRICT] ?? null) ? (bool) $context[self::DECODE_STRICT] : (bool) $this->defaultContext[self::DECODE_STRICT];

        return @base64_decode($data, $strict);
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
        if (!is_string($data)) {
            return null;
        }

        return @base64_encode($data);
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
        return Base64EncoderType::class;
    }
}
